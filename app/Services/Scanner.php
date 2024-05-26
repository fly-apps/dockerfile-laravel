<?php
namespace App\Services;

use Illuminate\Support\Facades\Process;

/**
* This service scans configuration values for a Laravel project
*/
class Scanner
{
    /**
    * Scan for the Laravel version
    */
    public function laravelVersion( $options )
    {
        // From detection
        $run = Process::run( 'php artisan --version' );
        $version = $run->output();
        $version = explode('Laravel Framework', $version);
        if( count($version) >1 ){
            // From artisan command
            return trim($version[1]);
        }else{
            // From composer.json
            $composerContent = (new \App\Services\File())->composerJsonContent( $options['path'] );
            if( isset($composerContent['require']) && isset($composerContent['require']['laravel/framework']) ){
                return trim($composerContent['require']['laravel/framework'],'^');
            }
        }
        
        // Default Latest Version
        return  "11.0.0";
    }

    /**
     * Detect octane setup and flavor 
     */
    public function octaneFlavor( array $options )
    {
        $composerContent = (new \App\Services\File())->composerJsonContent( $options['path'] );
        $octane = false;

        // Detect octane from composer.json
        if( isset($composerContent['require']) && isset( $composerContent['require']['laravel/octane'] ) ){
    
            // Determine flavor
            if( file_exists( $options['path'].'/frankenphp') ){
                return 'frankenphp';
            }else if(  file_exists(  $options['path'].'/rr' ) && file_exists(  $options['path'].'/.rr.yaml') ){
                return 'roadrunner';
            }else{
                return 'swoole';
            }
        }
        return $options['octane'];
    }

    /**
     * Detect filament declaration and version
     */
    public function filamentVersion( array $options )
    {
        // Composer json contains filament requirement
        $composerContent = (new \App\Services\File())->composerJsonContent( $options['path'] );

        // Extract version
        if( isset( $composerContent['require'] ) && isset($composerContent['require']['filament/filament']) ){
            return trim($composerContent['require']['filament/filament'], '^'); 
        }else{
            return false;
        }
    }

    /**
     * Scan directory and check if applicable for Fly.io deployment
     */
    public function isForFly()
    {
        if ( file_exists('fly.toml') ) 
            return true;
        else
            return false;
    }

    /**
     * Determines whether to build assets or not
     */
    public function shouldBuildAssets( array $options )
    {
        $shouldBuild = !$options['no-assets'];
        $packageJsonExists = (new \App\Services\File())->packageJsonExists( $options['path'] );

        if( $shouldBuild && $packageJsonExists ) {
            // If want to build assets, make sure package.json exists
            return true;
        }else{
            // Otherwise don't build
            return false;
        }
    }


    /**
     * Lists templates to generate based on options passed
     * 
     * @param array $options
     * @return array
     *      key is template name, and the value is the output file name.
     */
    public function templates( array $options )
    {
        // Define the list of templates to render.
        // The key is the template name, and the value is the output file name.
        $templates = [
            'dockerfile' => 'Dockerfile',
        ];

        // Scan for fly.io mark
        if ( $options['fly'] ) {
            $templates[ 'fly.dockerignore' ] = '.dockerignore';
            $templates[ 'fly.entrypoint']    = '.fly/entrypoint.sh';
            $templates[ 'fly.scripts.caches'] = '.fly/scripts/caches.sh';
        }

        return $templates;
    }

    
}