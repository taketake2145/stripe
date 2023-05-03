<?php
require_once  __DIR__ . '/../../../stripe/secrets.php';
require_once  __DIR__ . '/../../../stripe/vendor/autoload.php';

\Stripe\Stripe::setApiKey($stripeSecretKey);

$session_id = $_GET['session_id'];
$login_id = 5;

// セッションを取得して、
$stripe_status = \Stripe\Checkout\Session::retrieve([
  'id' => $session_id
]);

$payment_status = $stripe_status['payment_status'];

// 支払い済みか判別する
if ($payment_status !== 'paid') {

  // TODO ページ遷移
  die();
}


// DBに新規レコードを追加する
// Connect to the database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$stmt = $conn->prepare("UPDATE checkout_sessions SET cp_status=?, customer_id=?, subscription_id=?, updated_at=NOW() WHERE cp_status='PENDING' and user_id=? and session_id=?");

$stmt->bind_param("sssis", $cp_status, $customer_id, $subscription_id, $user_id, $session_id);

$cp_status = "PAID";

$customer_id = $stripe_status['customer'] ?? NULL;
$subscription_id = $stripe_status['subscription'] ?? NULL;
$user_id = $login_id;

$stmt->execute();

$conn->close();

// check for errors
if($stmt->errno) {
  
  // TODO DB保存に失敗しています  
}

// close the statement
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
  <title>Thanks for your order!</title>
</head>
<body>
  <a href="/">チェックアウト</a>
</body>
</html>
