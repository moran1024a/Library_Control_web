<?php

namespace App\Controllers;

use App\Core\Validator;
use App\Models\LogModel;
use App\Models\UserModel;
use Exception;

class UserController extends BaseController
{
    private UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function register(): void
    {
        $data = $this->sanitize($this->getJsonInput());
        $errors = Validator::required($data, ['username', 'password']);

        if (!empty($data['email']) && !Validator::email($data['email'])) {
            $errors['email'] = '邮箱格式错误';
        }

        if (!empty($data['phone']) && !Validator::phone($data['phone'])) {
            $errors['phone'] = '手机号格式错误';
        }

        if (!empty($data['password']) && !Validator::password($data['password'])) {
            $errors['password'] = '密码至少6位并包含字母和数字';
        }

        if (!empty($errors)) {
            $this->json(['success' => false, 'errors' => $errors], 422);
            return;
        }

        try {
            if ($this->userModel->findByUsername($data['username'])) {
                $this->json(['success' => false, 'message' => '用户名已存在'], 409);
                return;
            }

            $id = $this->userModel->createUser($data);
            $logger = new LogModel();
            $logger->recordAction(
                $id,
                'register',
                json_encode(['username' => $data['username']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );
            $this->json(['success' => true, 'message' => '注册成功', 'id' => $id]);
        } catch (Exception $exception) {
            $this->json(['success' => false, 'message' => $exception->getMessage()], 500);
        }
    }

    public function login(): void
    {
        $this->ensureSession();
        $data = $this->sanitize($this->getJsonInput());
        $errors = Validator::required($data, ['username', 'password']);

        if (!empty($errors)) {
            $this->json(['success' => false, 'errors' => $errors], 422);
            return;
        }

        try {
            $user = $this->userModel->findByUsername($data['username']);
            if (!$user || !password_verify($data['password'], $user['password_hash'])) {
                $this->json(['success' => false, 'message' => '用户名或密码错误'], 401);
                return;
            }

            $_SESSION['user'] = [
                'id' => (int) $user['id'],
                'username' => $user['username'],
                'role' => $user['role'],
            ];

            $logModel = new LogModel();
            $logModel->recordAction((int) $user['id'], 'login', '用户登录');

            $this->json(['success' => true, 'message' => '登录成功', 'user' => $_SESSION['user']]);
        } catch (Exception $exception) {
            $this->json(['success' => false, 'message' => $exception->getMessage()], 500);
        }
    }

    public function logout(): void
    {
        $this->ensureSession();
        $userId = $_SESSION['user']['id'] ?? null;
        session_destroy();

        $logModel = new LogModel();
        $logModel->recordAction($userId, 'logout', '用户退出');

        $this->json(['success' => true, 'message' => '已退出登录']);
    }

    public function update(): void
    {
        $this->ensureSession();
        if (empty($_SESSION['user'])) {
            $this->json(['success' => false, 'message' => '未登录'], 401);
            return;
        }

        $data = $this->sanitize($this->getJsonInput());

        if (!empty($data['email']) && !Validator::email($data['email'])) {
            $this->json(['success' => false, 'message' => '邮箱格式错误'], 422);
            return;
        }

        if (!empty($data['phone']) && !Validator::phone($data['phone'])) {
            $this->json(['success' => false, 'message' => '手机号格式错误'], 422);
            return;
        }

        if (!empty($data['password']) && !Validator::password($data['password'])) {
            $this->json(['success' => false, 'message' => '密码至少6位并包含字母和数字'], 422);
            return;
        }

        try {
            $updated = $this->userModel->updateUser($_SESSION['user']['id'], $data);
            $logModel = new LogModel();
            $logModel->recordAction(
                $_SESSION['user']['id'],
                'update_user',
                json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );
            $this->json(['success' => $updated, 'message' => $updated ? '更新成功' : '更新失败']);
        } catch (Exception $exception) {
            $this->json(['success' => false, 'message' => $exception->getMessage()], 500);
        }
    }
}
