<?php

function sendResponse400(): void
{
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'Error',
        'message' => 'Bad Request'
    ]);
}

function sendResponse401(): void
{
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'Error',
        'message' => 'Unauthorized'
    ]);
}

function sendResponse403(): void
{
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'Error',
        'message' => 'Forbidden'
    ]);
}

function sendResponse404(): void
{
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'Error',
        'message' => 'Not Found'
    ]);
}

function sendResponse405(): void
{
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'Error',
        'message' => 'Method Not Allowed'
    ]);
}

function sendResponse500(): void
{
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'Error',
        'message' => 'Internal server error occurred'
    ]);
}

function sendResponseCustom(string $message, mixed $data = null, string $status = 'Success', int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ]);
}