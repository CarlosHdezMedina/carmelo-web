<?php

return [
    'app' => [
        'name' => 'Carmelo Espinosa',
        'base_url' => '',
        'timezone' => 'Europe/Madrid',
    ],
    'database' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'carmelo_espinosa',
        'user' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
    ],
    'admin' => [
        'username' => 'pintor',
        'password' => 'alegriafria',
    ],
    'uploads' => [
        'artworks_dir' => __DIR__ . '/uploads/artworks',
        'artworks_public_path' => '/uploads/artworks',
    ],
];
