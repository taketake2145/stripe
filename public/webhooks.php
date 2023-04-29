<?php
require_once '../vendor/autoload.php';
require_once '../secrets.php';

\Stripe\Stripe::setApiKey($stripeSecretKey);

// Replace this endpoint secret with your endpoint's unique secret
// If you are testing with the CLI, find the secret by running 'stripe listen'
// If you are using an endpoint defined with the API or dashboard, look in your webhook settings
// at https://dashboard.stripe.com/webhooks
$endpoint_secret = $stripeWebhookKey;

$payload = @file_get_contents('php://input');
$event = null;
try {
  $event = \Stripe\Event::constructFrom(
    json_decode($payload, true)
  );
} catch(\UnexpectedValueException $e) {
  // Invalid payload
  echo '⚠️  Webhook error while parsing basic request.';
  http_response_code(400);
  exit();
}

// DBに新規レコードを追加する
// Connect to the database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Handle the event
switch ($event->type) {
  case 'customer.subscription.created':
    $subscription = $event->data->object; // contains a \Stripe\Subscription
    // Then define and call a method to handle the subscription being created.
    // handleSubscriptionCreated($subscription);

    $stmt = $conn->prepare("UPDATE checkout_sessions SET sub_status=?, updated_at=NOW() WHERE sub_status='UNKNOWN' and subscription_id=?");

    $stmt->bind_param("sssis", $cp_status, $customer_id, $subscription_id, $user_id, $session_id);
    
    $sub_status = "INCOMPLETE";
    $subscription_id = $payload['data']['object']['status'];
    
    $stmt->execute();    

    break;
  case 'customer.subscription.deleted':
    $subscription = $event->data->object; // contains a \Stripe\Subscription
    // Then define and call a method to handle the subscription being deleted.
    // handleSubscriptionDeleted($subscription);


    break;
  case 'customer.subscription.updated':
    $subscription = $event->data->object; // contains a \Stripe\Subscription
    // Then define and call a method to handle the subscription being updated.
    // handleSubscriptionUpdated($subscription);


    break;

  default:
    // Unexpected event type
    echo 'Received unknown event type';
}

// ログ追加
$stmt = $conn->prepare("INSERT INTO accesslog (log) VALUES (?)");
$stmt->bind_param("s", $payload);
$stmt->execute();
$stmt->close();

// DB接続を終了する
$conn->close();