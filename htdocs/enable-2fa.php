<?php
session_start();
require_once 'includes/GoogleAuthenticator.php'; // adjust if needed

// Simulate logged-in user (replace with real user session)
$userId = $_SESSION['user_id'];

$g = new PHPGangsta_GoogleAuthenticator();
$secret = $g->createSecret();

// Save to DB
$conn = new mysqli("sqlXXX.epizy.com", "your_db_user", "your_db_pass", "your_db_name");

$stmt = $conn->prepare("UPDATE users SET auth_secret=? WHERE id=?");
$stmt->bind_param("si", $secret, $userId);
$stmt->execute();
$stmt->close();
$conn->close();

// Show QR code
$qrUrl = $g->getQRCodeGoogleUrl("Cyber4Hire", $secret);
?>
<h2>Scan this QR code with Google Authenticator</h2>
<img src="<?= $qrUrl ?>" />
<p>Secret (in case you need to type it): <b><?= $secret ?></b></p>
