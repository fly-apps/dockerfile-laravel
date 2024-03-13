<?php

return [
    'paths' => [
        resource_path('views'),
    ],
    'compiled' => env(
        'VIEW_COMPILED_PATH',
        getcwd().'/storage/framework/views'  // OR: sys_get_temp_dir().'/dockerfile-laravel-view'
    )
];