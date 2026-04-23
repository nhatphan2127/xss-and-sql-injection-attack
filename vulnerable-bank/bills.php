<?php
require_once 'functions.php';
redirect_if_not_logged_in();

$user = get_user_data($conn, $_SESSION['user_id']);
$message = '';

$bill_categories = [
    'Utilities' => ['Electricity', 'Water', 'Internet'],
    'Insurance' => ['Health Insurance', 'Car Insurance'],
    'Entertainment' => ['Netflix', 'Spotify', 'Gaming']
];

if (isset($_POST['pay_bill'])) {
    $category = $_POST['category'];
    $biller = $_POST['biller'];
    $amount = (float)$_POST['amount'];
    $method = $_POST['payment_method'];
    
    if ($user['balance'] >= $amount) {
        $conn->query("UPDATE users SET balance = balance - $amount WHERE id = {$user['id']}");
        $conn->query("INSERT INTO bills (user_id, category, biller, amount, status) VALUES ({$user['id']}, '$category', '$biller', $amount, 'paid')");
        log_transaction($conn, $user['id'], 'debit', "Bill Payment: $biller", $amount);
        $message = "Bill paid successfully!";
        $user = get_user_data($conn, $user['id']);
    } else {
        $message = "Insufficient balance!";
    }
}

$bills = $conn->query("SELECT * FROM bills WHERE user_id = {$user['id']} ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bills - Vulnerable Bank</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/bill.css">
</head>
<body class="light">
    <div class="app-container">
        <?php include 'sidebar.php'; ?>
        
        <main class="main-content">
            <header class="top-bar">
                <div>
                    <h1 class="page-title">Bill Payments</h1>
                    <p class="page-subtitle">Pay your utilities and services instantly</p>
                </div>
            </header>
            
            <?php if ($message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="dashboard-grid">
                <!-- Form thanh toán hóa đơn -->
                <div class="card">
                    <h3 class="card-subtitle">Pay a New Bill</h3>
                    <form method="POST" class="bill-form">
                        <div class="form-group">
                            <label>Category</label>
                            <div class="custom-select-wrapper" id="categoryWrapper">
                                <div class="custom-select-trigger">
                                    <span>Select Category</span>
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                <div class="custom-options">
                                    <?php foreach ($bill_categories as $cat => $billers): ?>
                                        <div class="custom-option" data-value="<?php echo $cat; ?>"><?php echo $cat; ?></div>
                                    <?php endforeach; ?>
                                </div>
                                <input type="hidden" name="category" id="category" value="">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Biller</label>
                            <div class="custom-select-wrapper" id="billerWrapper">
                                <div class="custom-select-trigger">
                                    <span>Select Biller</span>
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                <div class="custom-options" id="billerOptions">
                                    <!-- Sẽ được đổ dữ liệu bởi JS -->
                                </div>
                                <input type="hidden" name="biller" id="biller" value="">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Amount ($)</label>
                            <div class="input-wrapper">
                                <i class="fas fa-dollar-sign input-icon"></i>
                                <input type="number" name="amount" class="search-input custom-input-padding" required step="0.01" placeholder="0.00">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Payment Method</label>
                            <select name="payment_method" class="search-input full-width">
                                <option value="balance">Main Balance ($<?php echo number_format($user['balance'], 2); ?>)</option>
                                <option value="card">Virtual Card</option>
                            </select>
                        </div>

                        <button type="submit" name="pay_bill" class="btn btn-primary full-width mt-1">
                            <i class="fas fa-receipt"></i> Pay Now
                        </button>
                    </form>
                </div>

                <!-- Bảng lịch sử thanh toán -->
                <div class="card col-span-2">
                    <h3 class="card-subtitle">Payment History</h3>
                    <div class="table-container">
                        <table class="bill-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Biller</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($bills->num_rows > 0): ?>
                                    <?php while ($b = $bills->fetch_assoc()): ?>
                                    <tr>
                                        <td class="text-muted"><?php echo date('M d, Y', strtotime($b['created_at'])); ?></td>
                                        <td class="font-semibold"><?php echo $b['biller']; ?></td>
                                        <td class="font-bold">$<?php echo number_format($b['amount'], 2); ?></td>
                                        <td><span class="status-pill status-pill-success">PAID</span></td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No bill payments found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
    const billData = <?php echo json_encode($bill_categories); ?>;

    // Hàm xử lý đóng mở tất cả custom select
    document.querySelectorAll('.custom-select-trigger').forEach(trigger => {
        trigger.addEventListener('click', function() {
            this.parentElement.classList.toggle('open');
        });
    });

    // Xử lý chọn Category
    document.querySelectorAll('#categoryWrapper .custom-option').forEach(option => {
        option.addEventListener('click', function() {
            const value = this.getAttribute('data-value');
            const wrapper = document.getElementById('categoryWrapper');
            
            // Cập nhật giao diện
            wrapper.querySelector('.custom-select-trigger span').innerText = value;
            wrapper.classList.remove('open');
            document.getElementById('category').value = value;
            
            updateBillerOptions(value);
        });
    });

    function updateBillerOptions(cat) {
        const billerOptions = document.getElementById('billerOptions');
        const billerTrigger = document.querySelector('#billerWrapper .custom-select-trigger span');
        
        billerOptions.innerHTML = '';
        billerTrigger.innerText = 'Select Biller'; // Reset text
        
        if(billData[cat]) {
            billData[cat].forEach(b => {
                const div = document.createElement('div');
                div.className = 'custom-option';
                div.innerText = b;
                div.addEventListener('click', function() {
                    billerTrigger.innerText = b;
                    document.getElementById('biller').value = b;
                    document.getElementById('billerWrapper').classList.remove('open');
                });
                billerOptions.appendChild(div);
            });
        }
    }

    // Đóng menu khi click ra ngoài
    window.addEventListener('click', function(e) {
        if (!e.target.closest('.custom-select-wrapper')) {
            document.querySelectorAll('.custom-select-wrapper').forEach(w => w.classList.remove('open'));
        }
    });
    </script>
</body>
</html>
