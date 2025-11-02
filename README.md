# 图书管理系统（Library Management System）

该项目是基于自定义轻量级 MVC 框架构建的图书管理系统，满足以下要求：

- **语言**：PHP 7.4
- **数据库**：MySQL 5.7
- **结构**：MVC + Composer 自动加载
- **规范**：PSR-1 / PSR-2
- **功能阶段**：环境初始化、图书信息、用户管理、借还模块、查询统计、日志记录

## 目录结构

```
app/
  controllers/
  models/
  views/
  core/
config/
public/
vendor/
```

## 快速开始

1. 安装依赖（如果本地已安装 Composer，可执行 `composer dump-autoload` 重新生成自动加载文件）。
2. 创建数据库并导入 `config/config.php` 中的连接信息。
3. 运行初始化 SQL：

```sql
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE,
  password_hash VARCHAR(255),
  email VARCHAR(100),
  phone VARCHAR(20),
  role ENUM('user','admin') DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE books (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200),
  author VARCHAR(100),
  isbn VARCHAR(30),
  category VARCHAR(50),
  stock INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE borrow_records (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  book_id INT,
  borrow_date DATE,
  return_date DATE NULL,
  status ENUM('borrowed','returned','overdue'),
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (book_id) REFERENCES books(id)
);
CREATE TABLE logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  action VARCHAR(50),
  details TEXT,
  timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

4. 配置 Web 服务器根目录指向 `public/index.php`，即可通过浏览器访问首页（包含简单的中文 AJAX 演示页面）。

## API 概览

| 方法 | 路径 | 描述 |
| ---- | ---- | ---- |
| GET  | `/books` | 列出图书，支持 `search`、`category`、分页参数 |
| POST | `/books/add` | 新增图书 |
| POST | `/books/edit/{id}` | 更新图书 |
| POST | `/books/delete/{id}` | 删除图书 |
| GET  | `/search/books` | 多条件图书查询（标题、作者、分类、分页） |
| POST | `/register` | 用户注册 |
| POST | `/login` | 用户登录并建立会话 |
| GET  | `/logout` | 退出登录 |
| POST | `/user/update` | 更新当前用户信息 |
| POST | `/borrow/{book_id}` | 借阅图书（登录用户） |
| POST | `/return/{record_id}` | 归还图书（登录用户） |
| GET  | `/records` | 查询借阅记录（普通用户仅限本人，管理员可查看全部） |
| GET  | `/search/records` | 多条件借阅记录查询 |
| GET  | `/stats/borrow` | 借阅统计（JSON，可用于 Chart.js） |
| GET  | `/check/overdue` | 检查并标记逾期记录 |

所有接口返回 JSON，配合前端 AJAX 可实现实时交互。

## 测试数据库连接

在部署前可运行以下脚本（例如在项目根目录执行 `php -r "require 'public/index.php';"` 将触发数据库连接），或者在任意模型中调用 `Database::getInstance()` 验证连接。

## 前端演示

访问根路径 `/` 可看到使用 Bootstrap + 原生 Fetch 实现的基础中文界面，用于演示登录、注册和图书查询功能，可在此基础上扩展完善前端。

## 日志

系统通过 `LogModel` 将用户关键操作（注册、登录、借阅、归还等）写入 `logs` 表，便于审计和追踪。

## 许可证

MIT
