<?php
session_start();
require_once __DIR__ . '/config.php';

// Authentication Functions
function isLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function requireAdmin() {
    if (!isLoggedIn()) {
        header('Location: admin.php');
        exit();
    }
}

function adminLogin($username, $password) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT id, password FROM admin WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['admin_id'] = $user['id'];
            return true;
        }
        return false;
    } catch(PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        return false;
    }
}

// App Management Functions
function getApps($sort = null, $adminView = false) {
    global $pdo;
    try {
        $sql = "SELECT * FROM apps";
        if (!$adminView) {
            $sql .= " WHERE enabled = 1";
        }
        if ($sort === 'clicks') {
            $sql .= " ORDER BY click_count DESC";
        } else {
            $sql .= " ORDER BY created_at DESC";
        }
        
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Get apps error: " . $e->getMessage());
        return [];
    }
}

function addApp($name, $description, $thumbnail, $link, $is_downloadable = 0, $tags = '') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO apps (name, description, thumbnail, link, is_downloadable, tags) VALUES (?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$name, $description, $thumbnail, $link, $is_downloadable, $tags]);
    } catch(PDOException $e) {
        error_log("Add app error: " . $e->getMessage());
        return false;
    }
}

function updateApp($id, $data) {
    global $pdo;
    try {
        $sets = [];
        $values = [];
        foreach ($data as $key => $value) {
            $sets[] = "$key = ?";
            $values[] = $value;
        }
        $values[] = $id;
        
        $sql = "UPDATE apps SET " . implode(', ', $sets) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($values);
    } catch(PDOException $e) {
        error_log("Update app error: " . $e->getMessage());
        return false;
    }
}

function deleteApp($id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("DELETE FROM apps WHERE id = ?");
        return $stmt->execute([$id]);
    } catch(PDOException $e) {
        error_log("Delete app error: " . $e->getMessage());
        return false;
    }
}

function incrementClickCount($id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE apps SET click_count = click_count + 1 WHERE id = ?");
        return $stmt->execute([$id]);
    } catch(PDOException $e) {
        error_log("Increment click count error: " . $e->getMessage());
        return false;
    }
}

function incrementDownloadCount($id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE apps SET download_count = download_count + 1 WHERE id = ?");
        return $stmt->execute([$id]);
    } catch(PDOException $e) {
        error_log("Increment download count error: " . $e->getMessage());
        return false;
    }
}

function getAppsByTag($tag) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM apps WHERE enabled = 1 AND tags LIKE ? ORDER BY created_at DESC");
        $stmt->execute(['%' . $tag . '%']);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Get apps by tag error: " . $e->getMessage());
        return [];
    }
}

function getAllTags() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT DISTINCT tags FROM apps WHERE tags IS NOT NULL AND tags != ''");
        $tags = [];
        while ($row = $stmt->fetch()) {
            $tagArray = explode(',', $row['tags']);
            foreach ($tagArray as $tag) {
                $tag = trim($tag);
                if (!empty($tag) && !in_array($tag, $tags)) {
                    $tags[] = $tag;
                }
            }
        }
        return $tags;
    } catch(PDOException $e) {
        error_log("Get all tags error: " . $e->getMessage());
        return [];
    }
}

// Handle File Upload
function handleFileUpload($file, $type = 'image') {
    // Ensure upload directories exist with proper permissions
    $uploads_dir = __DIR__ . '/uploads';
    $html_files_dir = __DIR__ . '/html_files';
    $downloads_dir = __DIR__ . '/downloads';
    
    foreach ([$uploads_dir, $html_files_dir, $downloads_dir] as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
    }

    // Create default thumbnail if it doesn't exist
    $default_thumbnail = $uploads_dir . '/default-thumbnail.jpg';
    if (!file_exists($default_thumbnail)) {
        $img = imagecreatetruecolor(400, 300);
        $bg = imagecolorallocate($img, 240, 240, 240);
        $text_color = imagecolorallocate($img, 100, 100, 100);
        imagefill($img, 0, 0, $bg);
        imagestring($img, 5, 150, 140, "No Thumbnail", $text_color);
        imagejpeg($img, $default_thumbnail);
        imagedestroy($img);
    }

    // Determine target directory based on file type
    switch ($type) {
        case 'image':
            $target_dir = $uploads_dir;
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            break;
        case 'html':
            $target_dir = $html_files_dir;
            $allowed_types = ['html'];
            break;
        case 'download':
            $target_dir = $downloads_dir;
            $allowed_types = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip', 'rar', 'txt'];
            break;
        default:
            return ['success' => false, 'message' => 'Invalid file type.'];
    }

    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . '/' . $new_filename;
    $relative_path = basename($target_dir) . '/' . $new_filename;

    // Check file type
    if (!in_array($file_extension, $allowed_types)) {
        $allowed_list = implode(', ', $allowed_types);
        return ['success' => false, 'message' => "Only {$allowed_list} files are allowed."];
    }
    
    // Check file size (5MB max)
    if ($file["size"] > 5000000) {
        return ['success' => false, 'message' => 'File is too large. Maximum size is 5MB.'];
    }
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        if ($type === 'html') {
            // For HTML files, return the URL that can be used to access the file
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
            $relative_path = $protocol . $_SERVER['HTTP_HOST'] . '/' . $relative_path;
        }
        return ['success' => true, 'path' => $relative_path];
    }
    
    return ['success' => false, 'message' => 'Error uploading file.'];
}

// Page Content Management Functions
function getPageContent($page_name) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT content FROM pages WHERE page_name = ?");
        $stmt->execute([$page_name]);
        $result = $stmt->fetch();
        return $result ? $result['content'] : '';
    } catch(PDOException $e) {
        error_log("Get page content error: " . $e->getMessage());
        return '';
    }
}

function updatePageContent($page_name, $content) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE pages SET content = ? WHERE page_name = ?");
        return $stmt->execute([$content, $page_name]);
    } catch(PDOException $e) {
        error_log("Update page content error: " . $e->getMessage());
        return false;
    }
}

// Helper Functions
function sanitizeOutput($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function displayMessage($message, $type = 'success') {
    return "<div class='alert alert-{$type} mb-4 p-4 rounded'>" . sanitizeOutput($message) . "</div>";
}
?>
