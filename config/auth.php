<?php

return [

//     'defaults' => [
//         'guard' => 'api',
//         'passwords' => 'users',
//     ],


//     'guards' => [
//         'web' => [
//             'driver' => 'session',
//             'provider' => 'users',
//         ],

//         'api' => [
//             'driver' => 'jwt',
//             'provider' => 'users',
//             'hash' => false,
//         ],
//     ],
// ];
    'defaults' => [
        'guard' => env('AUTH_GUARD', 'api'),
        'passwords' => 'users',
    ],

    'guards' => [
        'api' => [
        'driver' => 'jwt',
        'provider' => 'users',
        'hash' => false
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model'  =>  App\models\User::class,
        ]
    ],
];