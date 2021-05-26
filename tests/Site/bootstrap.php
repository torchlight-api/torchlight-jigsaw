<?php

use Torchlight\Jigsaw\TorchlightExtension;

/** @var $container \Illuminate\Container\Container */
/** @var $events \TightenCo\Jigsaw\Events\EventBus */
TorchlightExtension::make($container, $events)->boot();
