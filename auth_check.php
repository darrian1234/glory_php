<?php
require_once 'config.php';

if (!isLoggedIn()) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    redirect('login.php');
}
?>