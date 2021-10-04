<?php

return [

    'app_path' => null,

    'paths' => [
        app_path('Tasks'),
    ],

    'cache_path' => null,

    'connection' => env('TASK_SCHEDULING_CONNECTION'),

    'queue' => env('TASK_SCHEDULING_QUEUE'),

];
