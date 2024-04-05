<?php

function ignoreFiles( )
{
    return ['composer.json','frankenphp','rr','.rr.yaml'];
}

/**
 * Sets up a test directory containing:
 *  new composer json 
 *  supporting files from base directory being tested
 */
function setupTestDirectoryForExtensionBaseCombo( $baseDirectory, $newComposerArr, $testDirNameToCreate )
{
    // Files to delete later
    $filesCreatedInTestDir = [ 'composer.json'];

    // Create merged composer.json in test directory
    $testDir = $testDirNameToCreate;
    if( !is_dir($testDir) )
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
                $filesCreatedInTestDir[] = $name;
            }     
        }
    }
    return $filesCreatedInTestDir;
}

// Test that supported combinations' templates are successfully generated
it('generates proper templates for each supported combination', function ( ) 
{
    $directories = \File::directories( 'tests/Feature/Supported' );   
    foreach($directories as $dir) {
        #if( $dir != "tests/Feature/Supported/10_base" ) continue;//-- revise and uncomment this line if you want to test out a specific Support subfolder

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
    }    
});

// Tests whether snippets are added into generated files for special occasions/configurations
it('generates templates with proper snippets', function ( ) 
{
    $baseDirectories = \File::directories( 'tests/Feature/Supported' );   
    $extDirectories = \File::directories( 'tests/Feature/Snippets' );
    foreach($baseDirectories as $base) {
        // composer.json content of base directory reference
        $baseComposer = (new \App\Services\File())->composerJsonContent( $base );
       
        foreach( $extDirectories as $ext ){
            
            // composer.json content of extension directory reference
            $extComposer = (new \App\Services\File())->composerJsonContent( $ext );

            // Merge content together
            $newComposer = [
                'require' => array_merge( $extComposer['require'],$baseComposer['require'])
            ];

            $testDir = 'tests/Feature/Combination';
            $filesCreatedInTestDir = setupTestDirectoryForExtensionBaseCombo( $base, $newComposer, $testDir );

            // Generate Dockerfile, by scanning contents of files in the current directory, set through --path
            // FIRST assert: command successfully runs and exits
            $this->artisan('generate --path="'.$testDir.'"')->assertExitCode(0);
           
            // Get templates to check for the extension
            foreach($extComposer['extra']['templates'] as $templateName){
                $failedFor = "Failed for:\n    Base Dir: ".$base."\n    Template Name: ".$templateName;
              
                // Check Generated file
                $generatedFileName = './'.$templateName;
                $generatedContent =  explode("\n", file_get_contents( $generatedFileName ) );
               
                // Compare with Expected file for that base
                $referenceFileName = $base.'/'.$templateName;
                $referenceContent = explode("\n", file_get_contents($referenceFileName) );
               
                // Get Difference 
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

                // Compare differenceFound with expected difference
                $differenceFound = trim( $differenceFound, "\n");
                $referenceContent = trim( file_get_contents( $ext.'/'.$templateName ),"\n");
                $this->assertEquals( $referenceContent, $differenceFound,   $failedFor );

             
                // Delete unnecessary files 
                unlink( $generatedFileName );

                // Delete Combination Folder Files
                foreach( $filesCreatedInTestDir as $fileName ){
                    unlink( $testDir.'/'.$fileName );
                }
                rmdir($testDir );
                
            }
            
        } 
    }    
});

