<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Torchlight\Jigsaw;

use Illuminate\Support\Arr;
use TightenCo\Jigsaw\Jigsaw;
use Torchlight\Blade\BladeManager;
use Torchlight\Block;
use Torchlight\Torchlight;

class BlockManager
{
    /**
     * @var array
     */
    public $blocks = [];

    /**
     * @var Jigsaw
     */
    protected $jigsaw;

    /**
     * @param Block $block
     */
    public function add(Block $block)
    {
        $this->blocks[$block->id()] = $block;
    }

    public function render(Jigsaw $jigsaw)
    {
        $this->jigsaw = $jigsaw;

        // Merge all of the markdown blocks (the ones in this class)
        // with any potential blade directive blocks, so that we
        // can request them all from the API at once.
        $blocks = array_merge(
            array_values($this->blocks),
            array_values(BladeManager::getBlocks())
        );

        // Jigsaw sites can be huge, so we'll split the entirety
        // of the blocks into chunks of 50 since time is not
        // an issue when building locally.
        $chunks = array_chunk($blocks, 50);

        foreach ($chunks as $chunk) {
            Torchlight::highlight($chunk);
        }

        $this->renderMarkdownCapturedBlocks();
        $this->renderBladeDirectiveBlocks();
    }

    protected function renderMarkdownCapturedBlocks()
    {
        // Grep the directory to find files that have Torchlight blocks.
        // No sense reg-exing through files that don't have blocks.
        $files = $this->filesWithBlocks();

        foreach ($files as $file) {
            $contents = file_get_contents($file);

            // Gather up all of the <pre> elements.
            $elements = $this->getCapturedGroup("/\n(<pre(?:.+)<\/pre>)\n/", $contents, $all = true);

            foreach ($elements as $element) {
                // Grab the ID out of the placeholder so we can see if we have a block for it.
                $torchlightId = head(Torchlight::findTorchlightIds($element));

                /** @var Block $block */
                if (!$block = Arr::get($this->blocks, $torchlightId)) {
                    continue;
                }

                // ID and class are the only two attributes that the
                // markdown extension supports, so those are the
                // only ones we need to carry over.
                $id = $this->getCapturedGroup('/id="(.+?)"/', $element);
                $classes = $this->getCapturedGroup('/class="(.+?)"/', $element);

                $id = $id ? " id='$id'" : '';

                // User defined classes + Torchlight classes from the API.
                $classes = trim("$classes $block->classes");

                // Build up a new element.
                $html = "<pre><code{$id} class='{$classes}' style='{$block->styles}'>{$block->highlighted}</code></pre>";

                // Finally swap the old element for the new, highlighted one.
                $contents = str_replace($element, $html, $contents);
            }

            file_put_contents($file, $contents);
        }
    }

    protected function renderBladeDirectiveBlocks()
    {
        // Check again to see if there are any lingering `__torchlight-block-*`
        // strings in any of the files. If there are, we'll process them
        // through the Blade directive handler.
        $files = $this->filesWithBlocks();

        foreach ($files as $file) {
            file_put_contents(
                $file, BladeManager::renderContent(file_get_contents($file))
            );
        }
    }

    protected function getCapturedGroup($pattern, $contents, $all = false)
    {
        $method = $all ? 'preg_match_all' : 'preg_match';
        $method($pattern, $contents, $matches);

        // We only want the captured part.
        return Arr::get($matches, 1);
    }

    protected function filesWithBlocks()
    {
        // Recursively look for any blocks in the output
        // directory, returning only the paths.
        $output = explode(
            "\n", trim(shell_exec("grep -l -r __torchlight-block- {$this->jigsaw->getDestinationPath()}/*"))
        );

        // Filter out empty values caused by our grep query.
        return array_filter($output);
    }
}
