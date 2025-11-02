<?php

namespace App\Core;

class Validator
{
    public static function required(array $data, array $fields): array
    {
        $errors = [];
        foreach ($fields as $field) {
            if (!isset($data[$field]) || trim((string) $data[$field]) === '') {
                $errors[$field] = '字段不能为空';
            }
        }

        return $errors;
    }

    public static function minValue(array $data, string $field, int $min): array
    {
        if ((int) ($data[$field] ?? 0) < $min) {
            return [$field => '数值不能小于 ' . $min];
        }

        return [];
    }

    public static function email(?string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function phone(?string $value): bool
    {
        return $value !== null && (bool) preg_match('/^\+?[0-9\-]{6,20}$/', $value);
    }

    public static function password(string $value): bool
    {
        return (bool) preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{6,}$/', $value);
    }
}
