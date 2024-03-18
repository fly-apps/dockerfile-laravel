<?php
namespace App\Services;

use Illuminate\Support\Facades\Process;


class Scanner
{
    /**
     * This service scans configuration values for a Laravel project
     */

     /**
      * Scan for the Laravel version
      */
    public function laravelVersion()
    {
        // From the composer.json
        if( file_exists('composer.json') ){
            $composer = json_decode( file_get_contents('composer.json'), 1);
            if( isset($composer['require']) && isset($composer['require']['laravel/framework']) ){
                return trim( $composer['require']['laravel/framework'], '^');
            }
        }

        // From artisan command
        $run = Process::run( 'php artisan --version' );
        $version = $run->output();
        $version = explode('Laravel Framework', $version);
        if( count($version) >1 ){
            return trim($version[1]);
        }

        // Default Latest Version
        return  "11.0.0";
    }
}