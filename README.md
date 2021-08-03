# Torchlight Client for Jigsaw

[![Tests](https://github.com/torchlight-api/torchlight-jigsaw/actions/workflows/tests.yml/badge.svg)](https://github.com/torchlight-api/torchlight-jigsaw/actions/workflows/tests.yml) [![Latest Stable Version](https://poser.pugx.org/torchlight/torchlight-jigsaw/v)](//packagist.org/packages/torchlight/torchlight-jigsaw) [![Total Downloads](https://poser.pugx.org/torchlight/torchlight-jigsaw/downloads)](//packagist.org/packages/torchlight/torchlight-jigsaw)  [![License](https://poser.pugx.org/torchlight/torchlight-jigsaw/license)](//packagist.org/packages/torchlight/torchlight-jigsaw)

A [Torchlight](https://torchlight.dev) syntax highlighting extension for the static site builder [Jigsaw](https://jigsaw.tighten.co/).

Torchlight is a VS Code-compatible syntax highlighter that requires no JavaScript, supports every language, every VS Code theme, line highlighting, git diffing, and more.

## Installation

To install, require the package from composer:

```
composer require torchlight/torchlight-jigsaw
```

After the package is downloaded, add the following line to your `bootstrap.php`

```php
Torchlight\Jigsaw\TorchlightExtension::make($container, $events)->boot();
```

This will boot the extension so that it can register its bindings, events, and commands.

Now your `bootstrap.php` might look something like this:

```php
<?php

use App\Listeners\GenerateSitemap;
use TightenCo\Jigsaw\Jigsaw;

/** @var $container \Illuminate\Container\Container */
/** @var $events \TightenCo\Jigsaw\Events\EventBus */

/**
 * You can run custom code at different stages of the build process by
 * listening to the 'beforeBuild', 'afterCollections', and 'afterBuild' events.
 *
 * For example:
 *
 * $events->beforeBuild(function (Jigsaw $jigsaw) {
 *     // Your code here
 * });
 */

$events->afterBuild(GenerateSitemap::class);

Torchlight\Jigsaw\TorchlightExtension::make($container, $events)->boot();
```

## Configuration

To configure your Torchlight integration, you can start by publishing the configuration file:

```
./vendor/bin/jigsaw torchlight:install
```

Once run, you should see a new file `torchlight.php` in the root of your project, with contents that look like this:

```php
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
```

### Theme

You can change the theme of all your code blocks by adjusting the `theme` key in your configuration.

### Token

This is your API token from [torchlight.dev](https://torchlight.dev). (Torchlight is completely free for personal and open source projects.)

### Blade Components

By default Torchlight works with both Markdown files as well as Blade files, using a [custom Laravel component](https://laravel.com/docs/master/blade#components). If you'd like to disable the registration of the component for whatever reason, you can turn this to false.

### Host

You can change the host where your API requests are sent. Not sure why you'd ever want to do that, but you can!

### Cache

Torchlight requires a separate cache path, distinct from the Jigsaw cache. Jigsaw cleans out its cache from time to time, whereas Torchlight depends on individual TTLs, courtesy of the Laravel cache driver.

> You may want to add your configured cache path (`/torchlight_cache/`) to your `.gitignore` file so the cache files aren't persisted to your git history.


## Usage

### Markdown
To use Torchlight in your Jigsaw markdown files, you don't need to do anything else beside using fenced code blocks like you have been.

~~~markdown
This is my great markdown file! I'm going to show some code now:

```php
echo "here is my code"
```

Wasn't that good code?
~~~

Torchlight will handle highlighting that block of code. 

If you want to add additional classes or an ID, you can use the syntax that is supported by Jigsaw's [underlying markdown parser](https://github.com/michelf/php-markdown).

~~~markdown
This is my great markdown file! I'm going to show some code now:

```php {#some-html-id.mt-4.mb-8}
echo "here is my code"
```

Wasn't that good code?
~~~

The resulting `code` element will have an id of `some-html-id` and classes of `mt-4 mb-8`, along with any classes that Torchlight applies.

### Blade

If you want to use Torchlight in your `.blade.php` files, you can use the custom blade component `x-torchlight-code`.

```blade
<pre><x-torchlight-code language='php'>
    echo "hello world";
</x-torchlight-code></pre>
```

You can add any classes or other attributes, and they will be preserved:

```blade
<pre><x-torchlight-code id='hello-world' class='mt-4 mb-8' language='php'>
    echo "hello world";
</x-torchlight-code></pre>
```

