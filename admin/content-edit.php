<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$pageTitle = 'تعديل المحتوى';

$id = intval($_GET['id'] ?? 0);
$content = getContentById($id);

if (!$content) {
    redirect('content.php', 'المحتوى غير موجود', 'error');
}

$categories = getCategories();
$genres = getGenres();
$servers = getVideoServers($id);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'خطأ في التحقق من الأمان.';
    } else {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $type = $_POST['type'] ?? 'movie';
        $categoryId = intval($_POST['category_id'] ?? 0);
        $genreId = intval($_POST['genre_id'] ?? 0);
        $year = intval($_POST['year'] ?? 0);
        $duration = intval($_POST['duration'] ?? 0);
        $quality = $_POST['quality'] ?? 'HD';
        $rating = floatval($_POST['rating'] ?? 0);
        $status = $_POST['status'] ?? 'draft';
        $featured = isset($_POST['featured']) ? 1 : 0;
        $trailer = trim($_POST['trailer'] ?? '');

        if (empty($title)) {
            $errors[] = 'عنوان المحتوى مطلوب';
        }

        $poster = $content['poster'];
        if (isset($_FILES['poster']) && $_FILES['poster']['error'] !== UPLOAD_ERR_NO_FILE) {
            try {
                $poster = yuki_upload_image($_FILES['poster'], __DIR__ . '/../uploads/posters/');
            } catch (RuntimeException $e) {
                $errors[] = 'فشل رفع الصورة: ' . $e->getMessage();
            }
        }

        $backdrop = $content['backdrop'];
        if (isset($_FILES['backdrop']) && $_FILES['backdrop']['error'] !== UPLOAD_ERR_NO_FILE) {
            try {
                $backdrop = yuki_upload_image($_FILES['backdrop'], __DIR__ . '/../uploads/backdrops/');
            } catch (RuntimeException $e) {
                $errors[] = 'فشل رفع الخلفية: ' . $e->getMessage();
            }
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("UPDATE content SET title = ?, description = ?, type = ?, category_id = ?, genre_id = ?, poster = ?, backdrop = ?, trailer = ?, rating = ?, year = ?, duration = ?, quality = ?, status = ?, featured = ? WHERE id = ?");
            $stmt->execute([$title, $description, $type, $categoryId ?: null, $genreId ?: null, $poster, $backdrop, $trailer, $rating, $year ?: null, $duration ?: null, $quality, $status, $featured, $id]);

            // Update servers - delete old and insert new
            $pdo->prepare("DELETE FROM video_servers WHERE content_id = ?")->execute([$id]);

            if (isset($_POST['servers']) && is_array($_POST['servers'])) {
                $serverStmt = $pdo->prepare("INSERT INTO video_servers (content_id, server_name, embed_url, quality, is_default) VALUES (?, ?, ?, ?, ?)");
                foreach ($_POST['servers'] as $index => $server) {
                    if (!empty($server['url'])) {
                        $serverStmt->execute([
                            $id,
                            $server['name'],
                            $server['url'],
                            $server['quality'] ?? 'HD',
                            $index === 0 ? 1 : 0
                        ]);
                    }
                }
            }

            redirect('content.php', 'تم تحديث المحتوى بنجاح!', 'success');
        }
    }
}

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="admin-header">
    <h1><i class="fas fa-edit"></i> تعديل: <?php echo sanitize($content['title']); ?></h1>
    <a href="content.php" class="btn btn-outline">
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
        <div class="form-group full">
            <label>عنوان المحتوى <span class="required">*</span></label>
            <input type="text" name="title" class="form-input" required value="<?php echo sanitize($content['title']); ?>">
        </div>

        <div class="form-group full">
            <label>الوصف</label>
            <textarea name="description" class="form-input" rows="4"><?php echo sanitize($content['description'] ?? ''); ?></textarea>
        </div>

        <div class="form-group">
            <label>النوع <span class="required">*</span></label>
            <select name="type" class="form-input" required>
                <option value="movie" <?php echo $content['type'] === 'movie' ? 'selected' : ''; ?>>فيلم</option>
                <option value="series" <?php echo $content['type'] === 'series' ? 'selected' : ''; ?>>مسلسل</option>
            </select>
        </div>

        <div class="form-group">
            <label>التصنيف</label>
            <select name="category_id" class="form-input">
                <option value="">-- اختر --</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?php echo $cat['id']; ?>" <?php echo $content['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                    <?php echo sanitize($cat['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>النوع الفني</label>
            <select name="genre_id" class="form-input">
                <option value="">-- اختر --</option>
                <?php foreach ($genres as $g): ?>
                <option value="<?php echo $g['id']; ?>" <?php echo $content['genre_id'] == $g['id'] ? 'selected' : ''; ?>>
                    <?php echo sanitize($g['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>سنة الإصدار</label>
            <input type="number" name="year" class="form-input" min="1900" max="2030" value="<?php echo $content['year'] ?? date('Y'); ?>">
        </div>

        <div class="form-group">
            <label>المدة (بالدقائق)</label>
            <input type="number" name="duration" class="form-input" min="1" value="<?php echo $content['duration'] ?? ''; ?>">
        </div>

        <div class="form-group">
            <label>الجودة</label>
            <select name="quality" class="form-input">
                <option value="HD" <?php echo $content['quality'] === 'HD' ? 'selected' : ''; ?>>HD</option>
                <option value="FHD" <?php echo $content['quality'] === 'FHD' ? 'selected' : ''; ?>>FHD</option>
                <option value="4K" <?php echo $content['quality'] === '4K' ? 'selected' : ''; ?>>4K</option>
            </select>
        </div>

        <div class="form-group">
            <label>التقييم (0-10)</label>
            <input type="number" name="rating" class="form-input" min="0" max="10" step="0.1" value="<?php echo $content['rating']; ?>">
        </div>

        <div class="form-group">
            <label>الحالة</label>
            <select name="status" class="form-input">
                <option value="draft" <?php echo $content['status'] === 'draft' ? 'selected' : ''; ?>>مسودة</option>
                <option value="published" <?php echo $content['status'] === 'published' ? 'selected' : ''; ?>>منشور</option>
                <option value="pending" <?php echo $content['status'] === 'pending' ? 'selected' : ''; ?>>معلق</option>
            </select>
        </div>

        <div class="form-group">
            <label>البوستر الحالي</label>
            <?php if ($content['poster']): ?>
            <img src="../uploads/posters/<?php echo sanitize($content['poster']); ?>" style="width: 100px; border-radius: 6px; margin-bottom: 8px; display: block;">
            <?php endif; ?>
            <input type="file" name="poster" class="form-input" accept="image/*">
        </div>

        <div class="form-group">
            <label>الخلفية الحالية</label>
            <?php if ($content['backdrop']): ?>
            <img src="../uploads/backdrops/<?php echo sanitize($content['backdrop']); ?>" style="width: 100px; border-radius: 6px; margin-bottom: 8px; display: block;">
            <?php endif; ?>
            <input type="file" name="backdrop" class="form-input" accept="image/*">
        </div>

        <div class="form-group full">
            <label>رابط الإعلان (Trailer)</label>
            <input type="url" name="trailer" class="form-input" placeholder="https://youtube.com/..." value="<?php echo sanitize($content['trailer'] ?? ''); ?>">
        </div>

        <div class="form-group full" style="display: flex; align-items: center; gap: 10px;">
            <input type="checkbox" name="featured" id="featured" value="1" <?php echo $content['featured'] ? 'checked' : ''; ?> style="width: auto;">
            <label for="featured" style="margin: 0;">محتوى مميز (يظهر في الصفحة الرئيسية)</label>
        </div>
    </div>

    <!-- Video Servers Section -->
    <div style="margin-top: 32px;">
        <h3 style="font-size: 18px; font-weight: 800; margin-bottom: 16px; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-server" style="color: var(--primary);"></i> سيرفرات المشاهدة
        </h3>
        <p style="color: var(--text-muted); margin-bottom: 16px; font-size: 14px;">
            أضف أو عدل روابط التضمين (Embed URLs) من السيرفرات.
        </p>

        <div id="serversContainer">
            <?php if (!empty($servers)): ?>
            <?php foreach ($servers as $index => $server): ?>
            <div class="server-input-group">
                <div>
                    <label>السيرفر</label>
                    <select name="servers[<?php echo $index; ?>][name]" class="form-input" required>
                        <option value="streamhg" <?php echo $server['server_name'] === 'streamhg' ? 'selected' : ''; ?>>StreamHG</option>
                        <option value="earnvids" <?php echo $server['server_name'] === 'earnvids' ? 'selected' : ''; ?>>EarnVids</option>
                        <option value="mixdrop" <?php echo $server['server_name'] === 'mixdrop' ? 'selected' : ''; ?>>Mixdrop</option>
                        <option value="doodstream" <?php echo $server['server_name'] === 'doodstream' ? 'selected' : ''; ?>>DoodStream</option>
                    </select>
                </div>
                <div>
                    <label>رابط التضمين (Embed URL) <span class="required">*</span></label>
                    <input type="url" name="servers[<?php echo $index; ?>][url]" class="form-input" value="<?php echo sanitize($server['embed_url']); ?>" required>
                </div>
                <div>
                    <label>الجودة</label>
                    <select name="servers[<?php echo $index; ?>][quality]" class="form-input">
                        <option value="HD" <?php echo $server['quality'] === 'HD' ? 'selected' : ''; ?>>HD</option>
                        <option value="FHD" <?php echo $server['quality'] === 'FHD' ? 'selected' : ''; ?>>FHD</option>
                        <option value="4K" <?php echo $server['quality'] === '4K' ? 'selected' : ''; ?>>4K</option>
                    </select>
                </div>
                <button type="button" class="remove-server" onclick="this.closest('.server-input-group').remove()">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
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
                    <label>رابط التضمين (Embed URL) <span class="required">*</span></label>
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
            <?php endif; ?>
        </div>

        <button type="button" class="add-server-btn" id="addServerBtn">
            <i class="fas fa-plus"></i> إضافة سيرفر آخر
        </button>
    </div>

    <div style="margin-top: 32px;">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fas fa-save"></i> حفظ التغييرات
        </button>
    </div>
</form>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
