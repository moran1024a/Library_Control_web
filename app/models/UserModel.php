<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class UserModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function createUser(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO users (username, password_hash, email, phone, role) VALUES (:username, :password_hash, :email, :phone, :role)');
        $stmt->bindValue(':username', $data['username']);
        $stmt->bindValue(':password_hash', password_hash($data['password'], PASSWORD_DEFAULT));
        $stmt->bindValue(':email', $data['email'] ?? null);
        $stmt->bindValue(':phone', $data['phone'] ?? null);
        $stmt->bindValue(':role', $data['role'] ?? 'user');
        $stmt->execute();

        return (int) $this->db->lastInsertId();
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE username = :username');
        $stmt->bindValue(':username', $username);
        $stmt->execute();
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function updateUser(int $id, array $data): bool
    {
        $fields = ['email = :email', 'phone = :phone'];
        $params = [
            ':email' => $data['email'] ?? null,
            ':phone' => $data['phone'] ?? null,
            ':id' => $id,
        ];

        if (!empty($data['password'])) {
            $fields[] = 'password_hash = :password_hash';
            $params[':password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        if (!empty($data['role'])) {
            $fields[] = 'role = :role';
            $params[':role'] = $data['role'];
        }

        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            if ($value === null) {
                $stmt->bindValue($key, null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue($key, $value);
            }
        }

        return $stmt->execute();
    }
}
