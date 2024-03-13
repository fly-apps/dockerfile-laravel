<?php

return [
    'paths' => [
        resource_path('views'),
    ],
    'compiled' => env(
        'VIEW_COMPILED_PATH',
        sys_get_temp_dir().'/dockerfile-laravel-view', // Please rm -r '/tmp/dockerfile-laravel-view' to delete cached views
    )
];