<?php
// using prepared statement?

$STRIPE_PATH = __DIR__ . '/../../../stripe';

require_once  $STRIPE_PATH . '/secrets.php';
require_once  $STRIPE_PATH . '/vendor/autoload.php';

$login_id = 5;

/*
 * 有効期限内のレコードがないか確認する（簡易的に重複申し込み防止対策）
 */
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    
  // TODO
  die();
}

// TODO 確認事項
// session_table から サブスクか都度か未払いか判別して、サブスクの場合は stripe側に問い合わせて、session_tableを更新する必要があるか?
// それとも session_table を使用して問題ないか？ （今のところ、こっちで実装している）

$stmt = $conn->prepare("SELECT cancel_at_period_end, subscription_id, period_start, period_end FROM checkout_sessions WHERE cp_status=? and user_id=? and period_end > ?");
$stmt->bind_param("sii", $cp_status, $login_id, $current_time);

$cp_status = 'PAID';
$current_time = time();

$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

// DB接続を終了する
$conn->close();

// 契約中の数
$count = $result->num_rows;

// 契約中か判別する
if ($count > 0) {

  // TODO メール通知
  if ($count > 1) {
    echo '二重登録あり！！！';
  }

  while ($row = $result->fetch_assoc()) {
    $cp_status_label = ($row['subscription_id'])? 'subscription': 'onetime';
    $cancel_at_period_end = $row['cancel_at_period_end'];    
    $period_start = $row['period_start'];
    $period_end = $row['period_end'];
  }
} else {
  
  $cp_status_label = 'PENDING';
  $cancel_at_period_end = 1;
  $is_subscription_id = false;
  $period_start = 0;
  $period_end = 0;
}


?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title>Sample</title>
    <script src="https://js.stripe.com/v3/"></script>
  </head>
  <body>

    <table>
      <tr>
        <th>ステータス</th>
        <th>次回自動更新</th>
        <th>開始日</th>
        <th>終了日</th>
      <tr>
      <tr>
        <td><?php echo $cp_status_label; ?></td>
        <td><?php echo ($cancel_at_period_end)? 'なし': date('Y-m-d', $period_end); ?></td>
        <td><?php echo ($period_start > 0)? date('Y-m-d', $period_start): '-'; ?></td>
        <td><?php echo ($period_end > 0)? date('Y-m-d', $period_end): '-'; ?></td>
      </tr>
    </table>
    
    <section>
      <form action="/create-checkout-session.php" method="POST">
        <input type="text" name="login_id" value="5" inputmode="numeric">
        <input type="hidden" name="lookup_key" value="subscription500">
        <button id="checkout-and-portal-button" type="submit">サブスク Checkout</button>
      </form>

      <form action="/create-checkout-session.php" method="POST">
        <input type="text" name="login_id" value="5" inputmode="numeric">
        <input type="hidden" name="lookup_key" value="10days">
        <button id="checkout-and-portal-button" type="submit">都度 Checkout</button>
      </form>   
      
      <form action="/canceled.php" method="POST">
        <input type="text" name="login_id" value="5" inputmode="numeric">
        <input type="hidden" name="cancel_at_period_end" value="canceled">
        <button type="submit">解約する</button>
      </form>      

      <form action="/canceled.php" method="POST">
        <input type="text" name="login_id" value="5" inputmode="numeric">
        <input type="hidden" name="cancel_at_period_end" value="resumed">
        <button type="submit">再開する</button>
      </form>         
      
    </section>
  </body>
</html>