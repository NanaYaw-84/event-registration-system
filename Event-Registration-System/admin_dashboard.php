<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_event') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $venue = trim($_POST['venue'] ?? '');
        $eventDate = trim($_POST['event_date'] ?? '');
        $price = (float) ($_POST['price'] ?? 0);

        if ($title === '' || $description === '' || $venue === '' || $eventDate === '') {
            $message = 'All event fields are required.';
            $messageType = 'danger';
        } else {
            $stmt = $conn->prepare('INSERT INTO events (title, description, venue, event_date, price) VALUES (?, ?, ?, ?, ?)');
            $stmt->bind_param('ssssd', $title, $description, $venue, $eventDate, $price);
            if ($stmt->execute()) {
                $message = 'Event added successfully.';
                $messageType = 'success';
            } else {
                $message = 'Failed to add event.';
                $messageType = 'danger';
            }
            $stmt->close();
        }
    }

    if ($_POST['action'] === 'delete_event') {
        $eventId = (int) ($_POST['event_id'] ?? 0);
        $stmt = $conn->prepare('DELETE FROM events WHERE id = ?');
        $stmt->bind_param('i', $eventId);
        if ($stmt->execute()) {
            $message = 'Event deleted successfully.';
            $messageType = 'success';
        } else {
            $message = 'Failed to delete event.';
            $messageType = 'danger';
        }
        $stmt->close();
    }
}

$eventsResult = $conn->query('SELECT * FROM events ORDER BY event_date ASC');
$events = $eventsResult ? $eventsResult->fetch_all(MYSQLI_ASSOC) : [];

$registrationsResult = $conn->query('SELECT r.id, r.payment_status, r.payment_reference, r.created_at, u.fullname, e.title FROM registrations r JOIN users u ON r.user_id = u.id JOIN events e ON r.event_id = e.id ORDER BY r.created_at DESC');
$registrations = $registrationsResult ? $registrationsResult->fetch_all(MYSQLI_ASSOC) : [];

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Admin Dashboard</h2>
            <p class="text-muted mb-0">Manage events and registrations</p>
        </div>
        <a href="logout.php" class="btn btn-outline-danger rounded-3">Logout</a>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo htmlspecialchars($messageType); ?> rounded-3" role="alert">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-4">
                    <h4 class="fw-bold mb-3">Add New Event</h4>
                    <form method="post">
                        <input type="hidden" name="action" value="add_event">
                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" class="form-control rounded-3" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control rounded-3" name="description" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Venue</label>
                            <input type="text" class="form-control rounded-3" name="venue" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date</label>
                            <input type="date" class="form-control rounded-3" name="event_date" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price</label>
                            <input type="number" step="0.01" class="form-control rounded-3" name="price" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 rounded-3">Add Event</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm border-0 rounded-4 mb-4">
                <div class="card-body p-4">
                    <h4 class="fw-bold mb-3">Events</h4>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Venue</th>
                                    <th>Date</th>
                                    <th>Price</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($events as $event): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($event['title']); ?></td>
                                        <td><?php echo htmlspecialchars($event['venue']); ?></td>
                                        <td><?php echo htmlspecialchars($event['event_date']); ?></td>
                                        <td>$<?php echo htmlspecialchars($event['price']); ?></td>
                                        <td>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="action" value="delete_event">
                                                <input type="hidden" name="event_id" value="<?php echo (int) $event['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-3">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-4">
                    <h4 class="fw-bold mb-3">Registrations</h4>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Event</th>
                                    <th>Status</th>
                                    <th>Reference</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($registrations as $registration): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($registration['fullname']); ?></td>
                                        <td><?php echo htmlspecialchars($registration['title']); ?></td>
                                        <td>
                                            <?php if ($registration['payment_status'] === 'paid'): ?>
                                                <span class="badge bg-success">Paid</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($registration['payment_reference'] ?? '—'); ?></td>
                                        <td><?php echo htmlspecialchars($registration['created_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
