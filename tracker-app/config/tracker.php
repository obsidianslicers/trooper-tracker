<?php

use Intervention\Image\Drivers\Imagick\Driver;

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
        'timezone' => env('TRACKER_CALENDAR_TIMEZONE', env('APP_TIMEZONE', 'UTC')),
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
    | Donor Badge Image
    |--------------------------------------------------------------------------
    |
    | URL of the image displayed on a trooper's profile when they are an active
    | donor. Set TRACKER_DONOR_IMAGE_URL in your .env to customise this.
    |
    */

    'donor' => [
        'image_url' => env('TRACKER_DONOR_IMAGE_URL'),
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
        'driver' => env(
            'TRACKER_IMAGE_DRIVER',
            extension_loaded('imagick')
            ? Driver::class
            : Intervention\Image\Drivers\Gd\Driver::class
        ),
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

    /*
    |--------------------------------------------------------------------------
    | Exception Notification Settings
    |--------------------------------------------------------------------------
    |
    | Controls whether admin exception notification emails are sent for
    | unhandled server errors.
    |
    */

    'exception_notifications' => [
        'enabled' => env('TRACKER_EXCEPTION_EMAIL_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Supervisor / Queue Worker Health Check
    |--------------------------------------------------------------------------
    |
    | Controls the staleness thresholds used to detect whether the queue
    | worker process (kept alive by Supervisor in production) is still
    | consuming jobs, and whether admins should be emailed when it goes
    | down for longer than the configured threshold.
    |
    */

    'supervisor_check' => [
        'heartbeat_warn_minutes' => (int) env('TRACKER_SUPERVISOR_WARN_MINUTES', 3),
        'heartbeat_down_minutes' => (int) env('TRACKER_SUPERVISOR_DOWN_MINUTES', 10),
        'email_enabled' => env('TRACKER_SUPERVISOR_EMAIL_ENABLED', false),
        'renotify_after_minutes' => (int) env('TRACKER_SUPERVISOR_RENOTIFY_MINUTES', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | System Check
    |--------------------------------------------------------------------------
    |
    | Targets used by the admin System Check page. php.ini values below these
    | targets are reported as warnings for both this app and the XenForo forum.
    |
    */

    'system_check' => [
        'php_ini' => [
            'upload_max_filesize' => '100M',
            'post_max_size' => '256M',
            'memory_limit' => '512M',
            'max_execution_time' => '300',
            'max_input_time' => '300',
            'max_file_uploads' => '30',
        ],
        'ssl_warn_days' => 14,
        'disk_warn_percent' => 10,
        'disk_warn_bytes' => 2 * 1024 * 1024 * 1024,
        'legacy_path_prefix' => '/home/bitnami',
    ],

];
