<?php
$STRIPE_PATH = __DIR__ . '/../../../stripe';

require_once $STRIPE_PATH . '/secrets.php';
require_once $STRIPE_PATH . '/vendor/autoload.php';

\Stripe\Stripe::setApiKey($stripeSecretKey);

try {
  $checkout_session = \Stripe\Checkout\Session::retrieve($_POST['session_id']);

  // Authenticate your user.
  $session = \Stripe\BillingPortal\Session::create([
    'customer' => $checkout_session->customer,
    'return_url' => $SUCCESS_URL,
  ]);

  header("HTTP/1.1 303 See Other");
  header("Location: " . $session->url);
} catch (Error $e) {
  // http_response_code(500);
  // echo json_encode(['error' => $e->getMessage()]);

  header("Location: " . $ERROR_URL . "?error=create-portal");
}