<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];

$stmt = $conn->prepare('SELECT id, fullname, email, phone, created_at FROM users WHERE id = ?');
$stmt->bind_param('i', $userId);
$stmt->execute();
$userResult = $stmt->get_result();
$user = $userResult->fetch_assoc();
$stmt->close();

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$historyStmt = $conn->prepare('SELECT r.id, r.payment_status, r.payment_reference, r.created_at, e.title, e.venue, e.event_date, e.price FROM registrations r JOIN events e ON r.event_id = e.id WHERE r.user_id = ? ORDER BY r.created_at DESC');
$historyStmt->bind_param('i', $userId);
$historyStmt->execute();
$historyResult = $historyStmt->get_result();
$history = $historyResult->fetch_all(MYSQLI_ASSOC);
$historyStmt->close();

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="container py-5">
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-body p-4">
                    <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                        <i class="bi bi-person-circle text-primary" style="font-size: 1.7rem;"></i>
                    </div>
                    <h3 class="fw-bold mt-3 mb-2"><?php echo htmlspecialchars($user['fullname']); ?></h3>
                    <p class="text-muted mb-3">Member since <?php echo htmlspecialchars(date('M d, Y', strtotime($user['created_at']))); ?></p>

                    <ul class="list-unstyled small">
                        <li class="mb-2"><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></li>
                        <li class="mb-2"><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone']); ?></li>
                    </ul>

                    <a href="dashboard.php" class="btn btn-outline-primary w-100 rounded-3">Back to Dashboard</a>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="fw-bold mb-1">My Bookings</h4>
                            <p class="text-muted mb-0">Track your event registrations and payment status.</p>
                        </div>
                    </div>

                    <?php if (!empty($history)): ?>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Event</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Reference</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($history as $item): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($item['title']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($item['venue']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($item['event_date']); ?></td>
                                            <td>$<?php echo htmlspecialchars($item['price']); ?></td>
                                            <td>
                                                <?php if ($item['payment_status'] === 'paid'): ?>
                                                    <span class="badge bg-success">Paid</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($item['payment_reference'] ?? '—'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-light rounded-3 border" role="alert">
                            You have not booked any events yet.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
