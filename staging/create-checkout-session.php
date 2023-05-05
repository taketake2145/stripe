<?php
$login_id = $_POST['login_id'];


$STRIPE_PATH = ($IS_SERVICE_MODE === 'master')? __DIR__ . '/../../../../stripe': __DIR__ . '/../../../../stripe';

require_once $STRIPE_PATH . '/secrets.php';
require_once $STRIPE_PATH . '/vendor/autoload.php';

\Stripe\Stripe::setApiKey($stripeSecretKey);


$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {

  // TODO
  die();
}

// 複数契約していないか確認する
// NOTICE: 完全な判別ではない。複数端末やブラウザタブで、契約前に決済画面を開いていた場合は、それぞれで複数契約できてしまう
$stmt = $conn->prepare("SELECT subscription_id FROM checkout_sessions WHERE cp_status=? and user_id=? and period_end > ?");
$stmt->bind_param("sii", $cp_status, $login_id, $current_time);

$cp_status = 'PAID';
$current_time = time();

$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

// 契約中の数
$count = $result->num_rows;

if ($count > 0) {

  // DB接続を終了する
  $conn->close();

  // $e->getMessage()
  header("Location: " . $ERROR_URL . "?error=duplicate");
  die();
}

try {
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
  $stmt = $conn->prepare("INSERT INTO checkout_sessions (cp_status, user_id, session_id) VALUES (?, ?, ?)");  
  $stmt->bind_param("sis", $cp_status, $user_id, $session_id);
  
  $cp_status = "PENDING";
  $user_id = $login_id;
  $session_id = $checkout_session->id;
  
  $stmt->execute();      
  $stmt->close();

  // DB接続を終了する
  $conn->close();

  header("HTTP/1.1 303 See Other");
  header("Location: " . $checkout_session->url);
} catch (Error $e) {

  // DB接続を終了する
  $conn->close();

  // $e->getMessage()
  header("Location: " . $ERROR_URL . "?error=create-checkout");
}

