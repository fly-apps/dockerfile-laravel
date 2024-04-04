<?php

function ignoreFiles( )
{
    return ['composer.json','frankenphp','rr','.rr.yaml'];
}

function getTestOptions( string $directory ): string 
{
    $composerContent = (new \App\Services\File())->composerJsonContent( $directory );
    $composerConfig  = $composerContent['require'];
    
    // Matches with options for in App\Commands\GenerateCommand::generate() command
    $optionsToCheck = [ 
        'laravel/framework' => 'laravel-version',
    ];

    // Gather options
    $optionsFound = '';
    foreach( $optionsToCheck as $key => $option ){
        if( isset($composerConfig[$key]) ){
            $optionsFound .= '--'.$option.'="'.$composerConfig[$key].'" ';
        }
    }
    
    // Set directory to check files in 
    $optionsFound .= '--path="'.$directory.'"';

    return $optionsFound;
}

// Test that supported combinations' templates are successfully generated
it('generates proper templates for each supported combination', function ( ) 
{
    $directories = \File::directories( 'tests/Feature/Supported' );   
    foreach($directories as $dir) {
        #if( $dir != 'tests/Feature/Supported/10_filament_v3' ) continue; -- revise and uncomment this line if you want to test out a specific Support subfolder

        // Detect options from composer.json
        $options = getTestOptions( $dir );

        // Generate Dockerfile using options
        // FIRST assert: command successfully runs and exits
        $this->artisan('generate '.$options)->assertExitCode(0);

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

