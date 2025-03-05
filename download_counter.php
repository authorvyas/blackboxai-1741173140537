<?php
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['app_id'])) {
    $app_id = filter_var($_POST['app_id'], FILTER_VALIDATE_INT);
    if ($app_id !== false) {
        incrementDownloadCount($app_id);
        
        // Get the app details to return the download link
        $stmt = $pdo->prepare("SELECT link FROM apps WHERE id = ? AND is_downloadable = 1");
        $stmt->execute([$app_id]);
        $app = $stmt->fetch();
        
        if ($app) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'download_url' => $app['link']]);
            exit;
        }
    }
}

header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>
