<?php
require_once __DIR__ . '/../../../stripe/secrets.php';
require_once __DIR__ . '/../../../stripe/vendor/autoload.php';

\Stripe\Stripe::setApiKey($stripeSecretKey);

$login_id = $_POST['login_id'];
$original_lookup_key = $_POST['lookup_key'];

// 内部の商品コードからstripeの商品コードに変換する
switch ($original_lookup_key) {
  case 'subscription500':
    $price_id = ($IS_SERVICE_MODE === 'master')? 'price_1MuTduHjUfxVs1AiJBWpJJLB': 'price_1N22gYHjUfxVs1AipFPqIGqT';
    $mode = 'subscription';
    break;
  case '10days':
    $price_id = ($IS_SERVICE_MODE === 'master')? 'price_1N35UaHjUfxVs1AikrDc94vC': 'price_1N2N9kHjUfxVs1AiY2fliSjh';
    $mode = 'payment';
    break;    

  default:
    header("Location: " . $ERROR_URL . "?error=no-item");
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
    'success_url' => $SUCCESS_URL . '?session_id={CHECKOUT_SESSION_ID}',
    'cancel_url' => $CANCEL_URL,
  ]);  

  // DBに新規レコードを追加する
  $conn = new mysqli($servername, $username, $password, $dbname);

  if ($conn->connect_error) {
    header("Location: " . $ERROR_URL . "?error=checkout-id");
    die();
  }

  $stmt = $conn->prepare("INSERT INTO checkout_sessions (cp_status, user_id, session_id) VALUES (?, ?, ?)");  
  $stmt->bind_param("sis", $cp_status, $user_id, $session_id);
  
  $cp_status = "PENDING";
  $user_id = $login_id;
  $session_id = $checkout_session->id;
  
  $stmt->execute();      
  $stmt->close();
  
  $conn->close();

  header("HTTP/1.1 303 See Other");
  header("Location: " . $checkout_session->url);
} catch (Error $e) {

  // $e->getMessage()
  header("Location: " . $ERROR_URL . "?error=create-checkout");
}
