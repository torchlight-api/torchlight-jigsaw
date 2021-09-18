<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Torchlight\Jigsaw\Tests;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use TightenCo\Jigsaw\Console\BuildCommand;
use TightenCo\Jigsaw\Jigsaw;
use Torchlight\Jigsaw\Exceptions\UnrenderedBlockException;
use Torchlight\Jigsaw\TorchlightExtension;
use Torchlight\Torchlight;

class BaseTest extends TestCase
{
    protected $app;

    protected $container;

    protected function prepareForBuilding()
    {
        $sitePath = __DIR__ . '/Site';

        // Clear out the old build directory.
        if (is_dir("$sitePath/build_testing")) {
            exec("rm -rf $sitePath/build_testing");
        }

        // Clear the old config.
        if (file_exists("$sitePath/torchlight.php")) {
            exec("rm $sitePath/torchlight.php");
        }

        /**
         * These next lines basically mimic the /vendor/bin/jigsaw.php file.
         */
        require realpath(__DIR__ . '/../vendor/tightenco/jigsaw/jigsaw-core.php');

        $this->app = new Application('Jigsaw', '1.3.37');

        /** @var Container $container */
        $this->container = $container;

        $this->container->instance('cwd', $sitePath);
        $this->container->buildPath = [
            'source' => $sitePath,
            'views' => $sitePath,
            'destination' => "$sitePath/build_testing",
        ];

        // There are other Jigsaw commands we could register,
        // but we dont' need them so we don't add them.
        $this->app->add(new BuildCommand($this->container));
        Jigsaw::addUserCommands($this->app, $this->container);

        // This is from the bottom of jigsaw-core.php. We have to do it
        // ourselves since we're in a different working directory than
        // that file expects us to be.
        $events = $this->container->events;

        include "$sitePath/bootstrap.php";
    }

    protected function build($source = 'source-1')
    {
        // Turn off the Jigsaw progress bars.
        $this->container->consoleOutput->setup($verbosity = -1);
        $jigsaw = $this->container->make(Jigsaw::class);
        $jigsaw->setSourcePath(__DIR__ . "/Site/$source");
        $jigsaw->build('testing');
    }

    protected function assertSnapshotMatches($file)
    {
        $expected = file_get_contents(__DIR__ . "/snapshots/$file.html");
        $actual = file_get_contents(__DIR__ . "/Site/build_testing/$file.html");

        $this->assertEquals($expected, $actual);
    }

    /** @test */
    public function most_of_the_tests_are_here()
    {
        TorchlightExtension::macro('afterStandaloneConfiguration', function () {
            Torchlight::getConfigUsing([
                'blade_components' => true,
                'token' => 'token'
            ]);
        });

        $this->prepareForBuilding();

        Http::fake();

        $this->build();

        Http::assertSentCount(1);

        Http::assertSent(function ($request) {
            $blocks = $request['blocks'];
            foreach ($blocks as $block) {
                if ($block['language'] === 'json' && $block['theme'] === 'github-light') {
                    return true;
                }
            }

            return false;
        });

        $this->assertSnapshotMatches('can-set-a-theme');
        $this->assertSnapshotMatches('attributes-get-carried-over');
        $this->assertSnapshotMatches('jigsaw-escapes-get-fixed');
        $this->assertSnapshotMatches('one-component');
        $this->assertSnapshotMatches('two-components');
        $this->assertSnapshotMatches('code-indents-work');
    }

    /** @test */
    public function no_blocks_doesnt_create_an_error()
    {
        TorchlightExtension::macro('afterStandaloneConfiguration', function () {
            Torchlight::getConfigUsing([
                'blade_components' => true,
                'token' => 'token'
            ]);
        });

        $this->prepareForBuilding();
        $this->build('source-2');

        // Before the fix, building source-2 would throw an
        // exception, so we just need to make sure we got here.
        $this->assertTrue(true);
    }

    /** @test */
    public function non_existent_block_throws()
    {
        TorchlightExtension::macro('afterStandaloneConfiguration', function () {
            Torchlight::getConfigUsing([
                'blade_components' => true,
                'token' => 'token'
            ]);
        });

        $this->prepareForBuilding();

        try {
            $this->build('source-3');
        } catch (UnrenderedBlockException $e) {
            return $this->assertTrue(true);
        }

        $this->assertTrue(false);
    }

    /** @test */
    public function expected_non_existent_block_is_fine()
    {
        TorchlightExtension::macro('afterStandaloneConfiguration', function () {
            Torchlight::getConfigUsing([
                'blade_components' => true,
                'token' => 'token',
                'ignore_leftover_ids' => [
                    'fake_id'
                ]
            ]);
        });

        $this->prepareForBuilding();

        $this->build('source-3');
        $this->assertTrue(true);
    }


    /** @test */
    public function test_publish_command()
    {
        $this->assertFalse(file_exists(__DIR__ . '/Site/torchlight.php'));

        exec('cd ' . __DIR__ . '/Site && ./vendor/bin/jigsaw torchlight:install');

        $this->assertTrue(file_exists(__DIR__ . '/Site/torchlight.php'));
    }
}
