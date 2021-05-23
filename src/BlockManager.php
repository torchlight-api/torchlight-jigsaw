<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Torchlight\Jigsaw;


use Illuminate\Support\Arr;
use TightenCo\Jigsaw\Jigsaw;
use Torchlight\Block;
use Torchlight\Torchlight;

class BlockManager
{
    public $blocks = [];

    public function add(Block $block)
    {
        $this->blocks[$block->id()] = $block;
    }

    public function render(Jigsaw $jigsaw)
    {
        // Send the request to the API.
        Torchlight::highlight(array_values($this->blocks));

        // Grep the directory to find files that have Torchlight blocks.
        // No sense reg-exing through files that don't have blocks.
        $files = $this->filesWithBlocks($jigsaw);

        foreach ($files as $file) {
            $contents = file_get_contents($file);

            // Gather up all of the <pre> elements.
            $elements = $this->getCapturedGroup("/\n(<pre(?:.+)<\/pre>)\n/", $contents, $all = true);

            foreach ($elements as $element) {
                // Grab the ID out of the placeholder so we can see if we have a block for it.
                $torchlightId = $this->getCapturedGroup("/__torchlight-block-(.+)__/", $element);

                if (!$block = Arr::get($this->blocks, $torchlightId)) {
                    continue;
                }

                // ID and class are the only two attributes that the
                // markdown extension supports, so those are the
                // only ones we need to carry over.
                $id = $this->getCapturedGroup("/id=\"(.+?)\"/", $element);
                $classes = $this->getCapturedGroup("/class=\"(.+?)\"/", $element);

                $id = $id ? "id='$id'" : '';
                // User defined classes + Torchlight classes from the API.
                $classes = trim("$classes $block->classes");

                // Build up a new element.
                $html = "<pre><code $id class='{$classes}' style='{$block->styles}'>{$block->highlighted}</code></pre>";

                // Finally swap the old element for the new, highlighted one.
                $contents = str_replace($element, $html, $contents);
            }

            file_put_contents($file, $contents);
        }
    }

    protected function getCapturedGroup($pattern, $contents, $all = false)
    {
        $method = $all ? 'preg_match_all' : 'preg_match';
        $method($pattern, $contents, $matches);

        // We only want the captured part.
        return Arr::get($matches, 1);
    }

    protected function filesWithBlocks($jigsaw)
    {
        // Recursively look for any blocks in the output
        // directory, returning only the paths.
        return explode(
            "\n", trim(shell_exec("grep -l -r __torchlight-block- {$jigsaw->getDestinationPath()}/*"))
        );
    }
}