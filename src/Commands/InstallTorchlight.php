<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Torchlight\Jigsaw\Commands;


use Illuminate\Contracts\Container\Container;
use TightenCo\Jigsaw\Console\Command;
use TightenCo\Jigsaw\Console\ConsoleOutput;

class InstallTorchlight extends Command
{

    /**
     * @var Container
     */
    protected $app;

    /**
     * @var ConsoleOutput
     */
    protected $consoleOutput;

    public function __construct(Container $app)
    {
        $this->app = $app;
        $this->consoleOutput = $app->consoleOutput;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('torchlight:install')
            ->setDescription('Install the Torchlight Config file into your application.');
    }

    protected function fire()
    {
        file_put_contents($this->app->get('cwd') . DIRECTORY_SEPARATOR . 'torchlight.php', $this->stub());

        $this->consoleOutput->writeln('Torchlight config file published!');
    }

    protected function stub()
    {
        return <<<EOT
<?php

return [
    // Which theme you want to use. You can find all of the themes at
    // https://torchlight.dev/themes, or you can provide your own.
    'theme' => 'material-theme-palenight',

    // Your API token from torchlight.dev.
    'token' => '',

    // If you want to register the blade directives, set this to true.
    'blade_components' => true,

    // The Host of the API.
    'host' => 'https://api.torchlight.dev',

    // If you want to specify the cache path, you can do so here. Note
    // that you should *not* use the same path that Jigsaw uses,
    // which is `cache` at the root level of your app.
    'cache_path' => 'torchlight_cache',
];
EOT;

    }
}