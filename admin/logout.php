<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Auth.php';

$auth = new Auth();
$auth->logout();

redirect(APP_URL . '/admin/index.php');
