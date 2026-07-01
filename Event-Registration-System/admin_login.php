<?php
session_start();
require_once 'config/database.php';

if (isset($_SESSION['admin_id'])) {
    header('Location: admin_dashboard.php');
    exit;
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $message = 'Email and password are required.';
        $messageType = 'danger';
    } else {
        $adminCountStmt = $conn->prepare('SELECT COUNT(*) AS admin_count FROM users WHERE LOWER(TRIM(COALESCE(role, ""))) = "admin"');
        $adminCountStmt->execute();
        $adminCountResult = $adminCountStmt->get_result();
        $adminCount = (int) ($adminCountResult->fetch_assoc()['admin_count'] ?? 0);
        $adminCountStmt->close();

        $stmt = $conn->prepare('SELECT id, fullname, password, role FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();
        $stmt->close();

        if ($admin && password_verify($password, $admin['password'])) {
            $role = strtolower(trim((string) ($admin['role'] ?? '')));
            $isAdminAccount = $role === 'admin' || $role === 'administrator' || $adminCount === 0;

            if ($isAdminAccount) {
                if ($role !== 'admin' && $role !== 'administrator') {
                    $promotionStmt = $conn->prepare('UPDATE users SET role = ? WHERE id = ?');
                    $newRole = 'admin';
                    $promotionStmt->bind_param('si', $newRole, $admin['id']);
                    $promotionStmt->execute();
                    $promotionStmt->close();
                }

                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['fullname'];
                header('Location: admin_dashboard.php');
                exit;
            }
        }

        $message = 'Invalid admin credentials.';
        $messageType = 'danger';
    }
}

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-7">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                            <i class="bi bi-shield-lock text-primary" style="font-size: 1.6rem;"></i>
                        </div>
                        <h3 class="fw-bold mt-3 mb-2">Admin Login</h3>
                        <p class="text-muted mb-0">Access the event management dashboard</p>
                    </div>

                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo htmlspecialchars($messageType); ?> rounded-3" role="alert">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="mb-3">
                            <label for="email" class="form-label">Admin Email</label>
                            <input type="email" class="form-control rounded-3" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control rounded-3" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 rounded-3">Login</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
