<?php

require_once __DIR__ . '/../src/Core/DotEnv.php';
require_once __DIR__ . '/../helpers/global_helper.php';

try {
    DotEnv::loadFromFile(__DIR__ . '/../.env');
} catch (InvalidArgumentException|RuntimeException $e) {
    logWithDate('Env configuration failed', $e->getMessage());
    exit(1);
}

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($requestUri !== "/client.php") {
    require_once __DIR__ . '/../src/Core/Database.php';
    require_once __DIR__ . '/front_controller.php';
} else {
    require_once __DIR__ . '/client.php';
}