<?php
$STRIPE_PATH = __DIR__ . '/../../../stripe';

require_once  $STRIPE_PATH . '/secrets.php';
require_once  $STRIPE_PATH . '/vendor/autoload.php';

$session_id = $_GET['session_id'];

// ガード（必須パラメータの有無を確認する）
if (!isset($session_id)) {
  die('silence');
}

/*
 * セッションIDから、支払い済みか確認する
 */
\Stripe\Stripe::setApiKey($stripeSecretKey);
$stripe_status = \Stripe\Checkout\Session::retrieve([
  'id' => $session_id
]);

$payment_status = $stripe_status['payment_status'];

// ガード（支払い済みか確認する）
if ($payment_status !== 'paid') {
  die('silence');
}


/*
 * DBに新規レコードを追加する
 */ 
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    
  // TODO
  die();
}

$cp_status = "PAID";
$customer_id = $stripe_status['customer'] ?? NULL;
$subscription_id = $stripe_status['subscription'] ?? NULL;

// 都度か継続か判別する
if ($subscription_id === NULL) {

  // 10日 = 864000秒
  $period_start = time();
  $period_end = $period_start + 864000;

  $stmt = $conn->prepare("UPDATE checkout_sessions SET cp_status=?, period_start=?, period_end=?, updated_at=NOW() WHERE cp_status='PENDING' and session_id=?");

  $stmt->bind_param("siis", $cp_status, $period_start, $period_end, $session_id);

  $stmt->execute();
  $stmt->close();
} else {

  // サブスクリプションIDから期間を取得する
  $stripe = new \Stripe\StripeClient($stripeSecretKey);
  $subscription_data = $stripe->subscriptions->retrieve(
    $subscription_id,
    []
  );

  $period_start = $subscription_data['current_period_start'];
  $period_end = $subscription_data['current_period_end'];

  $stmt = $conn->prepare("UPDATE checkout_sessions SET cp_status=?, customer_id=?, subscription_id=?, cancel_at_period_end=false, period_start=?, period_end=?, updated_at=NOW() WHERE cp_status='PENDING' and session_id=?");

  $stmt->bind_param("sssiis", $cp_status, $customer_id, $subscription_id, $period_start, $period_end, $session_id);

  $stmt->execute();
  $stmt->close();
}

// DB接続を終了する
$conn->close();
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
