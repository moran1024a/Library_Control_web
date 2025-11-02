<?php

namespace App\Controllers;

use App\Models\BorrowModel;
use App\Models\LogModel;
use Exception;

class BorrowController extends BaseController
{
    private BorrowModel $borrowModel;

    public function __construct()
    {
        $this->borrowModel = new BorrowModel();
    }

    public function borrow(int $bookId): void
    {
        $this->ensureSession();
        if (empty($_SESSION['user'])) {
            $this->json(['success' => false, 'message' => '未登录'], 401);
            return;
        }

        try {
            $result = $this->borrowModel->borrowBook($_SESSION['user']['id'], $bookId);
            if ($result['success']) {
                (new LogModel())->recordAction(
                    $_SESSION['user']['id'],
                    'borrow_book',
                    json_encode(['book_id' => $bookId], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                );
            }
            $this->json($result, $result['success'] ? 200 : 400);
        } catch (Exception $exception) {
            $this->json(['success' => false, 'message' => $exception->getMessage()], 500);
        }
    }

    public function return(int $recordId): void
    {
        $this->ensureSession();
        if (empty($_SESSION['user'])) {
            $this->json(['success' => false, 'message' => '未登录'], 401);
            return;
        }

        try {
            $result = $this->borrowModel->returnBook($recordId);
            if ($result['success']) {
                (new LogModel())->recordAction(
                    $_SESSION['user']['id'],
                    'return_book',
                    json_encode(['record_id' => $recordId], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                );
            }
            $this->json($result, $result['success'] ? 200 : 400);
        } catch (Exception $exception) {
            $this->json(['success' => false, 'message' => $exception->getMessage()], 500);
        }
    }

    public function records(): void
    {
        $this->ensureSession();
        if (empty($_SESSION['user'])) {
            $this->json(['success' => false, 'message' => '未登录'], 401);
            return;
        }

        $userId = $_SESSION['user']['id'];
        $queryUserId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : null;
        $status = $_GET['status'] ?? null;
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;

        if ($_SESSION['user']['role'] !== 'admin') {
            $userId = $_SESSION['user']['id'];
        } else {
            $userId = $queryUserId;
        }

        try {
            $result = $this->borrowModel->getRecords($userId, $status, $page);
            $this->json(['success' => true, 'data' => $result['data'], 'pagination' => $result['pagination']]);
        } catch (Exception $exception) {
            $this->json(['success' => false, 'message' => $exception->getMessage()], 500);
        }
    }

    public function search(): void
    {
        $this->ensureSession();
        if (empty($_SESSION['user'])) {
            $this->json(['success' => false, 'message' => '未登录'], 401);
            return;
        }

        $userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : null;
        $status = $_GET['status'] ?? null;
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;

        if ($_SESSION['user']['role'] !== 'admin') {
            $userId = $_SESSION['user']['id'];
        }

        try {
            $result = $this->borrowModel->getRecords($userId, $status, $page);
            $this->json(['success' => true, 'data' => $result['data'], 'pagination' => $result['pagination']]);
        } catch (Exception $exception) {
            $this->json(['success' => false, 'message' => $exception->getMessage()], 500);
        }
    }

    public function stats(): void
    {
        $this->ensureSession();
        if (empty($_SESSION['user'])) {
            $this->json(['success' => false, 'message' => '未登录'], 401);
            return;
        }

        try {
            $stats = $this->borrowModel->getStatistics();
            $this->json(['success' => true, 'data' => $stats]);
        } catch (Exception $exception) {
            $this->json(['success' => false, 'message' => $exception->getMessage()], 500);
        }
    }

    public function checkOverdue(): void
    {
        $this->ensureSession();
        if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            $this->json(['success' => false, 'message' => '无权限'], 403);
            return;
        }

        try {
            $count = $this->borrowModel->checkOverdue();
            $this->json(['success' => true, 'updated' => $count]);
        } catch (Exception $exception) {
            $this->json(['success' => false, 'message' => $exception->getMessage()], 500);
        }
    }
}
