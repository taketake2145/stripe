<?php
if (is_file(__DIR__ . '/../../../stripe/secrets.php')) {
  echo 'include aruyo';
} else {
  echo 'include naiyo';
}