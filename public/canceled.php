<?php
require_once '../vendor/autoload.php';
require_once '../secrets.php';

// キャンセル後や存在しないサブスクIDの場合は500になる
// サブスクIDがACTIVEの場合しか、cancel_at_period_endをfalseにはできない
try {
  $stripe = new \Stripe\StripeClient($stripeSecretKey);
  $stripe->subscriptions->update('sub_1N2MTIHjUfxVs1AiDNntpYUa', ['cancel_at_period_end' => true]);

  echo 'success';
} catch(\UnexpectedValueException $e) {
  echo 'failure';
}



