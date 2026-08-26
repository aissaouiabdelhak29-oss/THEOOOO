<?php
$siteName = getSetting('site_name', 'Yuki');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? sanitize($pageTitle) . ' | ' : ''; ?>إدارة <?php echo sanitize($siteName); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&family=Tajawal:wght@300;400;500;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-body">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <a href="index.php" class="admin-sidebar-brand">
            <i class="fas fa-play-circle"></i>
            <span><?php echo sanitize($siteName); ?> Admin</span>
        </a>

        <nav class="admin-nav">
            <div class="admin-nav-title">القائمة الرئيسية</div>
            <a href="index.php" class="admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i> لوحة التحكم
            </a>

            <div class="admin-nav-title">المحتوى</div>
            <a href="content.php" class="admin-nav-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['content.php', 'content-add.php', 'content-edit.php']) ? 'active' : ''; ?>">
                <i class="fas fa-film"></i> المحتوى
            </a>
            <a href="episodes.php" class="admin-nav-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['episodes.php', 'episode-add.php', 'episode-edit.php']) ? 'active' : ''; ?>">
                <i class="fas fa-list"></i> الحلقات
            </a>
            <a href="categories.php" class="admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'categories.php' ? 'active' : ''; ?>">
                <i class="fas fa-th-list"></i> التصنيفات
            </a>
            <a href="genres.php" class="admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'genres.php' ? 'active' : ''; ?>">
                <i class="fas fa-tags"></i> الأنواع
            </a>

            <div class="admin-nav-title">الإدارة</div>
            <a href="users.php" class="admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> المستخدمين
            </a>
            <a href="settings.php" class="admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i> الإعدادات
            </a>

            <div class="admin-nav-title">روابط سريعة</div>
            <a href="../index.php" target="_blank" class="admin-nav-link">
                <i class="fas fa-external-link-alt"></i> الموقع
            </a>
            <a href="../logout.php" class="admin-nav-link">
                <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
            </a>
        </nav>
    </aside>

    <!-- Mobile Toggle -->
    <button class="mobile-toggle" id="sidebarToggle" style="position: fixed; top: 20px; left: 20px; z-index: 1001; background: var(--surface-light); border: 1px solid var(--border); width: 44px; height: 44px; border-radius: 50%;">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Main Content -->
    <main class="admin-main">
