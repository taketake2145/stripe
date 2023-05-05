<?php
$STRIPE_PATH = ($IS_SERVICE_MODE === 'master')? __DIR__ . '/../../../../stripe': __DIR__ . '/../../../../stripe';

if (is_file($STRIPE_PATH . '/secrets.php')) {
  echo 'include aruyo';
} else {
  echo 'include naiyo ' . $STRIPE_PATH;
}