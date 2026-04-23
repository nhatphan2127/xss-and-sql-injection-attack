# SECURE_MODE Implementation - Summary of Changes

## Overview
Tôi đã thực hiện một hệ thống toggle giữa phiên bản Vulnerable và Secure bằng environment variable `SECURE_MODE`. Khi `SECURE_MODE=true`, tất cả các lỗ hổng XSS, SQL Injection, và File Upload sẽ được fix.

---

## Files Created (Tạo mới)

### 1. **config.php** (NEW)
```php
<?php
/**
 * Security Configuration
 * Set SECURE_MODE to toggle between vulnerable and secure versions
 */

// Get SECURE_MODE from environment, default to false (vulnerable mode)
define('SECURE_MODE', getenv('SECURE_MODE') === 'true' || getenv('SECURE_MODE') === '1');

// Helper function to conditionally escape output (prevent XSS)
function secure_output($text) {
    if (SECURE_MODE) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
    return $text;
}

// Helper function for secure SQL query building
function prepare_statement($conn, $query, $params = []) {
    if (!SECURE_MODE) {
        // Vulnerable mode: perform string interpolation
        foreach ($params as $key => $value) {
            $query = str_replace('$' . $key, "'" . $value . "'", $query);
        }
        return $query;
    }
    // Secure mode: return prepared statement
    return $conn->prepare($query);
}

// Debug mode to show which version is running
if (getenv('DEBUG') === 'true') {
    error_log('Bank running in ' . (SECURE_MODE ? 'SECURE' : 'VULNERABLE') . ' mode');
}
?>
```

**Mục đích:** Quản lý cấu hình SECURE_MODE và cung cấp helper functions cho việc toggle giữa 2 chế độ.

---

### 2. **SECURE_MODE_README.md** (NEW)
Tài liệu hướng dẫn cách sử dụng SECURE_MODE, danh sách lỗ hổng được fix, cấu trúc code, v.v.

### 3. **toggle-mode.sh** (NEW)
Script shell helper để dễ dàng chuyển đổi giữa SECURE và VULNERABLE mode.

---

## Files Modified (Sửa đổi)

### 1. **index.php** (MODIFIED)
**Các thay đổi:**
- ✅ Thêm `require_once 'config.php'` để load SECURE_MODE
- ✅ Thêm conditional cookie security settings:
  - SECURE_MODE=true: httponly=true, secure=true, samesite=Strict
  - SECURE_MODE=false: httponly=false (VULNERABLE)
- ✅ Thêm conditional SQL injection prevention:
  - SECURE_MODE=true: Dùng prepared statements với bind_param
  - SECURE_MODE=false: Dùng string interpolation (VULNERABLE)

**Lỗ hổng fix:**
- SQL Injection (Login form) - Line 21

---

### 2. **functions.php** (MODIFIED)
**Các thay đổi:**
- ✅ Thêm `require_once 'config.php'`
- ✅ get_user_data() - Thêm conditional:
  - SECURE_MODE=true: Prepared statement với bind_param("i", $user_id)
  - SECURE_MODE=false: Direct SQL interpolation (VULNERABLE)
- ✅ get_transactions() - Thêm conditional prepared statement
- ✅ log_transaction() - Thêm conditional prepared statement

**Lỗ hổng fix:**
- SQL Injection (User lookup, Transaction logs) - Lines 15-30

---

### 3. **dashboard.php** (MODIFIED)
**Các thay đổi:**
- ✅ Thêm `require_once 'config.php'`
- ✅ Loan summary query - Conditional prepared statement
- ✅ Search input value - Conditional htmlspecialchars() escaping (Line 51)
- ✅ Search display text - Conditional htmlspecialchars() escaping (Line 73)

**Lỗ hổng fix:**
- Reflected XSS (Search functionality) - Lines 51, 73
- SQL Injection (Loan query) - Line 15

---

### 4. **transfer.php** (MODIFIED)
**Các thay đổi:**
- ✅ Thêm `require_once 'config.php'`
- ✅ Account lookup query - Conditional prepared statement
- ✅ Balance update queries - Conditional prepared statements (2 places)
- ✅ Transaction logging - Conditional prepared statement

**Lỗ hổng fix:**
- SQL Injection (Account lookup) - Line 18
- SQL Injection (Balance updates) - Lines 28-36

---

### 5. **bills.php** (MODIFIED)
**Các thay đổi:**
- ✅ Thêm `require_once 'config.php'`
- ✅ Bill payment - Conditional prepared statements (2 queries)
- ✅ Bills list query - Conditional prepared statement

**Lỗ hổng fix:**
- SQL Injection (Category, Biller) - Lines 19-22
- SQL Injection (Bills query) - Line 30

---

### 6. **cards.php** (MODIFIED)
**Các thay đổi:**
- ✅ Thêm `require_once 'config.php'`
- ✅ Create card - Conditional prepared statement
- ✅ Top-up balance - Conditional prepared statements (2 queries)
- ✅ Cards list query - Conditional prepared statement

**Lỗ hổng fix:**
- SQL Injection (Card type, Currency) - Line 17
- SQL Injection (Balance updates) - Lines 28-33
- SQL Injection (Cards query) - Line 43

---

### 7. **profile.php** (MODIFIED)
**Các thay đổi:**
- ✅ Thêm `require_once 'config.php'`
- ✅ Bio update - Conditional:
  - SECURE_MODE=true: htmlspecialchars() + prepared statement
  - SECURE_MODE=false: Direct SQL (VULNERABLE)
- ✅ Avatar upload - Conditional:
  - SECURE_MODE=true: File extension & MIME type validation
  - SECURE_MODE=false: No validation (VULNERABLE - RCE risk)
- ✅ Bio display (textarea) - Conditional htmlspecialchars()
- ✅ Bio display (preview) - Conditional htmlspecialchars()

**Lỗ hổng fix:**
- Stored XSS (Bio) - Lines 10-18, 118
- SQL Injection (Bio) - Line 10-18
- File Upload RCE (Avatar) - Lines 21-45

---

### 8. **loans.php** (MODIFIED)
**Các thay đổi:**
- ✅ Thêm `require_once 'config.php'`
- ✅ Loan request - Conditional prepared statement
- ✅ Loans list query - Conditional prepared statement

**Lỗ hổng fix:**
- SQL Injection (Loan amount) - Line 11
- SQL Injection (Loans query) - Line 26

---

### 9. **docker-compose.yml** (MODIFIED)
**Các thay đổi:**
- ✅ Thêm environment variable vào `web` service:
```yaml
environment:
  SECURE_MODE: "false"  # Change to "true" for secure mode
```

**Mục đích:** Control SECURE_MODE thông qua Docker environment variable.

---

### 10. **setup.sql** (MODIFIED)
**Các thay đổi:**
- ✅ Sắp xếp lại thứ tự: ALTER TABLE → INSERT (thay vì UPDATE trước INSERT)
- ✅ Thêm role, account_number vào INSERT statement thay vì UPDATE

**Lý do:** Tránh UPDATE/INSERT conflict khi lần đầu chạy.

---

## Security Improvements Summary

| Lỗ hổng | Vị trí | Vulnerable Mode | Secure Mode |
|--------|--------|-----------------|-------------|
| **SQL Injection - Login** | index.php | String interpolation | Prepared statement |
| **SQL Injection - Account lookup** | transfer.php | String interpolation | Prepared statement |
| **SQL Injection - Balance update** | transfer.php, cards.php | String interpolation | Prepared statement |
| **SQL Injection - Category/Biller** | bills.php | String interpolation | Prepared statement |
| **SQL Injection - Card type/Currency** | cards.php | String interpolation | Prepared statement |
| **SQL Injection - Loan amount** | loans.php | String interpolation | Prepared statement |
| **SQL Injection - User/Transaction queries** | functions.php | String interpolation | Prepared statement |
| **Reflected XSS - Search** | dashboard.php | Direct echo | htmlspecialchars() |
| **Stored XSS - Bio** | profile.php | Direct echo | htmlspecialchars() |
| **File Upload RCE** | profile.php | No validation | Extension & MIME check |
| **Cookie Security** | index.php | httponly=false | httponly=true, secure, samesite |

---

## How to Toggle Modes

### Option 1: Environment Variable (Recommended)
```bash
# Run in Secure Mode
SECURE_MODE=true docker-compose up --build

# Run in Vulnerable Mode
SECURE_MODE=false docker-compose up --build
```

### Option 2: Edit docker-compose.yml
```yaml
environment:
  SECURE_MODE: "true"   # Change to false for vulnerable
```

Then:
```bash
docker-compose down && docker-compose up --build
```

### Option 3: Use Helper Script
```bash
chmod +x toggle-mode.sh
./toggle-mode.sh secure      # Chạy secure
./toggle-mode.sh vulnerable  # Chạy vulnerable
./toggle-mode.sh status      # Xem trạng thái hiện tại
```

---

## Code Pattern Used

Tất cả các file đều sử dụng pattern sau:

```php
if (SECURE_MODE) {
    // SECURE: Prepared statement hoặc htmlspecialchars()
    // Example:
    // $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    // $stmt->bind_param("i", $user_id);
    // $stmt->execute();
    // $result = $stmt->get_result();
} else {
    // VULNERABLE: Direct SQL interpolation hoặc echo
    // Example:
    // $sql = "SELECT * FROM users WHERE id = $user_id";
    // $result = $conn->query($sql);
}
```

---

## Testing the Implementation

### Test Vulnerable Mode (SECURE_MODE=false)
1. Login: Try `' OR '1'='1` - SQL Injection sẽ thành công
2. Dashboard: Try `<script>alert(1)</script>` trong search - XSS sẽ thành công
3. Profile: Upload `.php` file - File Upload sẽ thành công
4. Transfer/Bills: Thử SQL injection trong các fields

### Test Secure Mode (SECURE_MODE=true)
1. Login: `' OR '1'='1` - Sẽ bị block, coi như sai password
2. Dashboard: `<script>alert(1)</script>` - Sẽ bị HTML encode
3. Profile: Upload `.php` file - Sẽ bị reject
4. Transfer/Bills: SQL injection sẽ không hoạt động

---

## Files Summary

| File | Status | Purpose |
|------|--------|---------|
| config.php | NEW | SECURE_MODE configuration |
| index.php | MODIFIED | Login + Cookie security |
| functions.php | MODIFIED | DB functions + Prepared statements |
| dashboard.php | MODIFIED | XSS + SQL injection fixes |
| transfer.php | MODIFIED | SQL injection fixes |
| bills.php | MODIFIED | SQL injection fixes |
| cards.php | MODIFIED | SQL injection fixes |
| profile.php | MODIFIED | XSS + SQL injection + File upload fixes |
| loans.php | MODIFIED | SQL injection fixes |
| docker-compose.yml | MODIFIED | SECURE_MODE environment variable |
| setup.sql | MODIFIED | Fixed insert order |
| SECURE_MODE_README.md | NEW | Documentation |
| toggle-mode.sh | NEW | Helper script |

**Total:** 13 files (3 mới, 10 sửa đổi)

---

## Key Points

✅ **Backward Compatible** - Code không bị break, chỉ toggle giữa 2 chế độ
✅ **No Duplicate Code** - Cùng 1 codebase, chỉ dùng conditional if/else
✅ **Easy to Test** - Dễ dàng chuyển đổi để demo lỗ hổng và fix
✅ **Production Ready** - Trong production, chỉ cần set SECURE_MODE=true
✅ **Educational** - Giáo dục về các lỗ hổng bảo mật và cách fix

