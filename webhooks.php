<?php
$STRIPE_PATH = __DIR__ . '/../../../stripe';

require_once $STRIPE_PATH . '/secrets.php';
require_once $STRIPE_PATH . '/vendor/autoload.php';

\Stripe\Stripe::setApiKey($stripeSecretKey);
$endpoint_secret = $stripeWebhookKey;

$payload = @file_get_contents('php://input');
$event = null;
try {
  $event = \Stripe\Event::constructFrom(
    json_decode($payload, true)
  );
} catch(\UnexpectedValueException $e) {

  // Invalid payload
  // TODO WEBHOOKで失敗した場合は、メール通知したい
  exit();
}

// Connect to the database
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    
  // TODO WEBHOOKで失敗した場合は、メール通知したい
}


switch ($event->type) {
  case 'invoice.paid':
    $subscription = $event->data->object;

    $stmt = $conn->prepare("UPDATE checkout_sessions SET period_end=?, updated_at=NOW() WHERE subscription_id=?");
    $stmt->bind_param("is", $period_end, $subscription_id);

    $period_end = $subscription->lines->data[0]->period->end;
    $subscription_id = $subscription->subscription;    

    $stmt->execute();
    $stmt->close();
    
    break;

  case 'customer.subscription.deleted':
    $subscription = $event->data->object;

    $stmt = $conn->prepare("UPDATE checkout_sessions SET cp_status=?, updated_at=NOW() WHERE subscription_id=?");
    $stmt->bind_param("ss", $cp_status, $subscription_id);

    $cp_status = strtoupper($subscription->status);
    $subscription_id = $subscription->id;

    $stmt->execute();
    $stmt->close();    

    break;

  default:

    // Unexpected event type  
}

// DB接続を終了する
$conn->close();
