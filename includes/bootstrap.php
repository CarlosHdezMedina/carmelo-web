<?php

$config = require __DIR__ . '/../config.php';

date_default_timezone_set($config['app']['timezone']);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/functions.php';
