<?php
use PHPUnit\Framework\ExpectationFailedException;

function ignoreFiles( )
{
    return ['composer.json','frankenphp','rr','.rr.yaml'];
}

function assertEqualsIgnoreNewLine( $ins, $expected, $generated, $msg )
{
    try{
        // Ignore newlines when comparing strings; do this by removing new lines from strings to compare
        $il_expected  = str_replace( PHP_EOL, '', $expected  );
        $il_generated = str_replace( PHP_EOL, '', $generated  );
        $ins->assertEquals( $il_expected, $il_generated, $msg); 
    }catch (ExpectationFailedException $f) {

        // However, if variants without newlines failed to match! 
        // That's a true failed match!
        // To get a better diff error message, use original strings( with their newlines intact ) in comparison
        $ins->assertEquals( $expected, $generated, $msg);         
    }
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
        // First assert: command successfully runs and exits
        $this->artisan('generate '.$options)->assertExitCode(0);

        // Compare expected files from test directory with generated files
        $referenceFiles = \File::files( $dir );   
        foreach( $referenceFiles as $reference ){ 
            $failedForMsg = 'Failed for: "'.$reference->getPathName().'"';

            if( in_array( $reference->getFileName(), ignoreFiles())  ) continue;

            // Second assert: a new file with the reference file's name was created-it should exist!
            $this->assertFileExists( $reference->getFileName(), $failedForMsg );

            // Get contents i.e. /10_base/Dockerfile vs Dockerfile
            $expected  = file_get_contents( $reference->getPathName() ); // expected content from reference file
            $generated = file_get_contents( $reference->getFileName() ); // new file content

            // Third assert: contents are the same
                // TODO: ignore different ARG VALUES
            assertEqualsIgnoreNewLine( $this, $expected, $generated, $failedForMsg); 

            // Clean UP: Delete generated file, no longer needed
            unlink( $reference->getFileName() );

        }
    }    
});

