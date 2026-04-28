<?php
require __DIR__ . '/../app/bootstrap.php';
App\Core\Auth::logout();
header('Location: /admin/login.php');
exit;
