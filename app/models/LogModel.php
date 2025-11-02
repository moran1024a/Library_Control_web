<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class LogModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function recordAction(?int $userId, string $action, string $details): void
    {
        $stmt = $this->db->prepare('INSERT INTO logs (user_id, action, details) VALUES (:user_id, :action, :details)');
        if ($userId === null) {
            $stmt->bindValue(':user_id', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        }
        $stmt->bindValue(':action', $action);
        $stmt->bindValue(':details', $details);
        $stmt->execute();
    }
}
