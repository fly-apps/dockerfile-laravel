<?php

function ignoreFiles( )
{
    return ['composer.json','frankenphp','rr','.rr.yaml','package.json'];
}

function verifyFileCorrectlyGenerated( $test, $genDir, $refDir, $reference )
{
    // "tests/Feature/Dockerfile/fpm/pool.d/www.conf"
    $refFilePath = $reference->getPathName();
    $filePath    = trim( explode( $refDir, $refFilePath )[1], '/');
    $failedForMsg = 'Failed for: "'.$refFilePath.'"';

    // Skip if a setup file
    if( in_array( $filePath, ignoreFiles())  ) return false;

    // SECOND assert: a new file with the reference file's name was created; it should exist!
    $generatedFilePath = $genDir.'/'.$filePath;
    $test->assertFileExists( $generatedFilePath, $failedForMsg );
    
    // Contents of generated file
    $generated = file_get_contents( $generatedFilePath );
    // Override the reference file( found in getPathName ) with generated file content if needed; PLEASE double check diff and re-test this new ref manually!
    if( env('OVERRIDE_TEST_REFERENCES')===true )
        file_put_contents( $refFilePath, $generated );

    // Contents of reference file
    $expected = file_get_contents( $refFilePath ); 

    // THIRD assert: contents are the same
        // TODO: ignore different ARG VALUES
    $test->assertEquals( $expected, $generated, $failedForMsg); 

    // Clean UP: Delete generated file, no longer needed
    unlink( $generatedFilePath );

    return true;
}

/**
 * Sets up a test directory containing files combining base and snippet configuration:
 *  new composer json with merged details from base and snippet directories
 *  supporting files from base directory being tested( like rr.yaml, frankenphp binaries that are needed to detect the flavor of octane )
 * */
function setUpDirectoryForBaseSnippetCombo( $baseDirectory, $newComposerArr, $testDir )
{
    // Delete any previous combination folder
    if( is_dir($testDir) ){
        $fh = new \App\Services\File();
        $fh->deleteDir($testDir);
    }

    // Create combination directory
    mkdir($testDir);

    // Create new composer file using merged content
    file_put_contents( $testDir.'/composer.json', json_encode($newComposerArr, JSON_UNESCAPED_SLASHES));
    
    // Copy over supporting files from base directory to test directory
    $supportingFileNames = ignoreFiles(); 
    foreach( $supportingFileNames as $name ){
        $pathToBaseFile = $baseDirectory.'/'.$name;       
        if( file_exists($pathToBaseFile) ){
            $pathToTestFile = $testDir.'/'.$name;
            if( !file_exists($pathToTestFile) ){
                file_put_contents( 
                    $pathToTestFile,
                    file_get_contents($pathToBaseFile)
                );
            }     
        }
    }
}

function setFlags( $extCompArr, $pathToFiles ): string
{
    $flags = "";

    // Where to scan files in 
    if( $pathToFiles )
        $flags .= '--path="'.$pathToFiles.'" ';
    
    // Additional flags from mock composer.json
    if( isset($extCompArr['flags']) ){
        foreach( $extCompArr['flags'] as $flagKey=>$flagVal ){
            
            if( is_bool($flagVal) )
                $flags .= "--".$flagKey." ";
            else{
                $flags .= "--$flagKey='$flagVal' ";
            }
        }
    }

    return $flags;
}

// Test that expected files are generated properly for specific "base specifications"
it('generates proper templates for each supported base', function ( ) 
{
    $directories = \File::directories( 'tests/Feature/Supported' );   
    foreach($directories as $dir) {
        #if( $dir != "tests/Feature/Supported/10_octane_rr" ) continue;//-- revise and uncomment this line if you want to test out a specific Support subfolder
        // package.json is needed to generate a Dockerfile with asset build stage, will be deleted in second test below
        file_put_contents( $dir.'/package.json','{}' );

        // Generate Dockerfile, by scanning contents of files in the current directory, set through --path
        // FIRST assert: command successfully runs and exits
        $this->artisan('generate --path="'.$dir.'"')->assertExitCode(0);

        // Compare reference files from test directory with generated files
        $referenceFiles = \File::files( $dir );   
        foreach( $referenceFiles as $reference ){ 
            $failedForMsg = 'Failed for: "'.$reference->getPathName().'"';

            // Skip if a setup file
            if( in_array( $reference->getFileName(), ignoreFiles())  ) continue;

            // SECOND assert: a new file with the reference file's name was created; it should exist!
            $this->assertFileExists( $reference->getFileName(), $failedForMsg );
            
            // Contents of generated file
            $generated = file_get_contents( $reference->getFileName() );

            // Override the reference file with generated file content if needed; PLEASE double check diff and re-test this new ref manually!
            if( env('OVERRIDE_TEST_REFERENCES')===true )
                file_put_contents( $reference->getPathName(), $generated );

            // Contents of reference file
            $expected = file_get_contents( $reference->getPathName() ); 

            // THIRD assert: contents are the same
                // TODO: ignore different ARG VALUES
            $this->assertEquals( $expected, $generated, $failedForMsg); 

            // Clean UP: Delete generated file, no longer needed
            unlink( $reference->getFileName() );

        }

        if( is_dir('.fly') ){
            // Delete combination folder and files
            $fh = new \App\Services\File();
            $fh->deleteDir('.fly');
        }
    }    
    
});

// Tests whether snippets are added into generated files for special configurations
it('generates templates with proper snippets', function () 
{
    // Folders providing details that can combined with Base test directories
    $extDirectories = \File::directories( 'tests/Feature/Snippets' );
    // Base test directories
    $baseDirectories = \File::directories( 'tests/Feature/Supported' );   

    foreach($baseDirectories as $base) {
        // composer.json of base directory 
        $baseComposer = (new \App\Services\File())->composerJsonContent( $base );
       
        foreach( $extDirectories as $ext ){
            // skip frankenphp binary for now
            if( $ext == 'tests/Feature/Snippets/frankenphp_binary' ) continue;

            // composer.json of snippet directory 
            $extComposer = (new \App\Services\File())->composerJsonContent( $ext );

            // Merge composer together
            $newComposer = [
                'require' => array_merge( $extComposer['require'], $baseComposer['require'] )
            ];

            // Create new test directory for the base+snippet combination
            $testDir = 'tests/Feature/Combination';
            setUpDirectoryForBaseSnippetCombo( $base, $newComposer, $testDir );

            // Get flags from mock composer.json file 
            $flags = setFlags( $extComposer['extra'], $testDir );

            // Generate templates, by scanning contents of files in the combination folder
            // FIRST assert: command successfully runs and exits
            $this->artisan('generate '.$flags)->assertExitCode(0);
           
            // Verify that specific templates still matches the expected content from each base reference
            // BUT! contain the snippet expected from the snippet/extension folder
            foreach($extComposer['extra']['templates'] as $templateName){
                $failedFor = "Failed for:\n    Base Dir: ".$base."\n    Template Name: ".$templateName;
              
                // Get generated file content
                $generatedFileName = './'.$templateName;
                $generatedContent =  explode("\n", file_get_contents( $generatedFileName ) );
               
                // Get base reference file content
                $referenceFileName = $base.'/'.$templateName;
                if( file_exists($referenceFileName) ){
                    $referenceContent = explode("\n", file_get_contents($referenceFileName) );
                }else
                    $referenceContent = [""];
               
                // Get Difference  between generated and base reference
                $diff = new \Diff($referenceContent, $generatedContent);
                $renderer = new \Diff_Renderer_Text_Unified();
                $differenceFound = '';

              
                foreach (explode("\n", $diff->render($renderer)) as $line) {
                    if (
                        str_starts_with($line, '+') || 
                        str_starts_with($line, '-') 
                    ) {
                        $differenceFound .= trim( $line,'+|-' )."\n";
                    }
                }
               
                // Override the reference file with generated file content if needed; PLEASE double check diff and re-test this new ref manually!
                if( env('OVERRIDE_TEST_REFERENCES')===true )
                    file_put_contents( $ext.'/'.$templateName, $differenceFound );

                // There difference between the two should be the snippet added thanks to combining the base composer with snippet composer
                $differenceFound = trim( $differenceFound, "\n");
                $referenceContent = trim( file_get_contents( $ext.'/'.$templateName ),"\n");
                
                $this->assertEquals( $referenceContent, $differenceFound,   $failedFor );

                // Delete unnecessary files 
                unlink( $generatedFileName );
                // Delete combination folder and files
                $fh = new \App\Services\File();
                $fh->deleteDir('tests/Feature/Combination');
            }

            if( is_dir('.fly') ){
                // Delete combination folder and files
                $fh = new \App\Services\File();
                $fh->deleteDir('.fly');
            }
        } 

        // Delete the generated package.json file from "generates proper templates for each supported base"
        unlink( $base.'/package.json' );       
    }    
});

// Tests that config files requried by Dockerfiles are generated  
it('generates config files required by Dockerfiles', function()
{
    // Generate Dockerfile, by scanning contents of files in the current directory, set through --path
    // FIRST assert: command successfully runs and exits
    $this->artisan('generate')->assertExitCode(0);

    // Gather files to verify from the Dockerfile directory, containing files required by the Dockerfile generatec
    $baseDirectory = 'tests/Feature/Dockerfile';
    $fileList =  \File::files( $baseDirectory );
   
    // Get directories in Dockerfile   
    $directories = \File::directories( $baseDirectory );   
    foreach( $directories as $dir ){

        // Gather file paths from directory
        $fileList =  array_merge( $fileList, \File::files( $dir ) );

        // Gather files paths from subdirectory
        $subDirectories = \File::directories( $dir );   
        foreach( $subDirectories as $subDir ){
           $fileList = array_merge( $fileList, \File::files( $subDir ) );
        }
    }

    // Verify the file is created in the .fly directory
    foreach( $fileList as $file ){
        $result = verifyFileCorrectlyGenerated( $this, '.fly', $baseDirectory, $file );
    }

    if( is_dir('.fly') ){
        // Delete combination folder and files
        $fh = new \App\Services\File();
        $fh->deleteDir('.fly');
    }

    if( is_file('Dockerfile') )
        unlink('Dockerfile');

});

