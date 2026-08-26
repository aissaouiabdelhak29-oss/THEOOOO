<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$pageTitle = 'إضافة محتوى';

$categories = getCategories();
$genres = getGenres();

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

        $slug = generateSlug($title);

        // Check slug uniqueness
        $stmt = $pdo->prepare("SELECT id FROM content WHERE slug = ? LIMIT 1");
        $stmt->execute([$slug]);
        if ($stmt->fetch()) {
            $slug .= '-' . time();
        }

        // Handle poster upload
        $poster = '';
        if (isset($_FILES['poster']) && $_FILES['poster']['error'] !== UPLOAD_ERR_NO_FILE) {
            try {
                $poster = yuki_upload_image($_FILES['poster'], __DIR__ . '/../uploads/posters/');
            } catch (RuntimeException $e) {
                $errors[] = 'فشل رفع الصورة: ' . $e->getMessage();
            }
        }

        // Handle backdrop upload
        $backdrop = '';
        if (isset($_FILES['backdrop']) && $_FILES['backdrop']['error'] !== UPLOAD_ERR_NO_FILE) {
            try {
                $backdrop = yuki_upload_image($_FILES['backdrop'], __DIR__ . '/../uploads/backdrops/');
            } catch (RuntimeException $e) {
                $errors[] = 'فشل رفع الخلفية: ' . $e->getMessage();
            }
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("INSERT INTO content (title, slug, description, type, category_id, genre_id, poster, backdrop, trailer, rating, year, duration, quality, status, featured) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $slug, $description, $type, $categoryId ?: null, $genreId ?: null, $poster, $backdrop, $trailer, $rating, $year ?: null, $duration ?: null, $quality, $status, $featured]);

            $contentId = $pdo->lastInsertId();

            // Insert video servers
            if (isset($_POST['servers']) && is_array($_POST['servers'])) {
                $serverStmt = $pdo->prepare("INSERT INTO video_servers (content_id, server_name, embed_url, quality, is_default) VALUES (?, ?, ?, ?, ?)");
                foreach ($_POST['servers'] as $index => $server) {
                    if (!empty($server['url'])) {
                        $serverStmt->execute([
                            $contentId,
                            $server['name'],
                            $server['url'],
                            $server['quality'] ?? 'HD',
                            $index === 0 ? 1 : 0
                        ]);
                    }
                }
            }

            redirect('content.php', 'تم إضافة المحتوى بنجاح!', 'success');
        }
    }
}

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="admin-header">
    <h1><i class="fas fa-plus-circle"></i> إضافة محتوى جديد</h1>
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
            <input type="text" name="title" class="form-input" required value="<?php echo isset($_POST['title']) ? sanitize($_POST['title']) : ''; ?>">
        </div>

        <div class="form-group full">
            <label>الوصف</label>
            <textarea name="description" class="form-input" rows="4"><?php echo isset($_POST['description']) ? sanitize($_POST['description']) : ''; ?></textarea>
        </div>

        <div class="form-group">
            <label>النوع <span class="required">*</span></label>
            <select name="type" class="form-input" required>
                <option value="movie" <?php echo (isset($_POST['type']) && $_POST['type'] === 'movie') ? 'selected' : ''; ?>>فيلم</option>
                <option value="series" <?php echo (isset($_POST['type']) && $_POST['type'] === 'series') ? 'selected' : ''; ?>>مسلسل</option>
            </select>
        </div>

        <div class="form-group">
            <label>التصنيف</label>
            <select name="category_id" class="form-input">
                <option value="">-- اختر --</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?php echo $cat['id']; ?>" <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
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
                <option value="<?php echo $g['id']; ?>" <?php echo (isset($_POST['genre_id']) && $_POST['genre_id'] == $g['id']) ? 'selected' : ''; ?>>
                    <?php echo sanitize($g['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>سنة الإصدار</label>
            <input type="number" name="year" class="form-input" min="1900" max="2030" value="<?php echo isset($_POST['year']) ? intval($_POST['year']) : date('Y'); ?>">
        </div>

        <div class="form-group">
            <label>المدة (بالدقائق)</label>
            <input type="number" name="duration" class="form-input" min="1" value="<?php echo isset($_POST['duration']) ? intval($_POST['duration']) : ''; ?>">
        </div>

        <div class="form-group">
            <label>الجودة</label>
            <select name="quality" class="form-input">
                <option value="HD" <?php echo (!isset($_POST['quality']) || $_POST['quality'] === 'HD') ? 'selected' : ''; ?>>HD</option>
                <option value="FHD" <?php echo (isset($_POST['quality']) && $_POST['quality'] === 'FHD') ? 'selected' : ''; ?>>FHD</option>
                <option value="4K" <?php echo (isset($_POST['quality']) && $_POST['quality'] === '4K') ? 'selected' : ''; ?>>4K</option>
            </select>
        </div>

        <div class="form-group">
            <label>التقييم (0-10)</label>
            <input type="number" name="rating" class="form-input" min="0" max="10" step="0.1" value="<?php echo isset($_POST['rating']) ? $_POST['rating'] : '0'; ?>">
        </div>

        <div class="form-group">
            <label>الحالة</label>
            <select name="status" class="form-input">
                <option value="draft" <?php echo (!isset($_POST['status']) || $_POST['status'] === 'draft') ? 'selected' : ''; ?>>مسودة</option>
                <option value="published" <?php echo (isset($_POST['status']) && $_POST['status'] === 'published') ? 'selected' : ''; ?>>منشور</option>
                <option value="pending" <?php echo (isset($_POST['status']) && $_POST['status'] === 'pending') ? 'selected' : ''; ?>>معلق</option>
            </select>
        </div>

        <div class="form-group">
            <label>البوستر</label>
            <input type="file" name="poster" class="form-input" accept="image/*">
        </div>

        <div class="form-group">
            <label>الخلفية</label>
            <input type="file" name="backdrop" class="form-input" accept="image/*">
        </div>

        <div class="form-group full">
            <label>رابط الإعلان (Trailer)</label>
            <input type="url" name="trailer" class="form-input" placeholder="https://youtube.com/..." value="<?php echo isset($_POST['trailer']) ? sanitize($_POST['trailer']) : ''; ?>">
        </div>

        <div class="form-group full" style="display: flex; align-items: center; gap: 10px;">
            <input type="checkbox" name="featured" id="featured" value="1" <?php echo isset($_POST['featured']) ? 'checked' : ''; ?> style="width: auto;">
            <label for="featured" style="margin: 0;">محتوى مميز (يظهر في الصفحة الرئيسية)</label>
        </div>
    </div>

    <!-- Video Servers Section -->
    <div style="margin-top: 32px;">
        <h3 style="font-size: 18px; font-weight: 800; margin-bottom: 16px; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-server" style="color: var(--primary);"></i> سيرفرات المشاهدة
        </h3>
        <p style="color: var(--text-muted); margin-bottom: 16px; font-size: 14px;">
            أضف روابط التضمين (Embed URLs) من السيرفرات. المستخدم سيتمكن من اختيار السيرفر المفضل.
        </p>

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
        </div>

        <button type="button" class="add-server-btn" id="addServerBtn">
            <i class="fas fa-plus"></i> إضافة سيرفر آخر
        </button>
    </div>

    <div style="margin-top: 32px;">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fas fa-save"></i> حفظ المحتوى
        </button>
    </div>
</form>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
