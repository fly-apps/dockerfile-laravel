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
                            {--no-assets : Skip compiling static assets}
                            {--force : Overwrite existing files}
                            {--skip : Keep existing files}
                            {--dev : Include dev dependencies like the local .env file}
                            {--path=. : Set the directory to check files in}
                            {--frankenphp-binary : Generate a single file binary of the app via frankenphp}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Generate a Dockerfile for a Laravel application';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // Determine the default answer on whether to overwrite existing file(s) based on the options.
        if ($this->option('force')) {
            $this->answer = 'a';
        } elseif ($this->option('skip')) {
            $this->answer = 'N';
        } else {
            $this->answer = '';
        }

        // Scanner
        $scan = (new \App\Services\Scanner());

        // Define the options available to the templates.
        $options = [
            'build_assets' => $scan->shouldBuildAssets( $this->options() ),
            'dev' => $this->option('dev'),
            'laravel_version' => $scan->laravelVersion( $this->options() ),
            'fly' => $scan->isForFly(),
            'octane' => $scan->octaneFlavor( $this->options() ),
            'filament' => $scan->filamentVersion( $this->options() ),
            'frankenphp_binary' => $this->option('frankenphp-binary')
        ];   


        // Define the list of templates to render.
        // The key is the template name, and the value is the output file name.
        $templates = $scan->templates( $options );

        // Render each template. If any fail, return a failure status.
        foreach ($templates as $template => $output) {
            $r = $this->writeTemplateFile($template, $options, $output);

            if ($r === false) {
                return Command::FAILURE;
            }
        }
       
        return Command::SUCCESS;
    }

    /**
     * Write a template file to the filesystem
     *
     * @param string $template
     * @param array $options
     * @param string $output
     * @return bool
     */
    public function writeTemplateFile($template, $options, $output) {
        // Read the file before the change. If it doesn't exist, assume it's empty.
        try {
            if (file_exists($output)) {
              $before = file($output, FILE_IGNORE_NEW_LINES);

                if ($before === false) {
                    $before = [];
                }
            } else {
                $before = [];
            }
        } catch (Exception $e) {
            $before = [];
        }

        // Render the template.
        // Truncate the last line if it's empty as the before is read with FILE_IGNORE_NEW_LINES.
        $result = explode("\n", (string)view($template, $options));
        if (end($result) === '' && end($before) !== '') {
            array_pop($result);
        }

        // Helper for file-related functionalities, i.e. file creation
        $fileHelper = (new \App\Services\File());

        // Write the file if it doesn't exist; if it has changed ask the user what to do.
        if (empty($before)) {
            $this->line('<fg=green>' . str_pad('create', 11, ' ', STR_PAD_BOTH) . '</> ' . $output);
            $fileHelper->createFile( $output, $result );
        } elseif ($before === $result) {
            $this->line('<fg=blue>' . str_pad('identical', 11, ' ', STR_PAD_BOTH) . '</> ' . $output);
        } elseif ($this->answer === 'N') {
            $this->line('<fg=blue>' . str_pad('skipped', 11, ' ', STR_PAD_BOTH) . '</> ' . $output);
        } else {
            if ($this->answer !== 'a') {
                $this->line('<fg=red>' . str_pad('conflict', 11, ' ', STR_PAD_BOTH) . '</> ' . $output);
            }

            while (true) {
                if ($this->answer !== 'a') {
                    $this->answer = readline('Overwrite ' . $output . '? (enter "h" for help) [Ynaqdh] ');
                    $this->answer = strtolower($this->answer);
                }

                if ($this->answer === 'y' || $this->answer === '' || $this->answer === 'a') {
                    $this->line('<fg=yellow>' . str_pad('forced', 11, ' ', STR_PAD_BOTH) . '</> ' . $output);
                    return $fileHelper->createFile($output, $result);
                } elseif ($this->answer === 'n') {
                    return true;
                } elseif ($this->answer === 'd') {
                    $diff = new \Diff($before, $result);
                    $renderer = new \Diff_Renderer_Text_Unified();
                    foreach (explode("\n", $diff->render($renderer)) as $line) {
                        if (str_starts_with($line, '---') || str_starts_with($line, '+++')) {
                            $this->line(line);
                        } elseif (str_starts_with($line, '@@')) {
                            $this->line('<fg=blue>' . $line . '</>');
                        } elseif (str_starts_with($line, '+')) {
                            $this->line('<fg=green>' . $line . '</>');
                        } elseif (str_starts_with($line, '-')) {
                            $this->line('<fg=red>' . $line . '</>');
                        } else {
                            $this->line($line);
                        }
                    }
                } elseif ($this->answer === 'q') {
                    exit();
                } else {
                    $this->line('  Y - yes, overwrite');
                    $this->line('  n - no, do not overwrite');
                    $this->line('  a - all, overwrite this and all others');
                    $this->line('  q - quit, abort');
                    $this->line('  d - diff, show the differences between the old and the new');
                    $this->line('  h - help, show this help');
                }
            }
        }
    }
}
