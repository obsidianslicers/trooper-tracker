<?php

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

];
