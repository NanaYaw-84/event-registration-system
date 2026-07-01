<?php
session_start();
require_once __DIR__ . '/../config/database.php';

$reference = isset($_GET['reference']) ? trim($_GET['reference']) : '';

if ($reference === '') {
    die('Missing payment reference.');
}

$secretKey = 'sk_test_93b28b61739321ab24afcc4f25c9e19cab68ef85';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.paystack.co/transaction/verify/' . rawurlencode($reference));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $secretKey,
    'Content-Type: application/json',
    'Cache-Control: no-cache'
]);

$response = curl_exec($ch);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    $message = 'Unable to verify payment at the moment.';
    $messageType = 'danger';
} else {
    $responseData = json_decode($response, true);

    if (isset($responseData['status']) && $responseData['status'] === true && isset($responseData['data']['status']) && $responseData['data']['status'] === 'success') {
        $updateStmt = $conn->prepare('UPDATE registrations SET payment_status = ?, payment_reference = ? WHERE payment_reference = ? OR payment_reference IS NULL');
        $paidStatus = 'paid';
        $updateStmt->bind_param('sss', $paidStatus, $reference, $reference);
        $updateStmt->execute();
        $updateStmt->close();

        $message = 'Payment Successful. Registration Confirmed.';
        $messageType = 'success';
    } else {
        $message = 'Payment could not be completed.';
        $messageType = 'danger';
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body p-4 text-center">
                    <h3 class="mb-3">Payment Result</h3>
                    <div class="alert alert-<?php echo htmlspecialchars($messageType); ?>" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                    <a href="../dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
