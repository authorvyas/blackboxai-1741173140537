<?php
require_once __DIR__ . '/functions.php';

$message = '';
$messageType = 'success';

// Handle Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'login') {
        if (adminLogin($_POST['username'], $_POST['password'])) {
            header('Location: admin.php');
            exit();
        } else {
            $message = 'Invalid username or password';
            $messageType = 'error';
        }
    }
    
    // Require admin authentication for other actions
    requireAdmin();
    
    // Handle HTML file upload
    if ($_POST['action'] === 'upload_html') {
        $uploadResult = handleFileUpload($_FILES['html_file'], 'html');
        header('Content-Type: application/json');
        echo json_encode($uploadResult);
        exit;
    }
    
    // Handle App Management
    if ($_POST['action'] === 'add_app') {
        $uploadResult = handleFileUpload($_FILES['thumbnail']);
        if ($uploadResult['success']) {
            // Handle HTML file if uploaded along with the form
            $link = $_POST['link'];
            if (!empty($_FILES['html_file']['name'])) {
                $htmlResult = handleFileUpload($_FILES['html_file'], 'html');
                if ($htmlResult['success']) {
                    $link = $htmlResult['path'];
                } else {
                    $message = $htmlResult['message'];
                    $messageType = 'error';
                }
            }
            
            if (empty($message)) {
                $is_downloadable = isset($_POST['is_downloadable']) ? 1 : 0;
                $tags = !empty($_POST['tags']) ? trim($_POST['tags']) : '';
                
                if (addApp(
                    $_POST['name'],
                    $_POST['description'],
                    $uploadResult['path'],
                    $link,
                    $is_downloadable,
                    $tags
                )) {
                    $message = 'App added successfully';
                } else {
                    $message = 'Error adding app';
                    $messageType = 'error';
                }
            }
        } else {
            $message = $uploadResult['message'];
            $messageType = 'error';
        }
    }
    elseif ($_POST['action'] === 'update_app') {
        $data = [
            'name' => $_POST['name'],
            'description' => $_POST['description'],
            'link' => $_POST['link'],
            'is_downloadable' => isset($_POST['is_downloadable']) ? 1 : 0,
            'tags' => !empty($_POST['tags']) ? trim($_POST['tags']) : '',
            'enabled' => isset($_POST['enabled']) ? 1 : 0
        ];
        
        // Handle thumbnail upload if provided
        if (!empty($_FILES['thumbnail']['name'])) {
            $uploadResult = handleFileUpload($_FILES['thumbnail']);
            if ($uploadResult['success']) {
                $data['thumbnail'] = $uploadResult['path'];
            } else {
                $message = $uploadResult['message'];
                $messageType = 'error';
            }
        }
        
        // Handle HTML file upload if provided
        if (empty($message) && !empty($_FILES['html_file']['name'])) {
            $htmlResult = handleFileUpload($_FILES['html_file'], 'html');
            if ($htmlResult['success']) {
                $data['link'] = $htmlResult['path'];
            } else {
                $message = $htmlResult['message'];
                $messageType = 'error';
            }
        }
        
        if (empty($message)) {
            if (updateApp($_POST['id'], $data)) {
                $message = 'App updated successfully';
            } else {
                $message = 'Error updating app';
                $messageType = 'error';
            }
        }
    }
    elseif ($_POST['action'] === 'delete_app') {
        if (deleteApp($_POST['id'])) {
            $message = 'App deleted successfully';
        } else {
            $message = 'Error deleting app';
            $messageType = 'error';
        }
    }
    
    // Handle Page Content Updates
    elseif ($_POST['action'] === 'update_page') {
        if (updatePageContent($_POST['page_name'], $_POST['content'])) {
            $message = 'Page content updated successfully';
        } else {
            $message = 'Error updating page content';
            $messageType = 'error';
        }
    }
}

$sort = isset($_GET['sort']) ? $_GET['sort'] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Prabhat's Workspace</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <?php if (!isLoggedIn()): ?>
        <!-- Login Form -->
        <div class="min-h-screen flex items-center justify-center">
            <div class="bg-white p-8 rounded-lg shadow-md w-96">
                <h2 class="text-2xl font-bold mb-6 text-center">Admin Login</h2>
                <?php if ($message): ?>
                    <div class="mb-4 p-4 rounded <?= $messageType === 'error' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' ?>">
                        <?= sanitizeOutput($message) ?>
                    </div>
                <?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="action" value="login">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="username">Username</label>
                        <input type="text" name="username" id="username" required
                               class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="password">Password</label>
                        <input type="password" name="password" id="password" required
                               class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <button type="submit" class="w-full bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-600 transition-colors">
                        Login
                    </button>
                </form>
            </div>
        </div>
        <?php else: ?>
        <!-- Admin Dashboard -->
        <div class="container mx-auto px-4 py-8">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800">Admin Dashboard</h1>
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="text-blue-500 hover:text-blue-700">View Site</a>
                    <form method="POST" action="logout.php">
                        <button type="submit" class="bg-red-500 text-white py-2 px-4 rounded hover:bg-red-600 transition-colors">
                            Logout
                        </button>
                    </form>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="mb-8 p-4 rounded <?= $messageType === 'error' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' ?>">
                    <?= sanitizeOutput($message) ?>
                </div>
            <?php endif; ?>

            <!-- Add New App -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-xl font-bold mb-4">Add New App</h2>
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="action" value="add_app">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">App Name</label>
                        <input type="text" name="name" required class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Description</label>
                        <textarea name="description" rows="3" class="w-full px-3 py-2 border rounded-lg"></textarea>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Thumbnail</label>
                        <input type="file" name="thumbnail" required accept="image/*" class="w-full">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Tags (comma-separated)</label>
                        <input type="text" name="tags" class="w-full px-3 py-2 border rounded-lg" placeholder="e.g., School, Education, Math">
                    </div>
                    <div>
                        <label class="flex items-center space-x-2">
                            <input type="checkbox" name="is_downloadable" value="1" class="rounded">
                            <span class="text-gray-700 text-sm font-bold">Enable Download</span>
                        </label>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-gray-700 text-sm font-bold">Link</label>
                        <div class="flex items-center space-x-2">
                            <input type="url" name="link" id="link_input" class="w-full px-3 py-2 border rounded-lg">
                            <span class="text-gray-500">or</span>
                            <label class="bg-gray-100 px-4 py-2 rounded-lg cursor-pointer hover:bg-gray-200">
                                <input type="file" name="html_file" accept=".html" class="hidden" onchange="handleHTMLUpload(this)">
                                Upload HTML
                            </label>
                        </div>
                        <p class="text-sm text-gray-500">Enter a URL or upload an HTML file</p>
                    </div>
                    <button type="submit" class="bg-blue-500 text-white py-2 px-4 rounded hover:bg-blue-600 transition-colors">
                        Add App
                    </button>
                </form>
            </div>

            <!-- Manage Apps -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold">Manage Apps</h2>
                    <div>
                        <label class="mr-2">Sort by:</label>
                        <select onchange="window.location.href='?sort='+this.value" class="border rounded-lg px-2 py-1">
                            <option value="">Latest</option>
                            <option value="clicks" <?= $sort === 'clicks' ? 'selected' : '' ?>>Click Count</option>
                        </select>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">App</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Clicks</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach (getApps($sort, true) as $app): ?>
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <img src="<?= sanitizeOutput($app['thumbnail']) ?>" alt="" class="h-10 w-10 rounded-full">
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900"><?= sanitizeOutput($app['name']) ?></div>
                                            <div class="text-sm text-gray-500"><?= sanitizeOutput($app['link']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <?= number_format($app['click_count']) ?>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $app['enabled'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                        <?= $app['enabled'] ? 'Enabled' : 'Disabled' ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm font-medium space-x-2">
                                    <button onclick="editApp(<?= htmlspecialchars(json_encode($app)) ?>)" 
                                            class="text-blue-600 hover:text-blue-900">Edit</button>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="delete_app">
                                        <input type="hidden" name="id" value="<?= $app['id'] ?>">
                                        <button type="submit" onclick="return confirm('Are you sure?')" 
                                                class="text-red-600 hover:text-red-900">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Edit App Modal -->
            <div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden">
                <div class="flex items-center justify-center min-h-screen">
                    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
                        <h3 class="text-xl font-bold mb-4">Edit App</h3>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="update_app">
                            <input type="hidden" name="id" id="edit_id">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-gray-700 text-sm font-bold mb-2">App Name</label>
                                    <input type="text" name="name" id="edit_name" required class="w-full px-3 py-2 border rounded-lg">
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-bold mb-2">Description</label>
                                    <textarea name="description" id="edit_description" rows="3" class="w-full px-3 py-2 border rounded-lg"></textarea>
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-bold mb-2">New Thumbnail (optional)</label>
                                    <input type="file" name="thumbnail" accept="image/*" class="w-full">
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-bold mb-2">Tags (comma-separated)</label>
                                    <input type="text" name="tags" id="edit_tags" class="w-full px-3 py-2 border rounded-lg" placeholder="e.g., School, Education, Math">
                                </div>
                                <div>
                                    <label class="flex items-center space-x-2">
                                        <input type="checkbox" name="is_downloadable" id="edit_downloadable" value="1" class="rounded">
                                        <span class="text-gray-700 text-sm font-bold">Enable Download</span>
                                    </label>
                                </div>
                                <div class="space-y-2">
                                    <label class="block text-gray-700 text-sm font-bold">Link</label>
                                    <div class="flex items-center space-x-2">
                                        <input type="url" name="link" id="edit_link" class="w-full px-3 py-2 border rounded-lg">
                                        <span class="text-gray-500">or</span>
                                        <label class="bg-gray-100 px-4 py-2 rounded-lg cursor-pointer hover:bg-gray-200">
                                            <input type="file" name="html_file" accept=".html" class="hidden" onchange="handleEditHTMLUpload(this)">
                                            Upload HTML
                                        </label>
                                    </div>
                                    <p class="text-sm text-gray-500">Enter a URL or upload an HTML file</p>
                                </div>
                                <div>
                                    <label class="flex items-center">
                                        <input type="checkbox" name="enabled" id="edit_enabled" class="mr-2">
                                        <span class="text-sm font-bold text-gray-700">Enabled</span>
                                    </label>
                                </div>
                                <div class="flex justify-end space-x-2">
                                    <button type="button" onclick="closeEditModal()" 
                                            class="bg-gray-500 text-white py-2 px-4 rounded hover:bg-gray-600 transition-colors">
                                        Cancel
                                    </button>
                                    <button type="submit" 
                                            class="bg-blue-500 text-white py-2 px-4 rounded hover:bg-blue-600 transition-colors">
                                        Update
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Manage Pages -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold mb-4">Manage Pages</h2>
                <div class="space-y-6">
                    <!-- About Us -->
                    <div>
                        <h3 class="text-lg font-semibold mb-2">About Us</h3>
                        <form method="POST">
                            <input type="hidden" name="action" value="update_page">
                            <input type="hidden" name="page_name" value="about_us">
                            <textarea name="content" rows="6" class="w-full px-3 py-2 border rounded-lg mb-2"><?= sanitizeOutput(getPageContent('about_us')) ?></textarea>
                            <button type="submit" class="bg-blue-500 text-white py-2 px-4 rounded hover:bg-blue-600 transition-colors">
                                Update About Us
                            </button>
                        </form>
                    </div>

                    <!-- Contact Us -->
                    <div>
                        <h3 class="text-lg font-semibold mb-2">Contact Us</h3>
                        <form method="POST">
                            <input type="hidden" name="action" value="update_page">
                            <input type="hidden" name="page_name" value="contact_us">
                            <textarea name="content" rows="6" class="w-full px-3 py-2 border rounded-lg mb-2"><?= sanitizeOutput(getPageContent('contact_us')) ?></textarea>
                            <button type="submit" class="bg-blue-500 text-white py-2 px-4 rounded hover:bg-blue-600 transition-colors">
                                Update Contact Us
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
    function editApp(app) {
        document.getElementById('edit_id').value = app.id;
        document.getElementById('edit_name').value = app.name;
        document.getElementById('edit_description').value = app.description || '';
        document.getElementById('edit_link').value = app.link;
        document.getElementById('edit_tags').value = app.tags || '';
        document.getElementById('edit_downloadable').checked = app.is_downloadable == 1;
        document.getElementById('edit_enabled').checked = app.enabled == 1;
        document.getElementById('editModal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
    }

    function handleHTMLUpload(input) {
        handleFileUpload(input, 'link_input');
    }

    function handleEditHTMLUpload(input) {
        handleFileUpload(input, 'edit_link');
    }

    function handleFileUpload(input, targetInputId) {
        if (input.files && input.files[0]) {
            const formData = new FormData();
            formData.append('html_file', input.files[0]);
            formData.append('action', 'upload_html');

            fetch('admin.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById(targetInputId).value = data.path;
                } else {
                    alert(data.message || 'Error uploading HTML file');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error uploading HTML file');
            });
        }
    }
    </script>
</body>
</html>
