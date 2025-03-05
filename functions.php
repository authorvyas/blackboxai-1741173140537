<?php
session_start();
require_once 'config.php';

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

// Handle File Upload
function handleFileUpload($file, $type = 'image') {
    $target_dir = $type === 'image' ? "uploads/" : "html_files/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    // Check file type
    if ($type === 'image') {
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($file_extension, $allowed_types)) {
            return ['success' => false, 'message' => 'Only JPG, JPEG, PNG & GIF files are allowed.'];
        }
    } else {
        if ($file_extension !== 'html') {
            return ['success' => false, 'message' => 'Only HTML files are allowed.'];
        }
    }
    
    // Check file size (5MB max)
    if ($file["size"] > 5000000) {
        return ['success' => false, 'message' => 'File is too large. Maximum size is 5MB.'];
    }
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        $path = $target_file;
        if ($type === 'html') {
            // For HTML files, return the URL that can be used to access the file
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
            $path = $protocol . $_SERVER['HTTP_HOST'] . '/' . $target_file;
        }
        return ['success' => true, 'path' => $path];
    }
    
    return ['success' => false, 'message' => 'Error uploading file.'];
}
?>
