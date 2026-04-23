<?php
require_once 'config.php';
require_once 'functions.php';
redirect_if_not_logged_in();

$user = get_user_data($conn, $_SESSION['user_id']);
$message = '';

// Update Bio (Stored XSS)
if (isset($_POST['update_bio'])) {
    $bio = $_POST['bio']; // User input
    
    if (SECURE_MODE) {
        // SECURE: Sanitize input and use prepared statement
        $bio_sanitized = htmlspecialchars($bio, ENT_QUOTES, 'UTF-8');
        $stmt = $conn->prepare("UPDATE users SET profile_bio = ? WHERE id = ?");
        $stmt->bind_param("si", $bio_sanitized, $user['id']);
        $stmt->execute();
    } else {
        // VULNERABLE: No sanitization - allows Stored XSS
        $conn->query("UPDATE users SET profile_bio = '$bio' WHERE id = {$user['id']}");
    }
    $message = "Bio updated successfully!";
    $user = get_user_data($conn, $user['id']);
}

// Avatar Upload (Unrestricted File Upload -> RCE)
if (isset($_FILES['avatar']) && $_FILES['avatar']['name']) {
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) mkdir($target_dir);
    
    $target_file = $target_dir . basename($_FILES["avatar"]["name"]);
    
    if (SECURE_MODE) {
        // SECURE: Validate file extension and type
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        $file_extension = strtolower(pathinfo($_FILES["avatar"]["name"], PATHINFO_EXTENSION));
        $allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_mime = mime_content_type($_FILES["avatar"]["tmp_name"]);
        
        if (!in_array($file_extension, $allowed_extensions) || !in_array($file_mime, $allowed_mime_types)) {
            $message = "Only image files (JPG, PNG, GIF) are allowed!";
        } else if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $target_file)) {
            $avatar_name = basename($_FILES["avatar"]["name"]);
            $stmt = $conn->prepare("UPDATE users SET avatar = ? WHERE id = ?");
            $stmt->bind_param("si", $avatar_name, $user['id']);
            $stmt->execute();
            $message = "Avatar updated!";
            $user = get_user_data($conn, $user['id']);
        } else {
            $message = "Upload failed!";
        }
    } else {
        // VULNERABLE: No check on file extension or type - allows RCE via .php upload
        if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $target_file)) {
            $conn->query("UPDATE users SET avatar = '{$_FILES["avatar"]["name"]}' WHERE id = {$user['id']}");
            $message = "Avatar updated!";
            $user = get_user_data($conn, $user['id']);
        } else {
            $message = "Upload failed!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Vulnerable Bank</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</head>
<body class="light">
    <div class="app-container">
        <?php include 'sidebar.php'; ?>
        
        <main class="main-content">
            <header class="top-bar">
                <h1>My Profile</h1>
                <a href="logout.php" class="btn btn-primary" style="background: var(--danger);">Logout</a>
            </header>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="dashboard-grid">
                <div class="card">
                    <div style="text-align: center; margin-bottom: 25px;">
                        <div style="position: relative; display: inline-block;">
                            <img src="<?php echo strpos($user['avatar'], 'uploads/') === 0 ? $user['avatar'] : 'uploads/' . $user['avatar']; ?>" 
                                 alt="Avatar" style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 5px solid white; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                            <form id="avatarForm" method="POST" enctype="multipart/form-data" style="position: absolute; bottom: 5px; right: 5px;">
                                <label for="avatarInput" class="btn btn-primary" style="width: 40px; height: 40px; border-radius: 50%; padding: 0; display: flex; align-items: center; justify-content: center; cursor: pointer;">
                                    <i class="fas fa-camera"></i>
                                </label>
                                <input type="file" id="avatarInput" name="avatar" style="display: none;" onchange="document.getElementById('avatarForm').submit()">
                            </form>
                        </div>
                        <h2 style="margin: 15px 0 5px;"><?php echo $user['username']; ?></h2>
                        <span style="background: var(--primary); color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase;">
                            <?php echo $user['role']; ?>
                        </span>
                    </div>
                    
                    <div style="border-top: 1px solid #eee; padding-top: 20px;">
                        <p><strong>Account:</strong> <?php echo $user['account_number']; ?></p>
                        <p><strong>Email:</strong> <?php echo $user['username']; ?>@vulnerablebank.com</p>
                    </div>
                </div>

                <div class="card card-info">
                    <h3>About Me</h3>
                    <form method="POST">
                        <div class="form-group">
                            <label>Profile Bio (HTML supported)</label>
                            <textarea name="bio" rows="6" class="search-input" style="width: 100%; border-radius: 10px; height: auto;" placeholder="Tell us about yourself..."><?php echo SECURE_MODE ? htmlspecialchars($user['profile_bio'], ENT_QUOTES, 'UTF-8') : $user['profile_bio']; ?></textarea>
                        </div>
                        <button type="submit" name="update_bio" class="btn btn-primary">Save Profile</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <h3>Profile Preview</h3>
                <div style="padding: 20px; background: #f8f9fc; border-radius: 10px; border: 1px solid #e3e6f0;">
                    <!-- VULNERABLE: Stored XSS -->
                    <div class="bio-display">
                        <?php 
                            // Display bio with or without escaping based on SECURE_MODE
                            if (SECURE_MODE) {
                                echo htmlspecialchars($user['profile_bio'], ENT_QUOTES, 'UTF-8');
                            } else {
                                echo $user['profile_bio']; // VULNERABLE: Stored XSS
                            }
                        ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
