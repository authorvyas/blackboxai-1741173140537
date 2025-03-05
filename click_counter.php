<?php
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['app_id'])) {
    $app_id = filter_var($_POST['app_id'], FILTER_VALIDATE_INT);
    if ($app_id !== false) {
        incrementClickCount($app_id);
    }
}
?>
