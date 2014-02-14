<?php
require_once __DIR__ . '/../vendor/autoload.php';

$klein = new \Klein\Klein();

$klein->respond(function () {
    return 'Welcome';
});

$klein->dispatch();