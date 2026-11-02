<?php

function logWithDate($subject, $message, $destination = null): void
{
    if ($destination === null) {
        $destination = __DIR__ . '/../logs/error.log';
    }

    $entry = sprintf('[%s] %s: %s%s', date('Y-m-d H:i:s'), $subject, $message, PHP_EOL);
    error_log($entry, 3, $destination);
}

function throwDbNullConnection($connection): void {
    if ($connection === null) {
        throw new Exception('Database connection is null');
    }
}