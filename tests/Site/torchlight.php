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