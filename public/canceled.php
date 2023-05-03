<?php
require_once __DIR__ . '/../../../stripe/secrets.php';
require_once __DIR__ . '/../../../stripe/vendor/autoload.php';

// TODO ログインIDからサブスクリプションIDを取得する
$login_id = $_POST['login_id'];
$subscription_id = 'sub_1N3UemHjUfxVs1AivLMYe3yM';

// キャンセル後や存在しないサブスクIDの場合は500になる
// サブスクIDがACTIVEの場合しか、cancel_at_period_endをfalseにはできない
try {
  $stripe = new \Stripe\StripeClient($stripeSecretKey);
  $test = $stripe->subscriptions->update($subscription_id, ['cancel_at_period_end' => true]);

  header("Location: " . $CANCEL_URL . "?success=canceled");
} catch(\UnexpectedValueException $e) {

  header("Location: " . $ERROR_URL . "?error=canceled");
}