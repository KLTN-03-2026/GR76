<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOS System API Documentation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .endpoint { font-family: monospace; font-weight: bold; }
        .method-get { color: #0d6efd; font-weight: bold; }
        .method-post { color: #198754; font-weight: bold; }
        .method-put, .method-patch { color: #fd7e14; font-weight: bold; }
        .method-delete { color: #dc3545; font-weight: bold; }
        pre { background-color: #f1f1f1; padding: 10px; border-radius: 5px; font-size: 0.85rem;}
    </style>
</head>
<body>
<div class="container py-5">
    <h1 class="mb-4">SOS System - API Documentation</h1>
    <p class="lead">Base URL: <code>http://localhost:8000</code></p>
    
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-dark text-white"><h4>A. Authentication (Public)</h4></div>
        <div class="card-body overflow-auto">
            <table class="table table-bordered table-striped">
                <thead class="table-light"><tr><th>Method</th><th>Endpoint</th><th>Description</th><th>Request Body Example</th></tr></thead>
                <tbody>
                    <tr><td class="method-post">POST</td><td class="endpoint">/api/login</td><td>User Login. Returns token.</td><td><pre>{"email": "user1@example.com", "mat_khau": "password"}</pre></td></tr>
                    <tr><td class="method-post">POST</td><td class="endpoint">/api/admin/login</td><td>Admin Login. Returns token.</td><td><pre>{"ten_dang_nhap": "admin1", "mat_khau": "password"}</pre></td></tr>
                    <tr><td class="method-post">POST</td><td class="endpoint">/api/register</td><td>User Registration</td><td><pre>{"ten": "A", "email": "a@x.com", "so_dien_thoai": "0912345678", "mat_khau": "password"}</pre></td></tr>
                    <tr><td class="method-post">POST</td><td class="endpoint">/api/logout</td><td>Logout (Requires Bearer token)</td><td><em>None</em></td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-primary text-white"><h4>B. Admin Endpoints (Requires Admin Token)</h4></div>
        <div class="card-body overflow-auto">
            <table class="table table-bordered table-striped">
                <thead class="table-light"><tr><th>Method</th><th>Endpoint</th><th>Description</th></tr></thead>
                <tbody>
                    <tr><td colspan="3" class="bg-light fw-bold text-center">Quản lý người dùng</td></tr>
                    <tr><td class="method-get">GET</td><td class="endpoint">/api/admin/users</td><td>Danh sách người dùng</td></tr>
                    <tr><td class="method-post">POST</td><td class="endpoint">/api/admin/users</td><td>Tạo người dùng mới</td></tr>
                    <tr><td class="method-get">GET</td><td class="endpoint">/api/admin/users/{id}</td><td>Chi tiết người dùng</td></tr>
                    <tr><td class="method-put">PUT</td><td class="endpoint">/api/admin/users/{id}</td><td>Cập nhật người dùng</td></tr>
                    <tr><td class="method-delete">DELETE</td><td class="endpoint">/api/admin/users/{id}</td><td>Xoá người dùng</td></tr>
                    <tr><td class="method-get">GET</td><td class="endpoint">/api/admin/users/search?q=</td><td>Tìm kiếm người dùng</td></tr>
                    <tr><td class="method-patch">PATCH</td><td class="endpoint">/api/admin/users/{id}/password</td><td>Đổi mật khẩu người dùng</td></tr>
                    
                    <tr><td colspan="3" class="bg-light fw-bold text-center">Quản lý sự cố</td></tr>
                    <tr><td class="method-get">GET</td><td class="endpoint">/api/admin/su-co</td><td>Danh sách sự cố</td></tr>
                    <tr><td class="method-post">POST</td><td class="endpoint">/api/admin/su-co</td><td>Tạo sự cố (cần truyền id_nguoi_dung)</td></tr>
                    <tr><td class="method-get">GET</td><td class="endpoint">/api/admin/su-co/{id}</td><td>Chi tiết sự cố</td></tr>
                    <tr><td class="method-put">PUT</td><td class="endpoint">/api/admin/su-co/{id}</td><td>Cập nhật sự cố</td></tr>
                    <tr><td class="method-delete">DELETE</td><td class="endpoint">/api/admin/su-co/{id}</td><td>Xóa sự cố</td></tr>
                    <tr><td class="method-get">GET</td><td class="endpoint">/api/admin/su-co/search?q=</td><td>Tìm kiếm sự cố</td></tr>
                    <tr><td class="method-patch">PATCH</td><td class="endpoint">/api/admin/su-co/{id}/status</td><td>Cập nhật trạng thái sự cố (body: <code>{"trang_thai": "Đã xử lý"}</code>)</td></tr>

                    <tr><td colspan="3" class="bg-light fw-bold text-center">Khác</td></tr>
                    <tr><td class="method-get">GET</td><td class="endpoint">/api/admin/logs</td><td>Xem log hệ thống (lọc query params: id_admin, id_su_co)</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-success text-white"><h4>C. User Endpoints (Requires User Token)</h4></div>
        <div class="card-body overflow-auto">
            <table class="table table-bordered table-striped">
                <thead class="table-light"><tr><th>Method</th><th>Endpoint</th><th>Description</th></tr></thead>
                <tbody>
                    <tr><td class="method-get">GET</td><td class="endpoint">/api/me</td><td>Xem hồ sơ cá nhân</td></tr>
                    <tr><td class="method-put">PUT</td><td class="endpoint">/api/me</td><td>Cập nhật hồ sơ</td></tr>
                    <tr><td class="method-patch">PATCH</td><td class="endpoint">/api/me/password</td><td>Đổi mật khẩu cá nhân</td></tr>
                    <tr><td class="method-post">POST</td><td class="endpoint">/api/su-co</td><td>Đăng sự cố</td></tr>
                    <tr><td class="method-get">GET</td><td class="endpoint">/api/my-su-co</td><td>Xem sự cố của mình</td></tr>
                    <tr><td class="method-get">GET</td><td class="endpoint">/api/su-co/{id}</td><td>Xem chi tiết sự cố</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-info text-white"><h4>D. AI Integration (Requires Token)</h4></div>
        <div class="card-body overflow-auto">
            <table class="table table-bordered table-striped">
                <thead class="table-light"><tr><th>Method</th><th>Endpoint</th><th>Description</th></tr></thead>
                <tbody>
                    <tr><td class="method-post">POST</td><td class="endpoint">/api/ai/phan-tich/{id_su_co}</td><td>AI phân tích sự cố (trả về loại, mức độ khẩn cấp, độ tin cậy)</td></tr>
                    <tr><td class="method-post">POST</td><td class="endpoint">/api/ai/check-duplicate/{id_su_co}</td><td>AI kiểm tra trùng lặp sự cố với các sự cố khác</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
