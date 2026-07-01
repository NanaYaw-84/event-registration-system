<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$registrationId = filter_input(INPUT_GET, 'registration_id', FILTER_VALIDATE_INT);

if ($registrationId === false || $registrationId <= 0) {
    die('Invalid registration request.');
}

$stmt = $conn->prepare('SELECT r.id, r.user_id, r.event_id, r.payment_status, e.title, e.price, u.email, u.fullname, u.phone FROM registrations r JOIN users u ON r.user_id = u.id JOIN events e ON r.event_id = e.id WHERE r.id = ? AND r.user_id = ?');
$stmt->bind_param('ii', $registrationId, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$registration = $result->fetch_assoc();
$stmt->close();

if (!$registration) {
    die('Registration not found or access denied.');
}

if ($registration['payment_status'] === 'paid') {
    header('Location: callback.php?reference=' . urlencode($registration['id']));
    exit;
}

$amount = (int) round((float) $registration['price'] * 100);
$reference = 'evt_' . $registrationId . '_' . time();
$secretKey = 'sk_test_93b28b61739321ab24afcc4f25c9e19cab68ef85';

if (empty(trim($secretKey))) {
    require_once __DIR__ . '/../includes/header.php';
    require_once __DIR__ . '/../includes/navbar.php';
    echo '<div class="container py-5"><div class="row justify-content-center"><div class="col-lg-6 col-md-8"><div class="card shadow-lg border-0 rounded-4"><div class="card-body p-4 text-center"><div class="text-danger mb-3"><i class="bi bi-shield-exclamation" style="font-size: 2rem;"></i></div><h4 class="fw-bold">Payment configuration missing</h4><p class="text-muted mb-0">Paystack secret key is missing.</p></div></div></div></div></div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$callbackUrl = $scheme . '://' . $host . '/Event-Registration-System/payment/callback.php?reference=' . urlencode($reference);

$payload = [
    'email' => $registration['email'],
    'amount' => $amount,
    'reference' => $reference,
    'callback_url' => $callbackUrl,
    'currency' => 'GHS',
    'channels' => ['mobile_money'],
    'metadata' => [
        'user_id' => (int) $registration['user_id'],
        'event_id' => (int) $registration['event_id'],
        'phone' => $registration['phone'] ?? ''
    ]
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.paystack.co/transaction/initialize');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $secretKey,
    'Content-Type: application/json',
    'Cache-Control: no-cache'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($curlError) {
    require_once __DIR__ . '/../includes/header.php';
    require_once __DIR__ . '/../includes/navbar.php';
    echo '<div class="container py-5"><div class="row justify-content-center"><div class="col-lg-6 col-md-8"><div class="card shadow-lg border-0 rounded-4"><div class="card-body p-4 text-center"><div class="text-danger mb-3"><i class="bi bi-exclamation-triangle" style="font-size: 2rem;"></i></div><h4 class="fw-bold">Payment could not be started</h4><p class="text-muted">' . htmlspecialchars($curlError) . '</p><a href="../dashboard.php" class="btn btn-outline-primary">Back to Dashboard</a></div></div></div></div></div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$responseData = json_decode($response, true);

if (!isset($responseData['status']) || !$responseData['status'] || empty($responseData['data']['authorization_url'])) {
    $message = isset($responseData['message']) ? $responseData['message'] : 'Unable to initialize payment.';
    require_once __DIR__ . '/../includes/header.php';
    require_once __DIR__ . '/../includes/navbar.php';
    echo '<div class="container py-5"><div class="row justify-content-center"><div class="col-lg-6 col-md-8"><div class="card shadow-lg border-0 rounded-4"><div class="card-body p-4 text-center"><div class="text-danger mb-3"><i class="bi bi-x-circle" style="font-size: 2rem;"></i></div><h4 class="fw-bold">Payment failed to initialize</h4><p class="text-muted">' . htmlspecialchars($message) . '</p><a href="../dashboard.php" class="btn btn-outline-primary">Back to Dashboard</a></div></div></div></div></div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$updateStmt = $conn->prepare('UPDATE registrations SET payment_reference = ? WHERE id = ? AND user_id = ?');
$updateStmt->bind_param('sii', $reference, $registrationId, $_SESSION['user_id']);
$updateStmt->execute();
$updateStmt->close();

header('Location: ' . $responseData['data']['authorization_url']);
exit;
