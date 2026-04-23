<?php
require_once 'functions.php';
redirect_if_not_logged_in();

$user = get_user_data($conn, $_SESSION['user_id']);
$message = '';

if (isset($_POST['request_loan'])) {
    $amount = (float)$_POST['amount'];
    // VULNERABLE: Direct numeric injection or logic bypass (negative amount?)
    $conn->query("INSERT INTO loans (user_id, amount, status) VALUES ({$user['id']}, $amount, 'pending')");
    $message = "Loan request submitted! Status: PENDING";
}

$loans = $conn->query("SELECT * FROM loans WHERE user_id = {$user['id']} ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Loans - Vulnerable Bank</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</head>
<body class="light">
    <div class="app-container">
        <?php include 'sidebar.php'; ?>
        
        <main class="main-content">
            <h1>Loan Management</h1>
            
            <?php if ($message): ?>
                <div class="card text-success">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="dashboard-grid">
                <div class="card">
                    <h3>Request a Loan</h3>
                    <form method="POST">
                        <div class="form-group">
                            <label>Amount ($)</label>
                            <input type="number" name="amount" required min="1">
                        </div>
                        <button type="submit" name="request_loan" class="btn btn-primary btn-submit">🏦 Request Now</button>
                    </form>
                </div>

                <div class="card">
                    <h3>Loan Status</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($loans && $loans->num_rows > 0): ?>
                                <?php while ($l = $loans->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($l['created_at'])); ?></td>
                                    <td>$<?php echo number_format($l['amount'], 2); ?></td>
                                    <td style="color: <?php echo $l['status'] == 'approved' ? 'green' : ($l['status'] == 'rejected' ? 'red' : 'orange'); ?>">
                                        <?php echo strtoupper($l['status']); ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="3">No loan requests found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="card">
                <h3>Transaction History</h3>
                <?php
                $transactions = get_transactions($conn, $user['id']);
                ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Type</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($t = $transactions->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('M d, Y H:i', strtotime($t['created_at'])); ?></td>
                            <td><?php echo $t['description']; ?></td>
                            <td class="<?php echo $t['type'] == 'credit' ? 'text-success' : 'text-danger'; ?>">
                                <?php echo strtoupper($t['type']); ?>
                            </td>
                            <td>$<?php echo number_format($t['amount'], 2); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
