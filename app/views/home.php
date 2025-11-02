<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>图书管理系统</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-4">
    <h1 class="mb-4">图书管理系统演示</h1>
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">用户登录</div>
                <div class="card-body">
                    <form id="loginForm">
                        <div class="mb-3">
                            <label class="form-label">用户名</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">密码</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <button class="btn btn-primary w-100" type="submit">登录</button>
                    </form>
                    <div class="alert alert-info mt-3" id="loginInfo" hidden></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">用户注册</div>
                <div class="card-body">
                    <form id="registerForm">
                        <div class="mb-3">
                            <label class="form-label">用户名</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">邮箱</label>
                            <input type="email" class="form-control" name="email">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">手机号</label>
                            <input type="text" class="form-control" name="phone">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">密码</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <button class="btn btn-success w-100" type="submit">注册</button>
                    </form>
                    <div class="alert alert-info mt-3" id="registerInfo" hidden></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">图书查询</div>
                <div class="card-body">
                    <form id="bookSearchForm">
                        <div class="mb-3">
                            <label class="form-label">标题或作者</label>
                            <input type="text" class="form-control" name="search">
                        </div>
                        <button class="btn btn-secondary w-100" type="submit">查询</button>
                    </form>
                    <ul class="list-group mt-3" id="bookList"></ul>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
const fetchJson = (url, options = {}) => fetch(url, Object.assign({
    headers: { 'Content-Type': 'application/json' }
}, options)).then(res => res.json());

document.getElementById('loginForm').addEventListener('submit', async (event) => {
    event.preventDefault();
    const formData = Object.fromEntries(new FormData(event.target));
    const result = await fetchJson('/login', { method: 'POST', body: JSON.stringify(formData) });
    const info = document.getElementById('loginInfo');
    info.hidden = false;
    info.className = `alert ${result.success ? 'alert-success' : 'alert-danger'}`;
    info.textContent = result.message || JSON.stringify(result);
});

document.getElementById('registerForm').addEventListener('submit', async (event) => {
    event.preventDefault();
    const formData = Object.fromEntries(new FormData(event.target));
    const result = await fetchJson('/register', { method: 'POST', body: JSON.stringify(formData) });
    const info = document.getElementById('registerInfo');
    info.hidden = false;
    info.className = `alert ${result.success ? 'alert-success' : 'alert-danger'}`;
    info.textContent = result.message || JSON.stringify(result);
});

document.getElementById('bookSearchForm').addEventListener('submit', async (event) => {
    event.preventDefault();
    const formData = Object.fromEntries(new FormData(event.target));
    const params = new URLSearchParams(formData);
    const result = await fetchJson(`/books?search=${encodeURIComponent(formData.search || '')}`);
    const list = document.getElementById('bookList');
    list.innerHTML = '';
    if (result.success) {
        result.data.forEach((book) => {
            const item = document.createElement('li');
            item.className = 'list-group-item';
            item.textContent = `${book.title} / ${book.author} （库存：${book.stock}）`;
            list.appendChild(item);
        });
    } else {
        const item = document.createElement('li');
        item.className = 'list-group-item list-group-item-danger';
        item.textContent = result.message || '查询失败';
        list.appendChild(item);
    }
});
</script>
</body>
</html>
