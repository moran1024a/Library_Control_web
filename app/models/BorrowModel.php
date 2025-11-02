<?php

namespace App\Models;

use App\Core\Database;
use DateTime;
use PDO;
use PDOException;

class BorrowModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function borrowBook(int $userId, int $bookId): array
    {
        $config = require __DIR__ . '/../../config/config.php';
        try {
            $this->db->beginTransaction();

            $bookStmt = $this->db->prepare('SELECT stock FROM books WHERE id = :id FOR UPDATE');
            $bookStmt->bindValue(':id', $bookId, PDO::PARAM_INT);
            $bookStmt->execute();
            $book = $bookStmt->fetch();

            if (!$book) {
                $this->db->rollBack();
                return ['success' => false, 'message' => '图书不存在'];
            }

            if ((int) $book['stock'] <= 0) {
                $this->db->rollBack();
                return ['success' => false, 'message' => '库存不足'];
            }

            $duplicateStmt = $this->db->prepare('SELECT COUNT(*) FROM borrow_records WHERE user_id = :user_id AND book_id = :book_id AND status = "borrowed"');
            $duplicateStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $duplicateStmt->bindValue(':book_id', $bookId, PDO::PARAM_INT);
            $duplicateStmt->execute();

            if ((int) $duplicateStmt->fetchColumn() > 0) {
                $this->db->rollBack();
                return ['success' => false, 'message' => '已借阅该图书'];
            }

            $insertStmt = $this->db->prepare('INSERT INTO borrow_records (user_id, book_id, borrow_date, status) VALUES (:user_id, :book_id, :borrow_date, "borrowed")');
            $insertStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $insertStmt->bindValue(':book_id', $bookId, PDO::PARAM_INT);
            $insertStmt->bindValue(':borrow_date', (new DateTime())->format('Y-m-d'));
            $insertStmt->execute();

            $updateStock = $this->db->prepare('UPDATE books SET stock = stock - 1 WHERE id = :id');
            $updateStock->bindValue(':id', $bookId, PDO::PARAM_INT);
            $updateStock->execute();

            $this->db->commit();

            return ['success' => true, 'record_id' => (int) $this->db->lastInsertId()];
        } catch (PDOException $exception) {
            $this->db->rollBack();
            $message = ($config['app']['debug'] ?? false) ? $exception->getMessage() : '借阅失败';
            return ['success' => false, 'message' => $message];
        }
    }

    public function returnBook(int $recordId): array
    {
        $config = require __DIR__ . '/../../config/config.php';
        try {
            $this->db->beginTransaction();

            $recordStmt = $this->db->prepare('SELECT * FROM borrow_records WHERE id = :id FOR UPDATE');
            $recordStmt->bindValue(':id', $recordId, PDO::PARAM_INT);
            $recordStmt->execute();
            $record = $recordStmt->fetch();

            if (!$record) {
                $this->db->rollBack();
                return ['success' => false, 'message' => '借阅记录不存在'];
            }

            if ($record['status'] === 'returned') {
                $this->db->rollBack();
                return ['success' => false, 'message' => '图书已归还'];
            }

            $updateStmt = $this->db->prepare('UPDATE borrow_records SET return_date = :return_date, status = "returned" WHERE id = :id');
            $updateStmt->bindValue(':return_date', (new DateTime())->format('Y-m-d'));
            $updateStmt->bindValue(':id', $recordId, PDO::PARAM_INT);
            $updateStmt->execute();

            $updateStock = $this->db->prepare('UPDATE books SET stock = stock + 1 WHERE id = :id');
            $updateStock->bindValue(':id', (int) $record['book_id'], PDO::PARAM_INT);
            $updateStock->execute();

            $this->db->commit();

            return ['success' => true];
        } catch (PDOException $exception) {
            $this->db->rollBack();
            $message = ($config['app']['debug'] ?? false) ? $exception->getMessage() : '归还失败';
            return ['success' => false, 'message' => $message];
        }
    }

    public function getRecords(?int $userId = null, ?string $status = null, int $page = 1): array
    {
        $config = require __DIR__ . '/../../config/config.php';
        $perPage = $config['app']['pagination'] ?? 10;
        $offset = ($page - 1) * $perPage;

        $conditions = [];
        $params = [];

        if ($userId !== null) {
            $conditions[] = 'user_id = :user_id';
            $params[':user_id'] = $userId;
        }

        if ($status !== null) {
            $conditions[] = 'status = :status';
            $params[':status'] = $status;
        }

        $where = $conditions ? ('WHERE ' . implode(' AND ', $conditions)) : '';

        $sql = "SELECT br.*, b.title, u.username FROM borrow_records br JOIN books b ON br.book_id = b.id JOIN users u ON br.user_id = u.id {$where} ORDER BY br.borrow_date DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $records = $stmt->fetchAll();

        $countSql = "SELECT COUNT(*) FROM borrow_records {$where}";
        $countStmt = $this->db->prepare($countSql);
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();

        return [
            'data' => $records,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => (int) $countStmt->fetchColumn(),
            ],
        ];
    }

    public function checkOverdue(): int
    {
        $stmt = $this->db->prepare('UPDATE borrow_records SET status = "overdue" WHERE status = "borrowed" AND borrow_date < DATE_SUB(CURDATE(), INTERVAL 30 DAY)');
        $stmt->execute();
        return $stmt->rowCount();
    }

    public function getStatistics(): array
    {
        $totalBorrowedStmt = $this->db->query('SELECT COUNT(*) AS total FROM borrow_records');
        $totalBorrowed = (int) $totalBorrowedStmt->fetchColumn();

        $statusStmt = $this->db->query('SELECT status, COUNT(*) AS count FROM borrow_records GROUP BY status');
        $statusCounts = $statusStmt->fetchAll();

        $topBooksStmt = $this->db->query('SELECT b.title, COUNT(*) AS borrow_count FROM borrow_records br JOIN books b ON br.book_id = b.id GROUP BY br.book_id ORDER BY borrow_count DESC LIMIT 5');
        $topBooks = $topBooksStmt->fetchAll();

        return [
            'total' => $totalBorrowed,
            'by_status' => $statusCounts,
            'top_books' => $topBooks,
        ];
    }
}
