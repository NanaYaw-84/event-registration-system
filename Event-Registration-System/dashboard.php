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

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-md-10">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-body p-4 p-md-5 text-center">
                    <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center" style="width: 72px; height: 72px;">
                        <i class="bi bi-person-circle text-primary" style="font-size: 2rem;"></i>
                    </div>
                    <h2 class="fw-bold mt-3 mb-2">Welcome, <?php echo htmlspecialchars($_SESSION['fullname']); ?>!</h2>
                    <p class="text-muted">You are logged in to the Event Registration System.</p>

                    <div class="d-grid gap-2 d-md-flex justify-content-center mt-4">
                        <a href="events.php" class="btn btn-primary px-4 rounded-3">View Events</a>
                        <a href="profile.php" class="btn btn-outline-primary px-4 rounded-3">My Profile</a>
                        <a href="logout.php" class="btn btn-outline-danger px-4 rounded-3">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
