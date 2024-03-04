<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class GenerateCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'generate
                            {--o|octane= : If using Octane, provide which flavor - one of: roadrunner, swoole, frankenphp}
                            {--no-assets : Skip compiling static assets}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Generate a Dockerfile';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        file_put_contents('Dockerfile', view('dockerfile', [
            'octane' => $this->option('octane'),
            'build_assets' => ! $this->option('no-assets')
        ]));
    }
}
