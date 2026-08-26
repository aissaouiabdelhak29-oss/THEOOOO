<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$slug = $_GET['slug'] ?? '';
$episodeId = isset($_GET['ep']) ? intval($_GET['ep']) : 0;

$content = getContentBySlug($slug);

if (!$content) {
    redirect('index.php', 'المحتوى غير موجود', 'error');
}

// Increment views
incrementViews($content['id']);

// ==================== [منح نقاط XP للمشاهدة] ====================
if (isLoggedIn()) {
    addXpToUser($_SESSION['user_id'], 15, 'watched_movie');
}
// ==============================================================

$pageTitle = sanitize($content['title']);

// Get video servers
$servers = getVideoServers($content['id']);

// Get episodes for series
$episodes = [];
$seasons = [];
$currentEpisode = null;
$episodeServers = [];

if ($content['type'] === 'series') {
    $seasons = getSeasons($content['id']);
    $episodes = getEpisodes($content['id']);

    if ($episodeId > 0) {
        foreach ($episodes as $ep) {
            if ($ep['id'] == $episodeId) {
                $currentEpisode = $ep;
                break;
            }
        }
    }

    if (!$currentEpisode && !empty($episodes)) {
        $currentEpisode = $episodes[0];
        $episodeId = $currentEpisode['id'];
    }

    if ($currentEpisode) {
        $episodeServers = getEpisodeServers($currentEpisode['id']);
    }
}

// Determine which servers to use
$activeServers = $content['type'] === 'series' && $currentEpisode ? $episodeServers : $servers;
$defaultServer = !empty($activeServers) ? $activeServers[0] : null;

// Check favorite status
$favorite = false;
if (isLoggedIn()) {
    $favorite = isFavorite($_SESSION['user_id'], $content['id']);
}

// Get related content
$related = getRelatedContent($content['id'], $content['type'], 6);


// ==================== [معالجة نظام التعليقات المطور والتحكم بالصلاحيات والعضويات] ====================
$comment_errors = [];
$item_id = intval($content['id']); 
$current_user_id = $_SESSION['user_id'] ?? null;

// جلب دور المستخدم الحالي (هل هو مسؤول/مشرف؟)
$is_admin = false;
if (isLoggedIn() && isset($_SESSION['user_role'])) {
    $is_admin = ($_SESSION['user_role'] === 'admin');
} elseif (isLoggedIn() && isset($currentUser['role'])) {
    $is_admin = ($currentUser['role'] === 'admin');
}

// 1. استقبال ومعالجة إضافة تعليق جديد (عبر الـ POST التقليدي كاحتياط أو Ajax)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        exit('Invalid security token.');
    }
    if (!isLoggedIn()) {
        $comment_errors[] = 'يجب تسجيل الدخول أولاً لتتمكن من التعليق.';
    } else {
        $comment_text = trim($_POST['comment_text'] ?? '');

        if (empty($comment_text)) {
            $comment_errors[] = 'لا يمكنك نشر تعليق فارغ.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO comments (user_id, item_id, comment_text) VALUES (?, ?, ?)");
            if ($stmt->execute([$current_user_id, $item_id, $comment_text])) {
                
                // ==================== [منح نقاط XP عند كتابة تعليق] ====================
                addXpToUser($current_user_id, 5, 'added_comment');
                // ====================================================================

                // إذا كان الطلب قادماً عبر Ajax سنرجع استجابة JSON
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    echo json_encode(['success' => true]);
                    exit;
                }
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit;
            }
        }
    }
}

// 2. معالجة حذف التعليق (عبر Ajax وبدون ريفريش)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_comment'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        exit('Invalid security token.');
    }
    if (isLoggedIn()) {
        $comment_id = intval($_POST['comment_id'] ?? 0);
        $check_stmt = $pdo->prepare("SELECT user_id FROM comments WHERE id = ?");
        $check_stmt->execute([$comment_id]);
        $comment_owner = $check_stmt->fetchColumn();

        if ($comment_owner == $current_user_id || $is_admin) {
            $delete_stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
            if ($delete_stmt->execute([$comment_id])) {
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    echo json_encode(['success' => true]);
                    exit;
                }
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit;
            }
        }
    }
}

// 3. معالجة تعديل التعليق (عبر Ajax وبدون ريفريش)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_comment'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        exit('Invalid security token.');
    }
    if (isLoggedIn()) {
        $comment_id = intval($_POST['comment_id'] ?? 0);
        $new_text = trim($_POST['new_comment_text'] ?? '');

        if (!empty($new_text)) {
            $check_stmt = $pdo->prepare("SELECT user_id FROM comments WHERE id = ?");
            $check_stmt->execute([$comment_id]);
            $comment_owner = $check_stmt->fetchColumn();

            if ($comment_owner == $current_user_id) {
                $update_stmt = $pdo->prepare("UPDATE comments SET comment_text = ? WHERE id = ?");
                if ($update_stmt->execute([$new_text, $comment_id])) {
                    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                        echo json_encode(['success' => true, 'new_text' => htmlspecialchars($new_text, ENT_QUOTES, 'UTF-8')]);
                        exit;
                    }
                    header("Location: " . $_SERVER['REQUEST_URI']);
                    exit;
                }
            }
        }
    }
}

// 4. استعلام جلب التعليقات مع الترتيب الذكي والآمن للغة العربية ونوع العضوية
$stmt = $pdo->prepare("
    SELECT c.*, u.username, u.role, u.membership_type 
    FROM comments c
    JOIN users u ON c.user_id = u.id
    WHERE c.item_id = ?
    ORDER BY 
        CASE WHEN u.role = 'admin' THEN 0 ELSE 1 END ASC,
        c.likes_count DESC,
        c.created_at DESC
");
$stmt->execute([$item_id]);
$comments = $stmt->fetchAll();

// ==================== [تسجيل المشاهدة في جدول watch_history] ====================
if (isLoggedIn() && isset($_SESSION['user_id']) && isset($content['id'])) {
    $userId = intval($_SESSION['user_id']);
    $contentId = intval($content['id']);
    $epId = ($content['type'] === 'series' && isset($currentEpisode['id'])) ? intval($currentEpisode['id']) : null;

    // التحقق هل تمت مشاهدة هذا المحتوى أو هذه الحلقة مسبقاً
    if ($epId !== null) {
        $stmtCheck = $pdo->prepare("SELECT id FROM watch_history WHERE user_id = ? AND content_id = ? AND episode_id = ? LIMIT 1");
        $stmtCheck->execute([$userId, $contentId, $epId]);
    } else {
        $stmtCheck = $pdo->prepare("SELECT id FROM watch_history WHERE user_id = ? AND content_id = ? AND (episode_id IS NULL OR episode_id = 0) LIMIT 1");
        $stmtCheck->execute([$userId, $contentId]);
    }
    
    if (!$stmtCheck->fetch()) {
        $stmtInsert = $pdo->prepare("INSERT INTO watch_history (user_id, content_id, episode_id, watched_at) VALUES (?, ?, ?, NOW())");
        $stmtInsert->execute([$userId, $contentId, $epId]);
    }
}
// ==============================================================================

require_once 'includes/header.php';
?>


<div class="watch-page">
    <div class="player-section">
        <div class="player-container">
            <?php if ($defaultServer): ?>
            <iframe id="playerFrame" src="<?php echo sanitize($defaultServer['embed_url']); ?>" 
                    allowfullscreen 
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    referrerpolicy="no-referrer"
                    frameborder="0"
                    scrolling="no">
            </iframe>
            <div class="player-loading" id="playerLoading" style="display: none;">
                <i class="fas fa-spinner"></i>
                <p>جاري تحميل الفيديو...</p>
            </div>
            <?php else: ?>
            <div class="player-loading" style="display: flex;">
                <i class="fas fa-exclamation-triangle" style="animation: none; color: var(--warning);"></i>
                <p>لا يوجد سيرفرات متاحة حالياً</p>
            </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($activeServers)): ?>
        <div class="server-selector">
            <div class="server-selector-title">
                <i class="fas fa-server"></i>
                اختر سيرفر المشاهدة
            </div>
            <div class="server-buttons">
                <?php foreach ($activeServers as $index => $server): ?>
                <button type="button" class="server-btn <?php echo $index === 0 ? 'active' : ''; ?>" 
                        data-embed-url="<?php echo sanitize($server['embed_url']); ?>"
                        data-server="<?php echo $server['server_name']; ?>">
                    <i class="fas <?php echo getServerIcon($server['server_name']); ?>"></i>
                    <?php echo getServerName($server['server_name']); ?>
                    <span class="server-quality"><?php echo $server['quality']; ?></span>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="watch-info">
        <div class="watch-poster">
            <img src="<?php echo $content['poster'] ? 'uploads/posters/' . sanitize($content['poster']) : 'assets/images/default-poster.jpg'; ?>" 
                 alt="<?php echo sanitize($content['title']); ?>">
        </div>
        <div class="watch-details">
            <h1><?php echo sanitize($content['title']); ?></h1>
            <div class="watch-meta">
                <span class="rating"><i class="fas fa-star"></i> <?php echo $content['rating']; ?></span>
                <span><i class="fas fa-calendar"></i> <?php echo $content['year']; ?></span>
                <?php if ($content['duration']): ?>
                <span><i class="fas fa-clock"></i> <?php echo formatDuration($content['duration']); ?></span>
                <?php endif; ?>
                <span class="quality-tag"><?php echo $content['quality']; ?></span>
                <?php if ($content['category_name']): ?>
                <span><i class="fas fa-folder"></i> <?php echo sanitize($content['category_name']); ?></span>
                <?php endif; ?>
                <?php if ($content['genre_name']): ?>
                <span><i class="fas fa-tag"></i> <?php echo sanitize($content['genre_name']); ?></span>
                <?php endif; ?>
            </div>
            <p class="watch-desc"><?php echo nl2br(sanitize($content['description'] ?? 'لا يوجد وصف')); ?></p>
            
            <div class="watch-actions">
                <?php if (isLoggedIn()): ?>
                <button type="button" class="btn btn-favorite <?php echo $favorite ? 'active' : ''; ?>" id="favoriteBtn" data-content-id="<?php echo $content['id']; ?>">
                    <i class="<?php echo $favorite ? 'fas' : 'far'; ?> fa-heart"></i>
                    <span><?php echo $favorite ? 'في المفضلة' : 'أضف للمفضلة'; ?></span>
                </button>
                <input type="hidden" id="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <?php else: ?>
                <a href="login.php" class="btn btn-favorite">
                    <i class="far fa-heart"></i> أضف للمفضلة
                </a>
                <?php endif; ?>

                <?php if ($content['trailer']): ?>
                <a href="<?php echo sanitize($content['trailer']); ?>" target="_blank" class="btn btn-outline">
                    <i class="fas fa-film"></i> مشاهدة الإعلان
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($content['type'] === 'series' && !empty($seasons)): ?>
    <div class="episode-section">
        <div class="section-header" style="padding: 0; margin-bottom: 20px;">
            <h2 class="section-title"><i class="fas fa-list"></i> الحلقات</h2>
        </div>

        <div class="episode-tabs">
            <?php foreach ($seasons as $season): ?>
            <button type="button" class="episode-tab <?php echo $season == ($currentEpisode['season'] ?? 1) ? 'active' : ''; ?>" 
                    data-season="<?php echo $season; ?>">
                الموسم <?php echo $season; ?>
            </button>
            <?php endforeach; ?>
        </div>

        <?php foreach ($seasons as $season): ?>
        <div class="episode-grid episode-tab-content" data-season="<?php echo $season; ?>" 
             style="<?php echo $season != ($currentEpisode['season'] ?? 1) ? 'display: none;' : ''; ?>">
            <?php foreach ($episodes as $ep): ?>
            <?php if ($ep['season'] == $season): ?>
            <a href="watch.php?slug=<?php echo urlencode($content['slug']); ?>&ep=<?php echo $ep['id']; ?>" 
               class="episode-card <?php echo $currentEpisode && $currentEpisode['id'] == $ep['id'] ? 'active' : ''; ?>">
                <div class="episode-thumb">
                    <img src="<?php echo $ep['poster'] ? 'uploads/episodes/' . sanitize($ep['poster']) : ($content['poster'] ? 'uploads/posters/' . sanitize($content['poster']) : 'assets/images/default-poster.jpg'); ?>" 
                         alt="<?php echo sanitize($ep['title']); ?>">
                    <span class="ep-num">حلقة <?php echo $ep['episode_number']; ?></span>
                </div>
                <div class="episode-info">
                    <h4><?php echo sanitize($ep['title']); ?></h4>
                    <p><?php echo mb_substr(sanitize($ep['description'] ?? ''), 0, 80) . '...'; ?></p>
                    <?php if ($ep['duration']): ?>
                    <span style="color: var(--text-muted); font-size: 12px;">
                        <i class="fas fa-clock"></i> <?php echo formatDuration($ep['duration']); ?>
                    </span>
                    <?php endif; ?>
                </div>
            </a>
            <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <style>
        .comments-section { background: #13131a; padding: 30px; border-radius: 15px; font-family: 'Cairo', sans-serif; margin-top: 40px; border: 1px solid #1e1e2f; direction: rtl; }
        .comments-section h3 { font-size: 20px; color: #fff; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; }
        
        .comment-form { margin-bottom: 35px; }
        .comment-form textarea { width: 100%; background: #0c0c12; border: 1px solid #222232; border-radius: 10px; padding: 15px; color: #fff; font-family: 'Cairo'; resize: none; min-height: 110px; transition: 0.3s; text-align: right; }
        .comment-form textarea:focus { border-color: #ff2a5f; outline: none; }
        .btn-comment { background: #ff2a5f; color: #fff; border: none; padding: 11px 26px; border-radius: 8px; cursor: pointer; font-family: 'Cairo'; font-weight: 600; margin-top: 12px; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-comment:hover { background: #e02050; }

        .comment-card { background: #0c0c12; border: 1px solid #1e1e2f; padding: 20px; border-radius: 12px; margin-bottom: 15px; transition: 0.3s; position: relative; }
        .comment-card.admin-comment { border-right: 5px solid #ff2a5f; background: rgba(255, 42, 95, 0.02); }
        
        .comment-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .user-info { display: flex; align-items: center; gap: 10px; }
        .username { font-weight: bold; font-size: 15px; color: #fff; }
        
        .badge { padding: 3px 8px; font-size: 11px; border-radius: 4px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; }
        .badge-admin { background: #ff2a5f; color: #fff; }
        .badge-user { background: #222232; color: #a0a0b0; }
        
        /* تنسيقات العضوية الـ VIP الذهبية */
        .badge-vip { background: linear-gradient(135deg, #ffd700, #ffa500); color: #000; font-weight: bold; box-shadow: 0 0 8px rgba(255, 215, 0, 0.5); }
        .vip-username { color: #ffd700 !important; text-shadow: 0 0 3px rgba(255, 215, 0, 0.3); }

        .comment-body { color: #cacaca; font-size: 15px; line-height: 1.6; text-align: right; white-space: pre-line; }
        .comment-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 15px; font-size: 13px; border-top: 1px solid #1c1c2b; padding-top: 10px; }
        
        .like-btn { background: none; border: none; color: #00b4d8; cursor: pointer; display: flex; align-items: center; gap: 6px; font-family: 'Cairo'; padding: 0; transition: 0.2s; }
        .like-btn:hover { color: #0077b6; }

        .comment-actions-btns { display: flex; gap: 10px; }
        .action-btn { background: none; border: none; font-family: 'Cairo'; cursor: pointer; font-size: 12px; display: inline-flex; align-items: center; gap: 4px; padding: 3px 8px; border-radius: 4px; transition: 0.2s; }
        .btn-edit-trigger { color: #ffb703; }
        .btn-edit-trigger:hover { background: rgba(255, 183, 3, 0.1); }
        .btn-delete-trigger { color: #ef233c; }
        .btn-delete-trigger:hover { background: rgba(239, 35, 60, 0.1); }

        .inline-edit-form { display: none; margin-top: 10px; width: 100%; }
        .inline-edit-form textarea { width: 100%; background: #181824; border: 1px solid #ffb703; border-radius: 6px; padding: 10px; color: #fff; font-family: 'Cairo'; resize: vertical; }
        .edit-actions { margin-top: 8px; display: flex; gap: 8px; }
        .btn-save { background: #ffb703; color: #000; border: none; padding: 5px 15px; border-radius: 4px; cursor: pointer; font-family: 'Cairo'; font-weight: bold; }
        .btn-cancel { background: #222232; color: #fff; border: none; padding: 5px 15px; border-radius: 4px; cursor: pointer; font-family: 'Cairo'; }
    </style>

    <div class="comments-section">
        <h3><i class="fas fa-comments"></i> تعليقات المشاهدين (<?php echo count($comments); ?>)</h3>

        <?php if (!empty($comment_errors)): ?>
            <?php foreach ($comment_errors as $err): ?>
                <div style="color: #ff2a5f; margin-bottom: 12px; font-size: 14px; text-align: right;"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (isLoggedIn()): ?>
            <form method="POST" action="" class="comment-form">
                <textarea name="comment_text" placeholder="اكتب رأيك الصريح عن هذا العمل ..." required></textarea>
                <button type="submit" name="submit_comment" class="btn-comment">
                    <i class="fas fa-paper-plane"></i> نشر التعليق
                </button>
            </form>
        <?php else: ?>
            <p style="background: #0c0c12; padding: 15px; border-radius: 8px; text-align: center; color: #a0a0b0; font-size: 14px; margin-bottom: 30px; border: 1px dashed #222232;">
                يرجى <a href="login.php" style="color: #ff2a5f; text-decoration: none; font-weight: bold;">تسجيل الدخول</a> لتتمكن من إضافة تعليقك.
            </p>
        <?php endif; ?>

        <div class="comments-list">
            <?php if (empty($comments)): ?>
                <p style="color: #727282; text-align: center; padding: 20px;">لا توجد تعليقات بعد، شاركنا رأيك أولاً!</p>
            <?php else: ?>
                <?php foreach ($comments as $comment): ?>
                    <div class="comment-card <?php echo ($comment['role'] === 'admin') ? 'admin-comment' : ''; ?>" id="comment-block-<?php echo $comment['id']; ?>">
                        <div class="comment-header">
                            <div class="user-info">
                                <i class="fas fa-user-circle" style="font-size: 22px; color: <?php 
                                    if ($comment['role'] === 'admin') { echo '#ff2a5f'; }
                                    elseif ($comment['membership_type'] === 'vip') { echo '#ffd700'; }
                                    else { echo '#52527a'; }
                                ?>;"></i>
                                
                                <span class="username <?php echo ($comment['membership_type'] === 'vip' && $comment['role'] !== 'admin') ? 'vip-username' : ''; ?>">
                                    <?php echo htmlspecialchars($comment['username'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                                
                                <?php if ($comment['role'] === 'admin'): ?>
                                    <span class="badge badge-admin"><i class="fas fa-crown"></i> مدير المنصة</span>
                                <?php endif; ?>

                                <?php if ($comment['membership_type'] === 'vip'): ?>
                                    <span class="badge badge-vip"><i class="fas fa-star"></i> VIP</span>
                                <?php elseif ($comment['role'] !== 'admin'): ?>
                                    <span class="badge badge-user">عضو</span>
                                <?php endif; ?>
                            </div>
                            <span style="font-size: 12px; color: #727282;"><i class="fas fa-clock"></i> <?php echo date('Y/m/d', strtotime($comment['created_at'])); ?></span>
                        </div>
                        
                        <div class="comment-body" id="text-<?php echo $comment['id']; ?>"><?php echo nl2br(htmlspecialchars($comment['comment_text'], ENT_QUOTES, 'UTF-8')); ?></div>
                        
                        <?php if (isLoggedIn() && $comment['user_id'] == $current_user_id): ?>
                            <form method="POST" action="" class="inline-edit-form" id="edit-form-<?php echo $comment['id']; ?>">
                                <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                <textarea name="new_comment_text" required><?php echo htmlspecialchars($comment['comment_text'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                                <div class="edit-actions">
                                    <button type="submit" name="edit_comment" class="btn-save"><i class="fas fa-check"></i> حفظ</button>
                                    <button type="button" class="btn-cancel" onclick="toggleEdit(<?php echo $comment['id']; ?>)">إلغاء</button>
                                </div>
                            </form>
                        <?php endif; ?>

                        <div class="comment-footer">
                            <button class="like-btn" data-id="<?php echo $comment['id']; ?>">
                                <i class="fas fa-thumbs-up"></i> أعجبني (<span><?php echo intval($comment['likes_count']); ?></span>)
                            </button>

                            <div class="comment-actions-btns">
                                <?php if (isLoggedIn() && $comment['user_id'] == $current_user_id): ?>
                                    <button type="button" class="action-btn btn-edit-trigger" onclick="toggleEdit(<?php echo $comment['id']; ?>)">
                                        <i class="fas fa-edit"></i> تعديل
                                    </button>
                                <?php endif; ?>

                                <?php if (isLoggedIn() && ($comment['user_id'] == $current_user_id || $is_admin)): ?>
                                    <form method="POST" action="" style="display:inline;">
                                        <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                        <button type="button" class="action-btn btn-delete-trigger">
                                            <i class="fas fa-trash-alt"></i> حذف
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php if (!empty($related)): ?>
    <section class="section" style="padding: 40px 0;">
        <div class="section-header" style="padding: 0;">
            <h2 class="section-title"><i class="fas fa-thumbs-up"></i> قد يعجبك أيضاً</h2>
        </div>
        <div class="content-grid" style="padding: 0;">
            <?php foreach ($related as $item): ?>
            <a href="watch.php?slug=<?php echo urlencode($item['slug']); ?>" class="content-card">
                <div class="card-img-wrap">
                    <img src="<?php echo $item['poster'] ? 'uploads/posters/' . sanitize($item['poster']) : 'assets/images/default-poster.jpg'; ?>" 
                         alt="<?php echo sanitize($item['title']); ?>" class="card-img" loading="lazy">
                    <div class="card-overlay">
                        <div class="card-play"><i class="fas fa-play"></i></div>
                    </div>
                    <span class="card-badge quality"><?php echo $item['quality']; ?></span>
                </div>
                <div class="card-info">
                    <h3 class="card-title"><?php echo sanitize($item['title']); ?></h3>
                    <div class="card-meta">
                        <span><i class="fas fa-star"></i> <?php echo $item['rating']; ?></span>
                        <span><i class="fas fa-calendar"></i> <?php echo $item['year']; ?></span>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
</div>

<script>
// دالة إظهار وإخفاء حقل تعديل التعليق
function toggleEdit(commentId) {
    const textBlock = document.getElementById('text-' + commentId);
    const editForm = document.getElementById('edit-form-' + commentId);
    
    if (editForm.style.display === 'none' || editForm.style.display === '') {
        textBlock.style.display = 'none';
        editForm.style.display = 'block';
    } else {
        textBlock.style.display = 'block';
        editForm.style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    
    // === [1. تعديل التعليق بدون تحديث الصفحة Ajax] ===
    document.querySelectorAll('.inline-edit-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const currentForm = this;
            const commentId = currentForm.querySelector('input[name="comment_id"]').value;
            const textBlock = document.getElementById('text-' + commentId);
            
            const formData = new FormData(currentForm);
            formData.append('edit_comment', '1');
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData,
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    textBlock.innerHTML = data.new_text.replace(/\n/g, "<br />");
                    toggleEdit(commentId);
                }
            });
        });
    });

    // === [2. حذف التعليق بدون تحديث الصفحة Ajax] ===
    document.querySelectorAll('.btn-delete-trigger').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            if(!confirm('هل أنت متأكد من رغبتك في حذف هذا التعليق نهائياً؟')) return;
            
            const currentBtn = this;
            const form = currentBtn.closest('form');
            const commentId = form.querySelector('input[name="comment_id"]').value;
            const commentCard = document.getElementById('comment-block-' + commentId);
            
            const formData = new FormData(form);
            formData.append('delete_comment', '1');
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData,
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    commentCard.style.transform = 'scale(0.9)';
                    commentCard.style.opacity = '0';
                    setTimeout(() => commentCard.remove(), 300);
                }
            });
        });
    });

    // === [3. كود تشغيل زر الإعجاب بالتعليقات المطور - لايك واحد فقط] ===
    const likeButtons = document.querySelectorAll('.like-btn');
    likeButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const commentId = this.getAttribute('data-id');
            const countSpan = this.querySelector('span');
            const icon = this.querySelector('i');
            const currentBtn = this;
            
            currentBtn.style.pointerEvents = 'none';
            const formData = new FormData();
            formData.append('comment_id', commentId);
            formData.append('csrf_token', document.getElementById('csrf_token').value);
            
            fetch('ajax/comment_like.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                currentBtn.style.pointerEvents = 'auto';
                if (data.success) {
                    countSpan.textContent = data.likes_count;
                    if (data.action === 'liked') {
                        icon.style.color = '#00b4d8';
                        icon.style.transform = 'scale(1.2)';
                        setTimeout(() => icon.style.transform = 'scale(1)', 200);
                    } else {
                        icon.style.color = '';
                    }
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                currentBtn.style.pointerEvents = 'auto';
                console.error('Error:', error);
            });
        });
    });

    // === [4. كود المفضلة التقليدي المدمج] ===
    const favoriteBtn = document.getElementById('favoriteBtn');
    if (favoriteBtn) {
        favoriteBtn.onclick = function(e) {
            e.preventDefault();
            const btn = this;
            const contentId = btn.getAttribute('data-content-id');
            const csrfToken = document.getElementById('csrf_token').value;
            const textSpan = btn.querySelector('span');
            const icon = btn.querySelector('i');
            
            btn.style.pointerEvents = 'none';
            const formData = new FormData();
            formData.append('content_id', contentId);
            formData.append('csrf_token', csrfToken);
            
            fetch('ajax/favorite.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                btn.style.pointerEvents = 'auto';
                if (data.success) {
                    if (data.action === 'added') {
                        btn.classList.add('active');
                        icon.className = 'fas fa-heart';
                        textSpan.textContent = 'في المفضلة';
                    } else if (data.action === 'removed') {
                        btn.classList.remove('active');
                        icon.className = 'far fa-heart';
                        textSpan.textContent = 'أضف للمفضلة';
                    }
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                btn.style.pointerEvents = 'auto';
                console.error('Error:', error);
            });
        };
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>