<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

$currentUser = getCurrentUser();

// التحقق من حالة العضوية
$user_membership = strtolower(trim($currentUser['membership_type'] ?? $currentUser['role'] ?? ''));
$is_user_vip = ($user_membership === 'vip' || $user_membership === '1' || $user_membership === 'premium');

$categories = getCategories();
$genres = getGenres();
$flash = getFlash();

$siteName = getSetting('site_name', 'Yuki');
$siteDesc = getSetting('site_description', 'منصة Yuki للبث السينمائي');

// تحديد رابط صورة LCP الرئيسية (سواء كانت من متغير الصفحة أو صورة افتراضية)
$lcpHeroImage = $heroPosterUrl ?? 'uploads/posters/hero-default.webp';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <meta name="description" content="<?php echo sanitize($siteDesc); ?>">
    <title><?php echo isset($pageTitle) ? sanitize($pageTitle) . ' | ' : ''; echo sanitize($siteName); ?></title>
    
    <!-- 1. تسريع شبكة السيرفرات الخارجية (Preconnect) -->
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>

    <!-- 2. حل مشكلة LCP الأساسي: طلب الصورة الرئيسية فوراً بأعلى أولوية (High Fetch Priority) -->
    <?php if (!empty($lcpHeroImage)): ?>
        <link rel="preload" as="image" href="<?php echo sanitize($lcpHeroImage); ?>" fetchpriority="high" type="image/webp">
    <?php endif; ?>

    <!-- 3. تحميل الخطوط والأيقونات دون حظر العرض (Non-Render-Blocking CSS) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"></noscript>

    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&family=Tajawal:wght@400;700;800&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&family=Tajawal:wght@400;700;800&display=swap" rel="stylesheet"></noscript>

    <!-- 4. ملف التنسيقات الرئيسي -->
    <link rel="stylesheet" href="assets/css/style.css">
    <?php if (isset($extraCss)) echo $extraCss; ?>

    <?php if (!$is_user_vip): ?>
        <!-- Google AdSense (للغير VIP فقط) -->
        <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-1011861128324529" crossorigin="anonymous"></script>
        
        <!-- كود كشف مانع الإعلانات الخفيف -->
        <script>
        (function() {
            function triggerBlockModal() {
                if (document.getElementById('anti-ab-overlay')) return;
                let overlay = document.createElement('div');
                overlay.id = 'anti-ab-overlay';
                overlay.style.cssText = 'position:fixed; top:0; left:0; width:100%; height:100vh; background:rgba(10, 10, 18, 0.96); z-index:9999999; display:flex; justify-content:center; align-items:center; color:#fff; font-family:"Cairo", sans-serif; text-align:center; padding:15px; box-sizing:border-box;';
                
                overlay.innerHTML = `
                    <div style="background:#181824; padding:25px 20px; border-radius:16px; border:1px solid #e94560; max-width:380px; width:100%; box-shadow:0 10px 30px rgba(0,0,0,0.8);">
                        <i class="fas fa-shield-alt" style="font-size:45px; color:#e94560; margin-bottom:12px;"></i>
                        <h3 style="margin:0 0 10px 0; font-size:19px; font-weight:700;">تم كشف مانع الإعلانات / DNS</h3>
                        <p style="color:#aaa; font-size:13px; line-height:1.6; margin-bottom:20px;">
                            يرجى تعطيل مانع الإعلانات أو إلغاء تفعيل <b>AdGuard DNS</b> لمتابعة المشاهدة، أو الترقية لعضوية VIP.
                        </p>
                        <button onclick="location.reload()" style="background:#e94560; color:#fff; border:none; padding:12px 20px; border-radius:8px; cursor:pointer; font-weight:bold; font-size:14px; width:100%; display:flex; align-items:center; justify-content:center; gap:8px;">
                            <i class="fas fa-sync-alt"></i> إعـادة تحميـل الصفحـة
                        </button>
                    </div>
                `;
                document.body.appendChild(overlay);
                document.body.style.overflow = 'hidden';
            }

            function checkAdBlockAndDNS() {
                let testAd = document.createElement('div');
                testAd.innerHTML = '&nbsp;';
                testAd.className = 'adsbox google-ad ad-zone banner-ad ad-space';
                testAd.style.cssText = 'position:absolute !important; top:-9999px !important; left:-9999px !important; width:1px !important; height:1px !important; display:block !important;';
                document.body.appendChild(testAd);

                window.setTimeout(function() {
                    if (testAd.offsetHeight === 0 || testAd.clientHeight === 0 || window.getComputedStyle(testAd).display === 'none') {
                        testAd.remove();
                        triggerBlockModal();
                        return;
                    }
                    testAd.remove();
                }, 600);
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', checkAdBlockAndDNS);
            } else {
                checkAdBlockAndDNS();
            }
        })();
        </script>
    <?php endif; ?>

    <style>
        /* إجبار إظهار النص فوراً بالخط البديل لحين تحميل الخط الكلي لمنع تأخير FCP/LCP */
        @font-face { font-display: swap; }

        @media screen and (max-width: 768px) {
            .navbar .logo { display: none !important; }
            .navbar { position: fixed; top: 0; left: 0; width: 100%; z-index: 1000; }
            .nav-container { position: relative !important; }
            .search-box { flex-grow: 1 !important; margin: 0 6px !important; }
            .search-box input { width: 100% !important; font-size: 14px !important; padding: 6px 10px !important; }
            .user-toggle { gap: 8px !important; display: flex !important; align-items: center !important; }
            .user-toggle img { width: 38px !important; height: 38px !important; border-radius: 50% !important; }
            .user-toggle span { display: inline-block !important; font-size: 14px !important; max-width: 95px !important; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .user-toggle .fa-chevron-down { display: none !important; }
            .nav-links { display: none !important; flex-direction: column !important; position: absolute !important; top: 100% !important; right: 0 !important; width: 100% !important; padding: 15px !important; z-index: 9999 !important; }
            .nav-links.active, .nav-links.show { display: flex !important; }
            .user-menu .user-dropdown { display: none !important; position: absolute !important; left: 0 !important; top: 100% !important; min-width: 200px; border-radius: 8px; padding: 10px 0; z-index: 9999 !important; }
            .user-menu.active .user-dropdown, .user-menu.show .user-dropdown { display: block !important; }
            .nav-dropdown { position: relative !important; }
            .nav-dropdown .dropdown-menu { display: none !important; position: absolute !important; left: 0 !important; top: 100% !important; min-width: 200px; border-radius: 8px; padding: 10px 0; z-index: 9999 !important; }
            .nav-dropdown.active .dropdown-menu, .nav-dropdown.show .dropdown-menu { display: block !important; }
            .mobile-toggle { display: block !important; background: transparent; border: none; color: inherit; font-size: 22px; cursor: pointer; padding: 6px 10px; }
        }
    </style>

  
</head>
<body>
    <?php if ($flash): ?>
    <div class="flash-message flash-<?php echo sanitize($flash['type']); ?>" id="flashMessage">
        <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'error' ? 'exclamation-circle' : 'info-circle'); ?>"></i>
        <?php echo sanitize($flash['message']); ?>
        <button class="flash-close" onclick="this.parentElement.remove()">&times;</button>
    </div>
    <?php endif; ?>

    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">
                <i class="fas fa-play-circle"></i>
                <span><?php echo sanitize($siteName); ?></span>
            </a>

            <div class="nav-links" id="navLinks">
                <a href="index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i> الرئيسية
                </a>
                <a href="movies.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'movies.php' ? 'active' : ''; ?>">
                    <i class="fas fa-film"></i> الأفلام
                </a>
                <a href="series.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'series.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tv"></i> المسلسلات
                </a>
                <div class="nav-dropdown" id="navDropdownMobile">
                    <a href="javascript:void(0);" class="nav-link" onclick="toggleCategories(event)">
                        <i class="fas fa-th-list"></i> التصنيفات <i class="fas fa-chevron-down"></i>
                    </a>
                    <div class="dropdown-menu">
                        <?php if (!empty($categories) && is_array($categories)): ?>
                            <?php foreach ($categories as $cat): ?>
                            <a href="category.php?slug=<?php echo urlencode($cat['slug']); ?>">
                                <i class="fas fa-<?php echo sanitize($cat['icon'] ?? 'folder'); ?>"></i> <?php echo sanitize($cat['name']); ?>
                            </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="nav-right">
                <form action="search.php" method="GET" class="search-box">
                    <input type="text" name="q" placeholder="ابحث عن فيلم أو مسلسل..." 
                           value="<?php echo isset($_GET['q']) ? sanitize($_GET['q']) : ''; ?>" required>
                    <button type="submit" aria-label="بحث"><i class="fas fa-search"></i></button>
                </form>

                <?php if ($currentUser): ?>
                <div class="user-menu" id="userMenuMobile">
                    <a href="javascript:void(0);" class="user-toggle" onclick="toggleUserMenu(event)">
                        <?php 
                        if (!empty($currentUser['avatar']) && $currentUser['avatar'] !== 'default-avatar.png' && file_exists('uploads/avatars/' . $currentUser['avatar'])) {
                            $headerAvatar = 'uploads/avatars/' . sanitize($currentUser['avatar']);
                        } else {
                            $headerAvatar = 'uploads/avatars/default-avatar.png';
                        }
                        ?>
                        <img src="<?php echo $headerAvatar; ?>?t=<?php echo time(); ?>" 
                             width="38" height="38"
                             alt="<?php echo sanitize($currentUser['username'] ?? ''); ?>">
                        
                        <span class="<?php echo $is_user_vip ? 'vip-name' : ''; ?>">
                            <?php echo sanitize($currentUser['username'] ?? ''); ?>
                        </span>
                        <i class="fas fa-chevron-down"></i>
                    </a>
                    <div class="user-dropdown">
                        <?php if ($is_user_vip): ?>
                            <div class="nav-vip-badge" style="background: linear-gradient(135deg, #ffd700, #ffa500); color: #000; font-weight: bold; padding: 6px 12px; border-radius: 6px; font-size: 13px; display: flex; align-items: center; gap: 6px; margin: 5px 10px;">
                                <i class="fas fa-crown"></i> عضو VIP 👑
                            </div>
                        <?php else: ?>
                            <a href="upgrade-vip.php" class="nav-upgrade-btn" style="color: #ffd700; border: 1px dashed #ffd700; padding: 8px 12px; border-radius: 6px; font-size: 13px; display: flex; align-items: center; gap: 6px; margin: 5px 10px;">
                                <i class="fas fa-rocket"></i> ترقية إلى VIP 🚀
                            </a>
                        <?php endif; ?>

                        <div class="dropdown-divider"></div>
                        
                        <a href="profile.php"><i class="fas fa-user"></i> الملف الشخصي</a>
                        <a href="favorites.php"><i class="fas fa-heart"></i> المفضلة</a>
                        <?php if (function_exists('isAdmin') && isAdmin()): ?>
                        <a href="admin/index.php"><i class="fas fa-cog"></i> لوحة التحكم</a>
                        <?php endif; ?>
                        <div class="dropdown-divider"></div>
                        <a href="logout.php" class="text-danger"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
                    </div>
                </div>
                <?php else: ?>
                <div class="auth-buttons">
                    <a href="login.php" class="btn btn-outline">تسجيل الدخول</a>
                    <a href="register.php" class="btn btn-primary">إنشاء حساب</a>
                </div>
                <?php endif; ?>

                <button class="mobile-toggle" id="mobileToggle" type="button" onclick="toggleNavMenu(event)" aria-label="فتح القائمة">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </nav>
    <div class="nav-spacer"></div>