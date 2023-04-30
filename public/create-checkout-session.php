<?php
require_once '../vendor/autoload.php';
require_once '../secrets.php';

\Stripe\Stripe::setApiKey($stripeSecretKey);

header('Content-Type: application/json');

$login_id = 5;

$original_lookup_key = $_POST['lookup_key'];

switch ($original_lookup_key) {
  case 'subscription500':
    $price_id = 'price_1N22gYHjUfxVs1AipFPqIGqT';
    $mode = 'subscription';
    break;
  case '10days':
    $price_id = 'price_1N2N9kHjUfxVs1AiY2fliSjh';
    $mode = 'payment';
    break;    

  default:

    echo json_encode([
      'status' => 'error',
      'text' => 'no item',
    ]);
    die();
};

try {

  // 商品のプライス情報を取得する
  $price = \Stripe\Price::retrieve([
    'id' => $price_id
  ]);

  // チェックアウトセッションを作成する
  $checkout_session = \Stripe\Checkout\Session::create([
    'line_items' => [[
      'price' => $price,
      'quantity' => 1,
    ]],
    'mode' => $mode,
    'success_url' => $YOUR_DOMAIN . '/success.php?session_id={CHECKOUT_SESSION_ID}',
    'cancel_url' => $YOUR_DOMAIN . '/',
  ]);  

  // DBに新規レコードを追加する
  $conn = new mysqli($servername, $username, $password, $dbname);

  if ($conn->connect_error) {
    echo json_encode([
      'status' => 'error',
      'text' => 'Connection failed',
    ]);
    die();
  }

  $stmt = $conn->prepare("INSERT INTO checkout_sessions (cp_status, user_id, session_id) VALUES (?, ?, ?)");  
  $stmt->bind_param("sis", $cp_status, $user_id, $session_id);
  
  $cp_status = "PENDING";
  $user_id = $login_id;
  $session_id = $checkout_session->id;
  
  $stmt->execute();      
  $stmt->close();      

  echo json_encode([
    'status' => 'success',
    'text' => $checkout_session->url,
  ]);  
  
  $conn->close();

} catch (Error $e) {
  // $e->getMessage()

  echo json_encode([
    'status' => 'error',
    'text' => 'checkout',
  ]);
}