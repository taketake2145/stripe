<?php
require_once '../vendor/autoload.php';
require_once '../secrets.php';

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
  echo 'Webhook error while parsing basic request.';
  http_response_code(400);
  exit();
}

// Connect to the database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  die("Connection failed.");
}

// Handle the event
switch ($event->type) {
  case 'customer.subscription.created':
    $subscription = $event->data->object;

    $stmt = $conn->prepare("INSERT INTO subscriptions (sub_status, event, subscription_id, period_start, period_end) VALUES (?, ?, ?, ?, ?)");

    $stmt->bind_param("sssii", $sub_status, $event_type, $subscription_id, $period_start, $period_end);

    $sub_status = strtoupper($subscription->status);
    $event_type = $event->type;
    $period_start = $subscription->current_period_start;
    $period_end = $subscription->current_period_end;
    $subscription_id = $subscription->id;

    $stmt->execute();

    break;

  case 'customer.subscription.deleted':
    $subscription = $event->data->object;
    
    $stmt = $conn->prepare("INSERT INTO subscriptions (sub_status, event, subscription_id, period_start, period_end) VALUES (?, ?, ?, ?, ?)");

    $stmt->bind_param("sssii", $sub_status, $event_type, $subscription_id, $period_start, $period_end);

    $sub_status = strtoupper($subscription->status);
    $event_type = $event->type;
    $period_start = $subscription->current_period_start;
    $period_end = $subscription->current_period_end;
    $subscription_id = $subscription->id;

    $stmt->execute();

    break;

  case 'customer.subscription.updated':
    $subscription = $event->data->object;
    
    $stmt = $conn->prepare("INSERT INTO subscriptions (sub_status, event, subscription_id, period_start, period_end) VALUES (?, ?, ?, ?, ?)");

    $stmt->bind_param("sssii", $sub_status, $event_type, $subscription_id, $period_start, $period_end);

    $sub_status = strtoupper($subscription->status);
    $event_type = $event->type;
    $period_start = $subscription->current_period_start;
    $period_end = $subscription->current_period_end;
    $subscription_id = $subscription->id;

    $stmt->execute();    
    break;

  default:

    // Unexpected event type  
}

// TODO テストが終了したら不要
// ログ追加
$stmt = $conn->prepare("INSERT INTO accesslog (log) VALUES (?)");
$stmt->bind_param("s", $payload);
$stmt->execute();
$stmt->close();

// DB接続を終了する
$conn->close();