<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Torchlight\Jigsaw;

use Illuminate\Cache\FileStore;
use Illuminate\Cache\Repository;
use Illuminate\Container\Container;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Facade;
use TightenCo\Jigsaw\Events\EventBus;
use TightenCo\Jigsaw\File\Filesystem;
use Torchlight\Blade\CodeComponent;
use Torchlight\Block;
use Torchlight\Torchlight;
use Torchlight\TorchlightServiceProvider;

class TorchlightExtension
{
    public $container;

    public $events;

    public $blocks;

    public $config = [];

    /**
     * @param Container $container
     * @param EventBus $events
     * @param null $config
     * @return static
     */
    public static function make(Container $container, EventBus $events, $config = null)
    {
        return new static($container, $events, $config);
    }

    /**
     * @param Container $container
     * @param EventBus $events
     * @param null|array $config
     */
    public function __construct(Container $container, EventBus $events, $config = null)
    {
        $this->container = $container;
        $this->events = $events;
        $this->blocks = new BlockManager;
        $this->config = $config ?? (array)$this->getConfigurationFromFile();
    }

    public function boot()
    {
        $this->configureStandaloneTorchlight();
        $this->hookIntoMarkdownParser();
        $this->registerFinalRenderFunction();
        $this->registerBladeComponent();
    }

    protected function configureStandaloneTorchlight()
    {
        // Set the root if it doesn't exist yet.
        if (!Facade::getFacadeApplication()) {
            Facade::setFacadeApplication($this->container);
        }

        // Bind our Manager class, as this is what
        // the Torchlight Facade references.
        $provider = new TorchlightServiceProvider($this->container);
        $provider->bindManagerSingleton();

        // There is no `config` helper, so we bind in a callback
        // that references the configuration on this class.
        Torchlight::getConfigUsing(function ($key, $default) {
            return Arr::get($this->config, $key, $default);
        });

        // Set an instantiated cache instance.
        Torchlight::setCacheInstance($this->makeFileCache());
    }

    /**
     * Jigsaw uses https://github.com/michelf/php-markdown to parse markdown.
     * It exposes a callback function for syntax highlighting, which is how
     * we hook into the build process to capture the code blocks.
     */
    protected function hookIntoMarkdownParser()
    {
        $this->container['markdownParser']->code_block_content_func = function ($code, $language) {
            // We have to undo the escaping that the Jigsaw Markdown handler does.
            // See MarkdownHandler->getEscapedMarkdownContent.
            $code = strtr($code, [
                "<{{'?php'}}" => '<?php',
                "{{'@'}}" => '@',
                '@{{' => '{{',
                '@{!!' => '{!!',
            ]);

            $block = Block::make()->code($code)->language($language);

            // Add it to our tracker.
            $this->blocks->add($block);

            // Leave our placeholder for replacing later.
            return $block->placeholder();
        };
    }

    protected function registerFinalRenderFunction()
    {
        // Once everything is done, we need to put the fully
        // highlighted code back in place.
        $this->events->afterBuild(function ($jigsaw) {
            $this->blocks->render($jigsaw);
        });
    }

    protected function registerBladeComponent()
    {
        $this->container['bladeCompiler']->component(CodeComponent::class, 'torchlight-code');
    }

    /**
     * @return array
     */
    protected function getConfigurationFromFile()
    {
        $path = $this->container['cwd'] . DIRECTORY_SEPARATOR . 'torchlight.php';

        return file_exists($path) ? include $path : [];
    }

    /**
     * @return Repository
     */
    protected function makeFileCache()
    {
        // Build a Laravel-standard Cache store so we don't
        // have to send API requests every single time.
        return new Repository(
            new FileStore(new Filesystem, $this->cachePath())
        );
    }

    /**
     * @return string
     */
    protected function cachePath()
    {
        // Can't put it in the Jigsaw cache directory, because
        // Jigsaw deletes that from time to time.
        return $this->container['cwd'] . DIRECTORY_SEPARATOR . Torchlight::config('cache_path', 'torchlight_cache');
    }
}
