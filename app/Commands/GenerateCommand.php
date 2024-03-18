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
                            {--force : overwrite existing files}
                            {--skip : keep existing files}';

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
        // determine the default answer based on the options
        if ($this->option('force')) {
            $this->answer = 'a';
        } elseif ($this->option('skip')) {
            $this->answer = 'N';
        } else {
            $this->answer = '';
        }

        // define the options available to the templates
        $options = [
            'octane' => $this->option('octane'),
            'build_assets' => ! $this->option('no-assets')
        ];

        // define the list of templates to render
        $templates = [
            'dockerfile' => 'Dockerfile',
        ];

        // ... add additional templates here, possibly based on scanning the source ...

        // render each template
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
        // read the file before the change
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

        // render the template
        $result = explode("\n", (string)view($template, $options));
        if (end($result) === '' && end($before) !== '') {
            array_pop($result);
        }

        // write the file if it doesn't exist; if it has changed ask the user what to do
        if (empty($before)) {
            echo $this->green(str_pad('create', 11, ' ', STR_PAD_BOTH)) . ' ' . $output . PHP_EOL;
            file_put_contents($output, implode("\n", $result) . "\n");
        } elseif ($before === $result) {
            echo $this->blue(str_pad('identical', 11, ' ', STR_PAD_BOTH)) . ' ' . $output . PHP_EOL;
        } elseif ($this->answer === 'N') {
            echo $this->blue(str_pad('skipped', 11, ' ', STR_PAD_BOTH)) . ' ' . $output . PHP_EOL;
        } else {
            if ($this->answer !== 'a') {
                echo $this->red(str_pad('conflict', 11, ' ', STR_PAD_BOTH)) . ' ' . $output . PHP_EOL;
            }

            while (true) {
                if ($this->answer !== 'a') {
                    $this->answer = readline('Overwrite ' . $output . '? (enter "h" for help) [Ynaqdh] ');
                    $this->answer = strtolower($this->answer);
                }

                if ($this->answer === 'y' || $this->answer === '' || $this->answer === 'a') {
                    echo $this->yellow(str_pad('forced', 11, ' ', STR_PAD_BOTH)) . ' ' . $output . PHP_EOL;
                    return file_put_contents($output, implode("\n", $result) . "\n");
                } elseif ($this->answer === 'n') {
                    return true;
                } elseif ($this->answer === 'd') {
                    $diff = new \Diff($before, $result);
                    $renderer = new \Diff_Renderer_Text_Unified();
                    foreach (explode("\n", $diff->render($renderer)) as $line) {
                        if (str_starts_with($line, '---') || str_starts_with($line, '+++')) {
                            echo line . PHP_EOL;
                        } elseif (str_starts_with($line, '@@')) {
                            echo $this->blue($line) . PHP_EOL;
                        } elseif (str_starts_with($line, '+')) {
                            echo $this->green($line) . PHP_EOL;
                        } elseif (str_starts_with($line, '-')) {
                            echo $this->red($line) . PHP_EOL;
                        } else {
                            echo $line . PHP_EOL;
                        }
                    }
                } elseif ($this->answer === 'q') {
                    exit();
                } else {
                    echo '  Y - yes, overwrite' . PHP_EOL;
                    echo '  n - no, do not overwrite' . PHP_EOL;
                    echo '  a - all, overwrite this and all others' . PHP_EOL;
                    echo '  q - quit, abort' . PHP_EOL;
                    echo '  d - diff, show the differences between the old and the new' . PHP_EOL;
                    echo '  h - help, show this help' . PHP_EOL;
                }
            }
        }
    }

    // Define the ANSI color functions
    public function red($text) {
        return "\033[31m{$text}\033[0m";
    }

    private function green($text) {
        return "\033[32m{$text}\033[0m";
    }

    private function blue($text) {
        return "\033[34m{$text}\033[0m";
    }

    private function yellow($text) {
        return "\033[33m{$text}\033[0m";
    }
}
