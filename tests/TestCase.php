<?php

abstract class TestCase extends Orchestra\Testbench\TestCase
{
    protected $consoleOutput;

    protected function getPackageProviders($app)
    {
        return [\Themsaid\Langman\LangmanServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('langman.path', __DIR__.'/temp');
        $app['config']->set('view.paths', [__DIR__.'/views_temp']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        exec('rm -rf '.__DIR__.'/temp/*');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        exec('rm -rf '.__DIR__.'/temp/*');

        $this->consoleOutput = '';
    }

    public function createTempFiles($files = [])
    {
        foreach ($files as $dir => $dirFiles) {
            mkdir(__DIR__.'/temp/'.$dir);

            foreach ($dirFiles as $file => $content) {
                if (is_array($content)) {
                    mkdir(__DIR__.'/temp/'.$dir.'/'.$file);

                    foreach ($content as $subDir => $subContent) {
                        mkdir(__DIR__.'/temp/vendor/'.$file.'/'.$subDir);
                        foreach ($subContent as $subFile => $subsubContent) {
                            file_put_contents(__DIR__.'/temp/'.$dir.'/'.$file.'/'.$subDir.'/'.$subFile.'.php', $subsubContent);
                        }
                    }
                } else {
                    file_put_contents(__DIR__.'/temp/'.$dir.'/'.$file.'.php', $content);
                }
            }
        }
    }

    public function resolveApplicationConsoleKernel($app)
    {
        // Updated to use dependency injection properly in Laravel 10
        $app->singleton('artisan', function ($app) {
            return new \Illuminate\Console\Application(
                $app,
                $app->make('events'),
                $app->version()
            );
        });

        $app->singleton(\Illuminate\Contracts\Console\Kernel::class, Kernel::class);
    }

    /**
     * Run an Artisan console command by name.
     *
     * @param string $command
     * @param array $parameters
     * @return int
     */
    public function artisan($command, $parameters = [])
    {
        $this->withoutMockingConsoleOutput();
        // Call the parent method with merged parameters
        return parent::artisan($command, array_merge($parameters, ['--no-interaction' => true]));
    }

    public function consoleOutput()
    {
        return $this->consoleOutput ?: $this->consoleOutput = $this->app->make(Kernel::class)->output();
    }
}
