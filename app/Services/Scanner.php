<?php
namespace App\Services;

use Illuminate\Support\Facades\Process;

/**
* This service scans configuration values for a Laravel project
*/

class Scanner
{

    /**
     * Scans for templates to generate
     * 
     * @return array
     *      key is template name, and the value is the output file name.
     */
    public function templates()
    {
        // Define the list of templates to render.
        // The key is the template name, and the value is the output file name.
        $templates = [
            'dockerfile' => 'Dockerfile',
        ];

        // Scan for fly.io mark
        if ( file_exists('fly.toml') ) {
            $templates[ 'fly.dockerignore' ] = '.dockerignore';
            $templates[ 'fly.entrypoint']    = '.fly/entrypoint.sh';
            $templates[ 'fly.scripts.caches'] = '.fly/scripts/caches.sh';
        }

        return $templates;
    }


    
    
}