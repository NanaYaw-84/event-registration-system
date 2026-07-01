<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'config/database.php';
require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body p-4 text-center">
                    <h2 class="mb-3">Welcome, <?php echo htmlspecialchars($_SESSION['fullname']); ?>!</h2>
                    <p class="text-muted">You are logged in to the Event Registration System.</p>

                    <div class="d-grid gap-2 d-md-flex justify-content-center mt-4">
                        <a href="events.php" class="btn btn-primary">View Events</a>
                        <a href="logout.php" class="btn btn-outline-danger">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
