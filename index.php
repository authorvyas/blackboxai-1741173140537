<?php
require_once 'functions.php';

// Get tag filter if present
$tag = isset($_GET['tag']) ? trim($_GET['tag']) : '';
$apps = !empty($tag) ? getAppsByTag($tag) : getApps();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prabhat's Workspace</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 0.9rem;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            margin: 0;
            line-height: 1.6;
        }
        .app-card {
            transition: transform 0.2s ease, box-shadow 0.3s ease;
            border: 1px solid #ddd;
            background-color: white;
        }
        .app-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .app-card img {
            height: 150px;
            object-fit: cover;
            position: relative;
        }
        .price-info .buy { color: #ff4444; font-weight: bold; }
        .price-info .free { color: #44aa44; }
        .click-count {
            font-size: 10px;
            color: #666;
            background-color: #f0f0f0;
            border-radius: 3px;
            padding: 4px;
        }
        .count { font-weight: bold; color: #333; }
        .btn-gradient {
            background: linear-gradient(90deg, #3b82f6, #9333ea);
            transition: all 0.3s ease;
        }
        .btn-gradient:hover {
            background: linear-gradient(90deg, #2563eb, #7e22ce);
        }
        .share-buttons a {
            transition: color 0.2s ease;
            margin-left: 8px;
        }
        .share-buttons a:hover {
            opacity: 0.8;
        }
        .whatsapp { color: #25D366; }
        .facebook { color: #3b5998; }
        .twitter { color: #1DA1F2; }
        .download-icon {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(255, 255, 255, 0.9);
            padding: 8px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 10;
        }
        .download-icon:hover {
            transform: scale(1.1);
            background: white;
        }
        @media (max-width: 640px) {
            .text-3xl { font-size: 1.25rem; }
            .text-2xl { font-size: 1rem; }
            .grid-cols-3 { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="bg-black shadow-lg sticky top-0 z-10">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <h1 class="text-3xl font-bold text-white">Prabhat's Workspace</h1>
                <nav class="hidden md:block">
                    <ul class="flex space-x-6">
                        <li><a href="/" class="text-white hover:text-gray-300 transition-colors">Home</a></li>
                        <li><a href="#about" class="text-white hover:text-gray-300 transition-colors">About Us</a></li>
                        <li><a href="#contact" class="text-white hover:text-gray-300 transition-colors">Contact Us</a></li>
                    </ul>
                </nav>
                <button class="md:hidden text-white focus:outline-none" onclick="document.getElementById('mobile-menu').classList.toggle('hidden')">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
            </div>
            <div id="mobile-menu" class="hidden md:hidden bg-black px-4 py-2">
                <ul class="space-y-2">
                    <li><a href="/" class="block text-white hover:text-gray-300">Home</a></li>
                    <li><a href="#about" class="block text-white hover:text-gray-300">About Us</a></li>
                    <li><a href="#contact" class="block text-white hover:text-gray-300">Contact Us</a></li>
                </ul>
            </div>
        </div>
    </header>

    <!-- Apps Grid -->
    <main class="container mx-auto px-4 py-8">
        <?php if (!empty($tag)): ?>
        <div class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Showing apps tagged with: #<?= sanitizeOutput($tag) ?></h2>
            <a href="/" class="text-blue-600 hover:text-blue-800">← Show all apps</a>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6" id="appContainer">
            <?php foreach ($apps as $app): ?>
            <div class="app-card rounded-xl overflow-hidden">
                <div class="relative">
                    <img src="<?= sanitizeOutput($app['thumbnail']) ?>" alt="<?= sanitizeOutput($app['name']) ?>" class="w-full">
                    <?php if ($app['is_downloadable']): ?>
                    <div class="download-icon" onclick="downloadApp(<?= $app['id'] ?>)" title="Download">
                        <i class="fas fa-download text-blue-600"></i>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="p-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2"><?= sanitizeOutput($app['name']) ?></h3>
                    
                    <?php if (!empty($app['description'])): ?>
                    <p class="text-gray-600 text-sm mb-3"><?= nl2br(sanitizeOutput($app['description'])) ?></p>
                    <?php endif; ?>
                    
                    <?php if (!empty($app['tags'])): ?>
                    <div class="flex flex-wrap gap-2 mb-3">
                        <?php foreach (explode(',', $app['tags']) as $tag): ?>
                        <a href="?tag=<?= urlencode(trim($tag)) ?>" 
                           class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-600 px-2 py-1 rounded-full transition-colors">
                            #<?= sanitizeOutput(trim($tag)) ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <div class="flex justify-between items-center">
                        <div class="stats space-x-3">
                            <span class="click-count">
                                <i class="fas fa-eye text-gray-400"></i>
                                <span class="count"><?= number_format($app['click_count']) ?></span>
                            </span>
                            <?php if ($app['is_downloadable']): ?>
                            <span class="download-count text-xs text-gray-500">
                                <i class="fas fa-download text-gray-400"></i>
                                <span class="count"><?= number_format($app['download_count']) ?></span>
                            </span>
                            <?php endif; ?>
                        </div>
                        <div class="share-buttons flex items-center">
                            <span class="text-xs text-gray-600 mr-2">Share:</span>
                            <a href="https://wa.me/?text=Check out <?= urlencode($app['name']) ?> at <?= urlencode($app['link']) ?>" 
                               target="_blank" class="whatsapp" title="Share on WhatsApp">
                                <i class="fab fa-whatsapp"></i>
                            </a>
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($app['link']) ?>" 
                               target="_blank" class="facebook" title="Share on Facebook">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="https://twitter.com/intent/tweet?text=Check out <?= urlencode($app['name']) ?>&url=<?= urlencode($app['link']) ?>" 
                               target="_blank" class="twitter" title="Share on Twitter">
                                <i class="fab fa-twitter"></i>
                            </a>
                        </div>
                    </div>

                    <div class="flex items-center justify-between mt-3">
                        <div class="price-info">
                            <span class="buy">Buy at ₹1999</span> | 
                            <span class="free">Online Access Free</span>
                        </div>
                    </div>

                    <?php if (!$app['is_downloadable']): ?>
                    <a href="<?= sanitizeOutput($app['link']) ?>" 
                       onclick="incrementClickCount(<?= $app['id'] ?>)"
                       class="block text-center mt-3 py-2 px-4 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">
                        Access Now
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- About Us Section -->
        <section id="about" class="my-12 bg-white rounded-xl shadow-md p-6 bg-gradient-to-br from-blue-50 to-purple-50">
            <h2 class="text-2xl font-bold text-indigo-700 mb-4">About Us</h2>
            <div class="prose max-w-none text-gray-700">
                <?= nl2br(sanitizeOutput(getPageContent('about_us'))) ?>
            </div>
        </section>

        <!-- Contact Us Section -->
        <section id="contact" class="my-12 bg-white rounded-xl shadow-md p-6 bg-gradient-to-br from-purple-50 to-blue-50">
            <h2 class="text-2xl font-bold text-purple-700 mb-4">Contact Us</h2>
            <div class="prose max-w-none text-gray-700">
                <?= nl2br(sanitizeOutput(getPageContent('contact_us'))) ?>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="bg-black text-white py-6">
        <div class="container mx-auto px-4 text-center">
            <p>© <?= date('Y') ?> Prabhat's Workspace. All rights reserved.</p>
        </div>
    </footer>

    <script>
        function incrementClickCount(appId) {
            fetch('click_counter.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'app_id=' + encodeURIComponent(appId)
            })
            .catch(error => console.error('Error:', error));
        }

        function downloadApp(appId) {
            event.preventDefault();
            event.stopPropagation();
            
            fetch('download_counter.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'app_id=' + encodeURIComponent(appId)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.needsConfirmation) {
                        if (confirm(data.message)) {
                            // User confirmed, proceed with download
                            fetch('download_counter.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: 'app_id=' + encodeURIComponent(appId) + '&confirm=true'
                            })
                            .then(response => response.json())
                            .then(downloadData => {
                                if (downloadData.success && downloadData.download_url) {
                                    window.location.href = downloadData.download_url;
                                } else {
                                    alert('Download failed. Please try again.');
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('Download failed. Please try again.');
                            });
                        }
                    } else if (data.download_url) {
                        window.location.href = data.download_url;
                    }
                } else {
                    alert(data.message || 'Download failed. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Download failed. Please try again.');
            });
        }
    </script>
</body>
</html>
