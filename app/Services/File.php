<?php
namespace App\Services;

class File
{
    /**
     * Generate a file with content
     * 
     * @param string output - the path to the new file
     * @param string result - the content of the new file
     */
    public function createFile( $output, $result )
    {
    
        // Create the directory the file is supposed to be located in 
        if( strpos($output, '/') ){
            // Get path to file
            $pathToFile = dirname($output);
          
            // Recursively create directories
            if( !file_exists($pathToFile) )
                mkdir( $pathToFile, 0755, true );

        }
       
        // Create the file, finally!
        return file_put_contents($output, implode("\n", $result) . "\n");
    }

    public function composerJsonContent( $directory )
    {
        $path = $directory.'/composer.json';

        if( !file_exists( $path ) ) return [];
        
        return json_decode( file_get_contents( $path ), 1 );
    }
}