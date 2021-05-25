<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Torchlight\Jigsaw\Tests;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use TightenCo\Jigsaw\Console\BuildCommand;
use TightenCo\Jigsaw\Jigsaw;

class BaseTest extends TestCase
{
    protected $app;

    protected $container;

    public function setUp(): void
    {
        parent::setUp();

        $this->prepareForBuilding();
    }

    protected function prepareForBuilding()
    {
        $sitePath = __DIR__ . '/Site';

        // Clear out the old build directory
        exec("rm -rf $sitePath/build_testing");

        /**
         * These next lines basically mimic the /vendor/bin/jigsaw.php file.
         */
        require_once(realpath(__DIR__ . '/../vendor/tightenco/jigsaw/jigsaw-core.php'));

        $this->app = new Application('Jigsaw', '1.3.37');

        /** @var Container $container */
        $this->container = $container;

        $this->container->instance('cwd', $sitePath);
        $this->container->buildPath = [
            'source' => "$sitePath/source",
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

    protected function build()
    {
        // Turn off the Jigsaw progress bars.
        $this->container->consoleOutput->setup($verbosity = -1);
        $this->container->make(Jigsaw::class)->build('testing');
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
        Http::fake();

        $this->build();

        Http::assertSentCount(1);

        $this->assertSnapshotMatches('attributes-get-carried-over');
        $this->assertSnapshotMatches('jigsaw-escapes-get-fixed');
        $this->assertSnapshotMatches('non-existent-id');
        $this->assertSnapshotMatches('one-component');
        $this->assertSnapshotMatches('two-components');
    }
}