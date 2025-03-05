<?php
require_once 'functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prabhat's Workspace</title>
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
    <!-- Header -->
    <header class="bg-white shadow-sm">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <h1 class="text-3xl font-bold text-gray-800">Prabhat's Workspace</h1>
                <nav>
                    <ul class="flex space-x-6">
                        <li><a href="#" class="text-gray-600 hover:text-gray-900">Home</a></li>
                        <li><a href="#about" class="text-gray-600 hover:text-gray-900">About Us</a></li>
                        <li><a href="#contact" class="text-gray-600 hover:text-gray-900">Contact Us</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <!-- Apps Grid -->
    <main class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php
            $apps = getApps();
            foreach ($apps as $app):
            ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden transition-transform hover:scale-105">
                <img src="<?= sanitizeOutput($app['thumbnail']) ?>" alt="<?= sanitizeOutput($app['name']) ?>" 
                     class="w-full h-48 object-cover">
                <div class="p-4">
                    <h3 class="text-xl font-semibold text-gray-800 mb-2"><?= sanitizeOutput($app['name']) ?></h3>
                    <div class="flex items-center text-sm text-gray-500 mb-3">
                        <i class="fas fa-mouse-pointer mr-2"></i>
                        <span><?= number_format($app['click_count']) ?> clicks</span>
                    </div>
                    <a href="<?= sanitizeOutput($app['link']) ?>" 
                       onclick="incrementClickCount(<?= $app['id'] ?>)"
                       class="block text-center py-2 px-4 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">
                        Buy at ₹1999 | Online Access Free
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- About Us Section -->
        <section id="about" class="my-12 bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">About Us</h2>
            <div class="prose max-w-none">
                <?= nl2br(sanitizeOutput(getPageContent('about_us'))) ?>
            </div>
        </section>

        <!-- Contact Us Section -->
        <section id="contact" class="my-12 bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Contact Us</h2>
            <div class="prose max-w-none">
                <?= nl2br(sanitizeOutput(getPageContent('contact_us'))) ?>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-6">
        <div class="container mx-auto px-4 text-center">
            <p>&copy; <?= date('Y') ?> Prabhat's Workspace. All rights reserved.</p>
        </div>
    </footer>

    <script>
    function incrementClickCount(appId) {
        fetch('click_counter.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'app_id=' + appId
        });
    }
    </script>
</body>
</html>
