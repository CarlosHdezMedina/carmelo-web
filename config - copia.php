<?php

return [
    'app' => [
        'name' => 'Carmelo Espinosa',
        'base_url' => '',
        'timezone' => 'Europe/Madrid',
    ],
    'database' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'carmelo',
        'user' => 'root',
        'password' => 'root',
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
