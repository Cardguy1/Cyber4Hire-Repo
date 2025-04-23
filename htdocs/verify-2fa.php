<?php
session_start();
require_once 'includes/GoogleAuthenticator.php';

$userId = $_SESSION['user_id'];

$conn = new mysqli("sqlXXX.epizy.com", "your_user", "your_pass", "your_db");
$stmt = $conn->prepare("SELECT auth_secret FROM users WHERE id=?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($secret);
$stmt->fetch();
$stmt->close();
$conn->close();

$g = new PHPGangsta_GoogleAuthenticator();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['code'];

    $isValid = $g->verifyCode($secret, $code, 2);

    if ($isValid) {
        echo "<h3>✅ Verified! You’re logged in with 2FA.</h3>";
        $_SESSION['verified_2fa'] = true;
    } else {
        echo "<h3>❌ Invalid code. Try again.</h3>";
    }
}
?>

<form method="POST">
    <label>Enter 2FA Code:</label>
    <input type="text" name="code" required>
    <button type="submit">Verify</button>
</form>
