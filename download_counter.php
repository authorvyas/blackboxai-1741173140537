<?php
require_once 'functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['app_id'])) {
    $app_id = filter_var($_POST['app_id'], FILTER_VALIDATE_INT);
    if ($app_id !== false) {
        // Get the app details
        $stmt = $pdo->prepare("SELECT link, name FROM apps WHERE id = ? AND is_downloadable = 1");
        $stmt->execute([$app_id]);
        $app = $stmt->fetch();
        
        if ($app) {
            // Check if this is a download confirmation
            if (isset($_POST['confirm']) && $_POST['confirm'] === 'true') {
                incrementDownloadCount($app_id);
                
                // Get file information
                $file_path = __DIR__ . '/' . $app['link'];
                $file_name = basename($app['link']);
                
                if (file_exists($file_path)) {
                    echo json_encode([
                        'success' => true,
                        'download_url' => $app['link'],
                        'file_name' => $file_name
                    ]);
                    exit;
                }
            } else {
                // First request - ask for confirmation
                echo json_encode([
                    'success' => true,
                    'needsConfirmation' => true,
                    'message' => "Are you sure you want to download '{$app['name']}'?",
                    'app_id' => $app_id
                ]);
                exit;
            }
        }
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid request or file not found']);
?>
