<?php
function getTestOptions( string $directory ): string 
{
    $composerFile    = $directory.'/composer.json';
    $composerContent = json_decode( file_get_contents( $composerFile ), 1 );
    $composerConfig  = $composerContent['require'];
    
    // Matches with options for in App\Commands\GenerateCommand::generate() command
    $optionsToCheck = [ 
        'laravel/framework' => 'laravel-version'
    ];

    // Gather options
    $optionsFound = '';
    foreach( $optionsToCheck as $key => $option ){
        if( isset($composerConfig[$key]) ){
            $optionsFound .= '--'.$option.'="'.$composerConfig[$key].'" ';
        }
    }
    return $optionsFound;
}

// Test that supported combinations' templates are successfully generated
it('generates the proper templates', function ( ) {

    $directories = \File::directories( 'tests/Feature/Supported' );   
    foreach($directories as $path) { 

        // Detect options from composer.json
        $options = getTestOptions( $path );

        // Generate Dockerfile using options
        // First assert: successfully runs
        $this->artisan('generate '.$options)->assertExitCode(0);

        // Compare expected files with generated files
        $expectedFiles = \File::files( $path );   
        foreach( $expectedFiles as $file ){ 

            if( $file->getFileName() == 'composer.json' ) continue;

            // Second assert: expected file was generated
            $this->assertFileExists( $file->getFileName() );

            // Get contents
            $expected  = file_get_contents( $file->getPathName() ); // expected file full path
            $generated = file_get_contents( $file->getFileName() ); // gen file is expected file's name in current dir

            // Clean UP: Delete generated file, no longer needed
            unlink( $file->getFileName() );

            // Third assert: contents are the same
                // TODO: ignore different ARG VALUES
            $this->assertEquals( $expected, $generated, 
                'Comparison unsuccessful for: "'.$file->getPathName() .'"'); 
        }
    }    
});

