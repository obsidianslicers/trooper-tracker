<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Calendar Settings
    |--------------------------------------------------------------------------
    |
    | This configuration controls calendar-related settings for the application.
    | The timezone setting determines which timezone is used when generating
    | calendar links and displaying event times in calendar exports.
    |
    */

    'calendar' => [
        'timezone' => env('TRACKER_CALENDAR_TIMEZONE', 'UTC'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Support Information
    |--------------------------------------------------------------------------
    |
    | These configuration options define the support and donation settings
    | for the application. The "goal" sets the fundraising target amount,
    | and the "url" provides the link where users can make donations or
    | view support information.
    |
    */

    'support' => [
        'goal' => env('TRACKER_SUPPORT_GOAL'),
        'url' => env('TRACKER_SUPPORT_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Processing Settings
    |--------------------------------------------------------------------------
    |
    | This configuration defines the image processing driver used by the
    | Intervention Image library for handling image uploads and manipulation.
    | Supported drivers include GD (default) and Imagick.
    |
    */

    'image' => [
        'driver' => env('TRACKER_IMAGE_DRIVER', Intervention\Image\Drivers\Imagick\Driver::class),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication / XenForo Integration
    |--------------------------------------------------------------------------
    |
    | This flag controls whether a linked XenForo account is mandatory for
    | authenticated troopers. When enabled, the EnsureXenforoLinked middleware
    | will redirect users without a XenForo link to the linking page.
    |
    */

    'auth' => [
        'require_xenforo' => env('TRACKER_REQUIRE_XENFORO', false),
    ],

];
