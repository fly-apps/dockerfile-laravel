<?php
namespace App\Services;

class File
{

    /**
     * There are some files that need exact content copies, don't add new lines for this at end
     */
    public function exactCopiesOnlyFiles()
    {
        return ['.fly/php/ondrej_ubuntu_php.gpg'];
    }

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
        if( in_array( $output, $this->exactCopiesOnlyFiles()) )
            return file_put_contents($output, implode("\n", $result));
        else
            return file_put_contents($output, implode("\n", $result) . "\n");
    }

    /**
     * Check if package.json exists in the directory
     * 
     * @param string $directory - string used as directory where the package.json file is scanned in 
     */
    public function packageJsonExists( string $directory )
    {
        $path = $directory.'/package.json';

        if( file_exists( $path ) ) 
            return true;
        else{
            return false;
        }
    }

    /**
     * Get array of key value pairs from a composer.json file
     * 
     * @param string $directory - string used as directory where the composer.json file is scanned in 
     */
    public function composerJsonContent( string $directory )
    {
        $path = $directory.'/composer.json';

        if( !file_exists( $path ) ) return [];
        
        return json_decode( file_get_contents( $path ), 1 );
    }

    public function deleteDir( $dir )
    {
        // First level delete for now
        if( is_dir($dir) ){
            $fileNames = scandir( $dir );
            foreach( $fileNames as $fileName ){
                $filePath =  $dir.'/'.$fileName;
                if( is_file($filePath) )
                    unlink( $filePath);
                else if( is_dir($filePath) && $fileName != '.' && $fileName != '..' )
                    $this->deleteDir( $filePath );
            }
            rmdir( $dir );
        }
      
    }
}