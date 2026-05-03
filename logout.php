<?php

require __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    logout_admin();
}

redirect('/login.php');
