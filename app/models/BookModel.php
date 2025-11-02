<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class BookModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAll(
        int $page = 1,
        ?string $search = null,
        ?string $category = null,
        ?string $author = null,
        ?string $title = null
    ): array
    {
        $config = require __DIR__ . '/../../config/config.php';
        $perPage = $config['app']['pagination'] ?? 10;
        $offset = ($page - 1) * $perPage;

        $conditions = [];
        $params = [];

        if ($search) {
            $conditions[] = '(title LIKE :search OR author LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }

        if ($title) {
            $conditions[] = 'title LIKE :title';
            $params[':title'] = '%' . $title . '%';
        }

        if ($author) {
            $conditions[] = 'author LIKE :author';
            $params[':author'] = '%' . $author . '%';
        }

        if ($category) {
            $conditions[] = 'category = :category';
            $params[':category'] = $category;
        }

        $where = $conditions ? ('WHERE ' . implode(' AND ', $conditions)) : '';

        $sql = "SELECT * FROM books {$where} ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $books = $stmt->fetchAll();

        $countSql = "SELECT COUNT(*) FROM books {$where}";
        $countStmt = $this->db->prepare($countSql);
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();

        return [
            'data' => $books,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => (int) $countStmt->fetchColumn(),
            ],
        ];
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM books WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $book = $stmt->fetch();

        return $book ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO books (title, author, isbn, category, stock) VALUES (:title, :author, :isbn, :category, :stock)');
        $stmt->bindValue(':title', $data['title']);
        $stmt->bindValue(':author', $data['author']);
        $stmt->bindValue(':isbn', $data['isbn']);
        $stmt->bindValue(':category', $data['category'] ?? '');
        $stmt->bindValue(':stock', (int) $data['stock'], PDO::PARAM_INT);
        $stmt->execute();

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare('UPDATE books SET title = :title, author = :author, isbn = :isbn, category = :category, stock = :stock WHERE id = :id');
        $stmt->bindValue(':title', $data['title']);
        $stmt->bindValue(':author', $data['author']);
        $stmt->bindValue(':isbn', $data['isbn']);
        $stmt->bindValue(':category', $data['category'] ?? '');
        $stmt->bindValue(':stock', (int) $data['stock'], PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM books WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }
}
