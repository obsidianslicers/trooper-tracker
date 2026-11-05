<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Local Plugins Folder
    |--------------------------------------------------------------------------
    |
    | Each folder in here that holds a plugin.json file is a plugin. Plugins can
    | also be installed as Composer packages of type "trooptracker-plugin".
    | Plugins are off until an administrator enables them under Admin > Plugins
    | or with "php artisan plugins:enable <slug>". See docs/PLUGINS.md.
    |
    */

    'path' => env('TRACKER_PLUGINS_PATH', base_path('plugins')),

    /*
    |--------------------------------------------------------------------------
    | Uploaded Plugins
    |--------------------------------------------------------------------------
    |
    | Administrators can install a plugin by uploading a zip file under
    | Admin > Plugins. A plugin runs PHP code on the server once enabled, so
    | uploads are off unless TRACKER_PLUGINS_ALLOW_UPLOADS is true. Uploaded
    | plugins are kept outside the git checkout so a deploy cannot remove them.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Deploy Files
    |--------------------------------------------------------------------------
    |
    | Where the app saves the packages plugins need, and where deploy.sh saves
    | how installing them went. deploy.sh expects the default; tests change it.
    |
    */

    'deploy_path' => storage_path('app/plugin-deploy'),

    'uploads' => [
        'enabled' => (bool) env('TRACKER_PLUGINS_ALLOW_UPLOADS', false),
        'path' => env('TRACKER_PLUGINS_UPLOADS_PATH', storage_path('app/plugins')),
        'max_kilobytes' => (int) env('TRACKER_PLUGINS_MAX_UPLOAD_KB', 10240),
        'max_unzipped_kilobytes' => (int) env('TRACKER_PLUGINS_MAX_UNZIPPED_KB', 51200),
        'max_files' => (int) env('TRACKER_PLUGINS_MAX_UPLOAD_FILES', 2000),
    ],

];
