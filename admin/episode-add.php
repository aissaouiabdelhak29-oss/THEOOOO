<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$pageTitle = 'إضافة حلقة';

$seriesId = intval($_GET['series'] ?? 0);
$series = getContentById($seriesId);

if (!$series || $series['type'] !== 'series') {
    redirect('episodes.php', 'المسلسل غير موجود', 'error');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'خطأ في التحقق.';
    } else {
        $season = intval($_POST['season'] ?? 1);
        $episodeNumber = intval($_POST['episode_number'] ?? 1);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $duration = intval($_POST['duration'] ?? 0);

        if (empty($title)) {
            $errors[] = 'عنوان الحلقة مطلوب';
        }

        $poster = '';
        if (isset($_FILES['poster']) && $_FILES['poster']['error'] !== UPLOAD_ERR_NO_FILE) {
            try {
                $poster = yuki_upload_image($_FILES['poster'], __DIR__ . '/../uploads/episodes/');
            } catch (RuntimeException $e) {
                $errors[] = 'فشل رفع الصورة: ' . $e->getMessage();
            }
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("INSERT INTO episodes (series_id, season, episode_number, title, description, duration, poster) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$seriesId, $season, $episodeNumber, $title, $description, $duration ?: null, $poster]);

            $episodeId = $pdo->lastInsertId();

            // Insert episode servers
            if (isset($_POST['servers']) && is_array($_POST['servers'])) {
                $serverStmt = $pdo->prepare("INSERT INTO episode_servers (episode_id, server_name, embed_url, quality, is_default) VALUES (?, ?, ?, ?, ?)");
                foreach ($_POST['servers'] as $index => $server) {
                    if (!empty($server['url'])) {
                        $serverStmt->execute([
                            $episodeId,
                            $server['name'],
                            $server['url'],
                            $server['quality'] ?? 'HD',
                            $index === 0 ? 1 : 0
                        ]);
                    }
                }
            }

            redirect('episodes.php?series=' . $seriesId, 'تم إضافة الحلقة بنجاح!', 'success');
        }
    }
}

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="admin-header">
    <h1><i class="fas fa-plus-circle"></i> إضافة حلقة لـ: <?php echo sanitize($series['title']); ?></h1>
    <a href="episodes.php?series=<?php echo $seriesId; ?>" class="btn btn-outline">
        <i class="fas fa-arrow-right"></i> رجوع
    </a>
</div>

<?php if (!empty($errors)): ?>
<div style="margin-bottom: 24px;">
    <?php foreach ($errors as $err): ?>
    <div class="flash-message flash-error" style="position: static; transform: none; animation: none; margin-bottom: 8px;">
        <i class="fas fa-exclamation-circle"></i> <?php echo sanitize($err); ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<form method="POST" action="" enctype="multipart/form-data" class="admin-form">
    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

    <div class="admin-form-grid">
        <div class="form-group">
            <label>الموسم <span class="required">*</span></label>
            <input type="number" name="season" class="form-input" min="1" value="1" required>
        </div>

        <div class="form-group">
            <label>رقم الحلقة <span class="required">*</span></label>
            <input type="number" name="episode_number" class="form-input" min="1" value="1" required>
        </div>

        <div class="form-group full">
            <label>عنوان الحلقة <span class="required">*</span></label>
            <input type="text" name="title" class="form-input" required>
        </div>

        <div class="form-group full">
            <label>الوصف</label>
            <textarea name="description" class="form-input" rows="3"></textarea>
        </div>

        <div class="form-group">
            <label>المدة (بالدقائق)</label>
            <input type="number" name="duration" class="form-input" min="1">
        </div>

        <div class="form-group">
            <label>البوستر</label>
            <input type="file" name="poster" class="form-input" accept="image/*">
        </div>
    </div>

    <!-- Episode Servers -->
    <div style="margin-top: 32px;">
        <h3 style="font-size: 18px; font-weight: 800; margin-bottom: 16px; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-server" style="color: var(--primary);"></i> سيرفرات الحلقة
        </h3>

        <div id="serversContainer">
            <div class="server-input-group">
                <div>
                    <label>السيرفر</label>
                    <select name="servers[0][name]" class="form-input" required>
                        <option value="streamhg">StreamHG</option>
                        <option value="earnvids">EarnVids</option>
                        <option value="mixdrop">Mixdrop</option>
                        <option value="doodstream">DoodStream</option>
                    </select>
                </div>
                <div>
                    <label>رابط التضمين <span class="required">*</span></label>
                    <input type="url" name="servers[0][url]" class="form-input" placeholder="https://..." required>
                </div>
                <div>
                    <label>الجودة</label>
                    <select name="servers[0][quality]" class="form-input">
                        <option value="HD">HD</option>
                        <option value="FHD">FHD</option>
                        <option value="4K">4K</option>
                    </select>
                </div>
            </div>
        </div>

        <button type="button" class="add-server-btn" id="addServerBtn">
            <i class="fas fa-plus"></i> إضافة سيرفر آخر
        </button>
    </div>

    <div style="margin-top: 32px;">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fas fa-save"></i> حفظ الحلقة
        </button>
    </div>
</form>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
