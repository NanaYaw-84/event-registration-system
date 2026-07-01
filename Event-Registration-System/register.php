<?php
session_start();
require_once 'config/database.php';

$message = '';
$messageType = '';
$event = null;
$eventId = null;

if (isset($_GET['event_id'])) {
    $eventId = filter_input(INPUT_GET, 'event_id', FILTER_VALIDATE_INT);

    if ($eventId === false || $eventId <= 0) {
        $message = 'Invalid event selected.';
        $messageType = 'danger';
    } else {
        if (!isset($_SESSION['user_id'])) {
            header('Location: login.php?redirect=' . urlencode('register.php?event_id=' . $eventId));
            exit;
        }

        $stmt = $conn->prepare('SELECT id, title, description, venue, event_date, price FROM events WHERE id = ?');
        $stmt->bind_param('i', $eventId);
        $stmt->execute();
        $result = $stmt->get_result();
        $event = $result->fetch_assoc();
        $stmt->close();

        if (!$event) {
            $message = 'The selected event could not be found.';
            $messageType = 'danger';
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = (int) $_SESSION['user_id'];
            $checkStmt = $conn->prepare('SELECT id FROM registrations WHERE user_id = ? AND event_id = ?');
            $checkStmt->bind_param('ii', $userId, $eventId);
            $checkStmt->execute();
            $checkStmt->store_result();

            if ($checkStmt->num_rows > 0) {
                $message = 'You have already registered for this event.';
                $messageType = 'warning';
            } else {
                $pendingStatus = 'pending';
                $insertStmt = $conn->prepare('INSERT INTO registrations (user_id, event_id, payment_status) VALUES (?, ?, ?)');
                $insertStmt->bind_param('iis', $userId, $eventId, $pendingStatus);

                if ($insertStmt->execute()) {
                    $registrationId = $insertStmt->insert_id;
                    header('Location: payment/initialize.php?registration_id=' . $registrationId);
                    exit;
                } else {
                    $message = 'Unable to create registration. Please try again.';
                    $messageType = 'danger';
                }

                $insertStmt->close();
            }

            $checkStmt->close();
        }
    }
} else {
    if (isset($_SESSION['user_id'])) {
        header('Location: dashboard.php');
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $fullname = trim($_POST['fullname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if ($fullname === '' || $email === '' || $phone === '' || $password === '') {
            $message = 'All fields are required.';
            $messageType = 'danger';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Please enter a valid email address.';
            $messageType = 'danger';
        } elseif (!preg_match('/^[0-9+\-\s]{7,15}$/', $phone)) {
            $message = 'Please enter a valid phone number.';
            $messageType = 'danger';
        } elseif (strlen($password) < 6) {
            $message = 'Password must be at least 6 characters long.';
            $messageType = 'danger';
        } else {
            $checkStmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
            $checkStmt->bind_param('s', $email);
            $checkStmt->execute();
            $checkStmt->store_result();

            if ($checkStmt->num_rows > 0) {
                $message = 'An account with that email already exists.';
                $messageType = 'warning';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $insertStmt = $conn->prepare('INSERT INTO users (fullname, email, phone, password) VALUES (?, ?, ?, ?)');
                $insertStmt->bind_param('ssss', $fullname, $email, $phone, $hashedPassword);

                if ($insertStmt->execute()) {
                    $message = 'Account created successfully. You can now log in.';
                    $messageType = 'success';
                } else {
                    $message = 'Registration failed. Please try again.';
                    $messageType = 'danger';
                }

                $insertStmt->close();
            }

            $checkStmt->close();
        }
    }
}

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7 col-md-9">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-body p-4 p-md-5">
                    <?php if ($event !== null): ?>
                        <div class="text-center mb-4">
                            <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                                <i class="bi bi-calendar2-event text-primary" style="font-size: 1.6rem;"></i>
                            </div>
                            <h3 class="fw-bold mt-3 mb-2">Event Registration</h3>
                            <p class="text-muted mb-0">Complete your booking securely</p>
                        </div>

                        <?php if (!empty($message)): ?>
                            <div class="alert alert-<?php echo htmlspecialchars($messageType); ?> rounded-3" role="alert">
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endif; ?>

                        <div class="border rounded-4 p-4 mb-4 bg-light">
                            <h4 class="mb-3"><?php echo htmlspecialchars($event['title']); ?></h4>
                            <p class="text-muted"><?php echo htmlspecialchars($event['description']); ?></p>
                            <p class="mb-2"><strong>Venue:</strong> <?php echo htmlspecialchars($event['venue']); ?></p>
                            <p class="mb-2"><strong>Date:</strong> <?php echo htmlspecialchars($event['event_date']); ?></p>
                            <p class="mb-0"><strong>Price:</strong> $<?php echo htmlspecialchars($event['price']); ?></p>
                        </div>

                        <form method="post">
                            <button type="submit" class="btn btn-primary w-100 rounded-3">Proceed to Payment</button>
                        </form>
                    <?php else: ?>
                        <div class="text-center mb-4">
                            <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                                <i class="bi bi-person-plus text-primary" style="font-size: 1.6rem;"></i>
                            </div>
                            <h3 class="fw-bold mt-3 mb-2">Create an Account</h3>
                            <p class="text-muted mb-0">Join our event platform in minutes</p>
                        </div>

                        <?php if (!empty($message)): ?>
                            <div class="alert alert-<?php echo htmlspecialchars($messageType); ?> rounded-3" role="alert">
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endif; ?>

                        <form method="post">
                            <div class="mb-3">
                                <label for="fullname" class="form-label">Full Name</label>
                                <input type="text" class="form-control rounded-3" id="fullname" name="fullname" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control rounded-3" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="text" class="form-control rounded-3" id="phone" name="phone" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control rounded-3" id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 rounded-3">Register</button>
                        </form>

                        <p class="text-center mt-3 mb-0">
                            Already have an account? <a href="login.php">Login here</a>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
