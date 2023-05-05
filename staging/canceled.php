<?php
$STRIPE_PATH = ($IS_SERVICE_MODE === 'master')? __DIR__ . '/../../../../stripe': __DIR__ . '/../../../../stripe';

require_once $STRIPE_PATH . '/secrets.php';
require_once $STRIPE_PATH . '/vendor/autoload.php';


$login_id = $_POST['login_id'];

$cancel_status = $_POST['cancel_at_period_end'];

// ガード（必須パラメータと値か確認する）
if (!(isset($cancel_status) && ($cancel_status === 'canceled' || $cancel_status === 'resumed'))) {
  header("Location: " . $ERROR_URL . "?error=param");
  die();
}

$is_canceled = ($cancel_status === 'canceled')? true: false;

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    
  // TODO
  die();
}

$stmt = $conn->prepare("SELECT subscription_id FROM checkout_sessions WHERE cp_status=? and user_id=? and period_end > ?");
$stmt->bind_param("sii", $cp_status, $login_id, $current_time);

$cp_status = 'PAID';
$current_time = time();

$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

// 契約中の数
$count = $result->num_rows;

if ($count > 1) {

  // DB接続を終了する
  $conn->close();

  // TODO メール通知
  header("Location: " . $ERROR_URL . "?error=duplicate");
} else if ($count === 1) {
  while ($row = $result->fetch_assoc()) {
    $subscription_id = $row['subscription_id'];
  }

  // ガード（サブスクか判別する）
  if (!$subscription_id) {
    header("Location: " . $ERROR_URL . "?error=not-subscription");  
    die();
  }

  try {
    $stripe = new \Stripe\StripeClient($stripeSecretKey);
    $stripe->subscriptions->update($subscription_id, ['cancel_at_period_end' => $is_canceled]);

    $stmt = $conn->prepare("UPDATE checkout_sessions SET cancel_at_period_end=?, updated_at=NOW() WHERE subscription_id=?");

    $stmt->bind_param("is", $is_canceled, $subscription_id);
  
    $stmt->execute();
    $stmt->close();  

    // DB接続を終了する
    $conn->close();
    
    header("Location: " . $CANCEL_URL . "?success=" . $cancel_status);
  } catch(\UnexpectedValueException $e) {

    // DB接続を終了する
    $conn->close();

    header("Location: " . $ERROR_URL . "?error=" . $cancel_status);
  }
} else {

  // DB接続を終了する
  $conn->close();

  // キャンセルするレコードなし
  header("Location: " . $ERROR_URL . "?error=cancel-none");
}
