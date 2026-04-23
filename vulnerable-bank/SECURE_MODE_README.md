# Environment Variable Toggle System

## Cách sử dụng

Hệ thống này cho phép bạn toggle giữa phiên bản **VULNERABLE** (chứa lỗ hổng) và **SECURE** (có fix) bằng cách đặt environment variable.

### 1. Phiên Bản Vulnerable (Mặc định)
```bash
# Để chạy phiên bản vulnerable (mặc định)
SECURE_MODE=false docker-compose up
# hoặc
docker-compose up
```

### 2. Phiên Bản Secure (Sửa chữa)
```bash
# Để chạy phiên bản secure với tất cả các fix
SECURE_MODE=true docker-compose up
```

### 3. Quick Toggle
Chỉnh sửa `docker-compose.yml`:
```yaml
services:
  web:
    environment:
      SECURE_MODE: "true"  # Đổi thành "false" để vulnerable
```

Rồi rebuild:
```bash
docker-compose up --build
```

## Các lỗ hổng được phát hiện & Fix

| Tập tin | Lỗ hổng | Vulnerable | Secure |
|--------|---------|-----------|--------|
| **index.php** | SQL Injection (Login) | Sử dụng string interpolation | Prepared statements |
| **dashboard.php** | Reflected XSS (Search) | Echo trực tiếp | htmlspecialchars() |
| **transfer.php** | SQL Injection (Account lookup) | Direct query | Prepared statements |
| **bills.php** | SQL Injection (Category, Biller) | Direct query | Prepared statements |
| **cards.php** | SQL Injection (Type, Currency) | Direct query | Prepared statements |
| **profile.php** | Stored XSS (Bio) + SQL Injection + File Upload RCE | No sanitization, No validation | htmlspecialchars() + file type check |
| **loans.php** | SQL Injection (Amount) | Direct query | Prepared statements |

## Cấu trúc Code

```
vulnerable-bank/
├── config.php              # Định nghĩa SECURE_MODE constant
├── index.php              # Login page (SQL Injection toggle)
├── functions.php          # DB functions (SQL Injection toggles)
├── dashboard.php          # Dashboard (XSS + SQL Injection toggles)
├── transfer.php           # Transfer (SQL Injection toggle)
├── bills.php              # Bills (SQL Injection toggle)
├── cards.php              # Cards (SQL Injection toggle)
├── profile.php            # Profile (XSS + SQL Injection + File Upload toggles)
├── loans.php              # Loans (SQL Injection toggle)
└── docker-compose.yml     # Contains SECURE_MODE env var
```

## Cách hoạt động

1. **config.php** đọc environment variable `SECURE_MODE`
2. Trong các file PHP, sử dụng `if (SECURE_MODE)` để chọn giữa:
   - **Secure**: Prepared statements, HTML escaping, file validation
   - **Vulnerable**: String interpolation, direct echo, no validation

## Kiểm tra

Khi chạy vulnerable mode:
- Login: Thử SQL injection `' OR '1'='1`
- Dashboard: Thử XSS `<script>alert('XSS')</script>` trong search
- Profile: Upload `.php` file và thử RCE
- Transfer/Bills/Cards: Thử SQL injection trong fields

Khi chạy secure mode:
- Tất cả các tấn công trên sẽ bị block
- Input được sanitize
- Query được parameterized
- File upload được validate
