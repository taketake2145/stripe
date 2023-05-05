<?php
$STRIPE_PATH = __DIR__ . '/../../../stripe';

if (is_file($STRIPE_PATH . '/secrets.php')) {
  echo 'include aruyo';
} else {
  echo 'include naiyo ' . $STRIPE_PATH;
}