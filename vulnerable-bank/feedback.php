<?php
require_once 'config.php';
require_once 'functions.php';
redirect_if_not_logged_in();

$user = get_user_data($conn, $_SESSION['user_id']);

// Handle feedback submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['feedback_message'])) {
    $message = $_POST['feedback_message'];
    $user_id = $user['id'];

    if (SECURE_MODE) {
        // SECURE: Use prepared statements to prevent SQL Injection
        $stmt = $conn->prepare("INSERT INTO feedback (user_id, message) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $message);
        $stmt->execute();
    } else {
        // VULNERABLE: Direct SQL injection possible
        $sql = "INSERT INTO feedback (user_id, message) VALUES ($user_id, '$message')";
        $conn->query($sql);
    }
    header("Location: feedback.php?success=1");
    exit();
}

// Fetch all feedback
$feedback_list = $conn->query("SELECT f.*, u.username FROM feedback f JOIN users u ON f.user_id = u.id ORDER BY f.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback | V-Bank</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</head>
<body class="light">
    <div class="app-container">
        <?php include 'sidebar.php'; ?>
        
        <main class="main-content">
            <header class="top-bar">
                <div>
                    <h1 style="font-size: 2rem;">Feedback</h1>
                    <p style="color: var(--text-muted); font-weight: 500;">Tell us what you think about our application.</p>
                </div>
            </header>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    Thank you for your feedback!
                </div>
            <?php endif; ?>

            <div class="card">
                <h3 style="margin-bottom: 1.5rem;"><i class="fas fa-comment-alt" style="margin-right: 0.5rem; color: var(--primary);"></i> Submit Feedback</h3>
                <form method="POST">
                    <div class="form-group">
                        <textarea name="feedback_message" class="form-input" placeholder="Your feedback..." rows="4" required style="width: 100%; padding: 1rem; border-radius: 0.5rem; border: 1px solid var(--border-color); background: var(--bg-main); color: var(--text-main); font-family: inherit;"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">
                        <i class="fas fa-paper-plane"></i> Send Feedback
                    </button>
                </form>
            </div>

            <div class="card" style="margin-top: 2rem;">
                <h3 style="margin-bottom: 1.5rem;"><i class="fas fa-comments" style="margin-right: 0.5rem; color: var(--info);"></i> Recent Feedback</h3>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php while ($f = $feedback_list->fetch_assoc()): ?>
                        <div style="padding: 1rem; border-radius: 0.75rem; background: var(--bg-main); border: 1px solid var(--border-color);">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                <span style="font-weight: 700; color: var(--primary);"><?php echo $f['username']; ?></span>
                                <span style="font-size: 0.75rem; color: var(--text-muted);"><?php echo date('M d, Y H:i', strtotime($f['created_at'])); ?></span>
                            </div>
                            <div style="color: var(--text-main); line-height: 1.5;">
                                <?php 
                                    // Stored XSS Toggle
                                    if (SECURE_MODE) {
                                        // SECURE: Escape output to prevent Stored XSS
                                        echo htmlspecialchars($f['message'], ENT_QUOTES, 'UTF-8');
                                    } else {
                                        // VULNERABLE: Direct echo - Stored XSS
                                        echo $f['message'];
                                    }
                                ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
    if (localStorage.getItem('theme') === 'dark') {
        document.body.classList.add('dark-mode');
    }
    </script>
</body>
</html>
