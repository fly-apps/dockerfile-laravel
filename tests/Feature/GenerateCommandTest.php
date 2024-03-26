<?php

it('generates the proper templates', function (  ) {

    // Test that supported combinations' templates are successfully generated
    $directories = \File::directories( 'tests/Feature/Supported' );   
    foreach($directories as $path) { 
        // TODO: Arrange options to pass to command, plus more combinations please

        // Run 
        $this->artisan('generate')->assertExitCode(0);

        // Assert comparison successfull 
        $expectedFiles = \File::files( $path );   
        foreach( $expectedFiles as $file ){
            
            // Relevant names
            $pathName = $file->getPathName();
            $fileName = $file->getFileName();   

            // Skip if composer
            if( $fileName == 'composer.json' ) continue;

            // First: assert file available
            $this->assertFileExists( $fileName );

            // Get contents
            $expected  = file_get_contents( $pathName );
            $generated = file_get_contents( $fileName );

            // Second: assert contents are the same
                // TODO: ignore different ARG VALUES
            $this->assertEquals( $expected, $generated, 
                'Comparison unsuccessful for: '.$fileName ); // Additional message to incl file name
        }
    } 

});
