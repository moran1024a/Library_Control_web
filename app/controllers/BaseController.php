<?php

namespace App\Controllers;

class BaseController
{
    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    protected function getJsonInput(): array
    {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        return is_array($data) ? $data : $_POST;
    }

    protected function sanitize(array $data): array
    {
        return array_map(static fn($value) => is_string($value) ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : $value, $data);
    }

    protected function ensureSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}
