<?php

require_once '../vendor/autoload.php';
require_once '../secrets.php';

\Stripe\Stripe::setApiKey($stripeSecretKey);

header('Content-Type: application/json');

$original_lookup_key = $_POST['lookup_key'];

switch ($original_lookup_key) {
  case 'subscription500':
    $price_id = 'price_1N22gYHjUfxVs1AipFPqIGqT';
    break;

  default:

    // TODO エラー画面に誘導する
    die();
};


try {
  $price = \Stripe\Price::retrieve([
    'id' => $price_id
  ]);

  $checkout_session = \Stripe\Checkout\Session::create([
    'line_items' => [[
      'price' => $price,
      'quantity' => 1,
    ]],
    'mode' => 'subscription',
    'success_url' => $YOUR_DOMAIN . '/success.php?session_id={CHECKOUT_SESSION_ID}',
    'cancel_url' => $YOUR_DOMAIN . '/',
  ]);

  // DBに新規レコードを追加する
  // Connect to the database
  $conn = new mysqli($servername, $username, $password, $dbname);

  // Check connection
  if ($conn->connect_error) {
      die("Connection failed: " . $conn->connect_error);
  }
  
  // Prepare the statement
  $stmt = $conn->prepare("INSERT INTO checkout_sessions (cp_status, user_id, session_id) VALUES (?, ?, ?)");
  
  // Bind the parameters
  $stmt->bind_param("sis", $cp_status, $user_id, $session_id);
  
  // Define the values to insert
  $cp_status = "PENDING";
  $user_id = 5;
  $session_id = $checkout_session->id;
  
  // Execute the statement
  $stmt->execute();
  
  // Close the statement and connection
  $stmt->close();
  $conn->close();

  header("HTTP/1.1 303 See Other");
  header("Location: " . $checkout_session->url);
} catch (Error $e) {
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()]);
}