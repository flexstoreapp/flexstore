<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Image Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default image processing driver that will be
    | used when manipulating or converting images. This driver is always
    | utilized unless another driver is explicitly specified instead.
    |
    | Supported: "gd", "imagick"
    |
    */

    'default' => env('IMAGE_DRIVER', extension_loaded('imagick') ? 'imagick' : 'gd'),

    /*
    |--------------------------------------------------------------------------
    | Image Extension Availability
    |--------------------------------------------------------------------------
    |
    | Uploads are stored untouched when neither image extension is available,
    | so that a server without GD or Imagick can still accept media.
    |
    */

    'extension_loaded' => extension_loaded('imagick') || extension_loaded('gd'),

];
