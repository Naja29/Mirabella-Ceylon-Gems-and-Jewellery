<?php
require_once __DIR__ . '/includes/customer_auth.php';

destroy_customer_session();

header('Location: login.php');
exit;
