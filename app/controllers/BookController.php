<?php

namespace App\Controllers;

use App\Core\Logger;
use App\Core\Validator;
use App\Models\BookModel;
use App\Models\LogModel;
use Exception;

class BookController extends BaseController
{
    private BookModel $bookModel;
    private Logger $logger;

    public function __construct()
    {
        $this->bookModel = new BookModel();
        $this->logger = new Logger(new LogModel());
    }

    public function index(): void
    {
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $search = $_GET['search'] ?? null;
        $category = $_GET['category'] ?? null;

        try {
            $result = $this->bookModel->getAll($page, $search, $category);
            $this->json(['success' => true, 'data' => $result['data'], 'pagination' => $result['pagination']]);
        } catch (Exception $exception) {
            $this->json(['success' => false, 'message' => $exception->getMessage()], 500);
        }
    }

    public function store(): void
    {
        $this->ensureSession();
        $data = $this->sanitize($this->getJsonInput());
        $data['stock'] = isset($data['stock']) ? (int) $data['stock'] : 0;
        $errors = Validator::required($data, ['title', 'author', 'isbn']);
        $errors = array_merge($errors, Validator::minValue($data, 'stock', 0));

        if (!empty($errors)) {
            $this->json(['success' => false, 'errors' => $errors], 422);
            return;
        }

        try {
            $id = $this->bookModel->create($data);
            $this->logger->record(
                $_SESSION['user']['id'] ?? null,
                'create_book',
                json_encode(['book_id' => $id], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );
            $this->json(['success' => true, 'message' => '新增图书成功', 'id' => $id]);
        } catch (Exception $exception) {
            $this->json(['success' => false, 'message' => $exception->getMessage()], 500);
        }
    }

    public function update(int $id): void
    {
        $this->ensureSession();
        $data = $this->sanitize($this->getJsonInput());
        $data['stock'] = isset($data['stock']) ? (int) $data['stock'] : 0;
        $errors = Validator::required($data, ['title', 'author', 'isbn']);
        $errors = array_merge($errors, Validator::minValue($data, 'stock', 0));

        if (!empty($errors)) {
            $this->json(['success' => false, 'errors' => $errors], 422);
            return;
        }

        try {
            $updated = $this->bookModel->update($id, $data);
            $this->logger->record(
                $_SESSION['user']['id'] ?? null,
                'update_book',
                json_encode(['book_id' => $id], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );
            $this->json(['success' => $updated, 'message' => $updated ? '更新成功' : '更新失败']);
        } catch (Exception $exception) {
            $this->json(['success' => false, 'message' => $exception->getMessage()], 500);
        }
    }

    public function delete(int $id): void
    {
        $this->ensureSession();
        try {
            $deleted = $this->bookModel->delete($id);
            $this->logger->record(
                $_SESSION['user']['id'] ?? null,
                'delete_book',
                json_encode(['book_id' => $id], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );
            $this->json(['success' => $deleted, 'message' => $deleted ? '删除成功' : '删除失败']);
        } catch (Exception $exception) {
            $this->json(['success' => false, 'message' => $exception->getMessage()], 500);
        }
    }

    public function search(): void
    {
        $title = $_GET['title'] ?? null;
        $author = $_GET['author'] ?? null;
        $category = $_GET['category'] ?? null;
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;

        try {
            $result = $this->bookModel->getAll($page, null, $category, $author, $title);
            $this->json(['success' => true, 'data' => $result['data'], 'pagination' => $result['pagination']]);
        } catch (Exception $exception) {
            $this->json(['success' => false, 'message' => $exception->getMessage()], 500);
        }
    }
}
