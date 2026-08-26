<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$pageTitle = 'إدارة الحلقات';

$seriesId = intval($_GET['series'] ?? 0);

// Get all series for dropdown
$seriesList = $pdo->query("SELECT id, title FROM content WHERE type = 'series' ORDER BY title")->fetchAll();

$episodes = [];
if ($seriesId > 0) {
    $episodes = getEpisodes($seriesId);
}

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="admin-header">
    <h1><i class="fas fa-list"></i> إدارة الحلقات</h1>
    <div class="admin-header-actions">
        <?php if ($seriesId > 0): ?>
        <a href="episode-add.php?series=<?php echo $seriesId; ?>" class="btn btn-primary">
            <i class="fas fa-plus"></i> إضافة حلقة
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="admin-form" style="margin-bottom: 32px;">
    <form method="GET" action="">
        <div class="form-group">
            <label>اختر المسلسل</label>
            <select name="series" class="form-input" onchange="this.form.submit()">
                <option value="">-- اختر مسلسل --</option>
                <?php foreach ($seriesList as $s): ?>
                <option value="<?php echo $s['id']; ?>" <?php echo $seriesId == $s['id'] ? 'selected' : ''; ?>>
                    <?php echo sanitize($s['title']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
</div>

<?php if ($seriesId > 0): ?>
<?php if (empty($episodes)): ?>
<div class="empty-state">
    <i class="fas fa-list"></i>
    <h3>لا توجد حلقات لهذا المسلسل</h3>
    <a href="episode-add.php?series=<?php echo $seriesId; ?>" class="btn btn-primary" style="margin-top: 16px;">
        <i class="fas fa-plus"></i> إضافة حلقة
    </a>
</div>
<?php else: ?>
<div class="admin-card">
    <div class="admin-card-header">
        <h3><i class="fas fa-list"></i> الحلقات (<?php echo count($episodes); ?>)</h3>
    </div>
    <table class="admin-table">
        <thead>
            <tr>
                <th>#</th>
                <th>الموسم</th>
                <th>الحلقة</th>
                <th>العنوان</th>
                <th>المدة</th>
                <th>السيرفرات</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($episodes as $ep): ?>
            <tr>
                <td><?php echo $ep['id']; ?></td>
                <td><?php echo $ep['season']; ?></td>
                <td><?php echo $ep['episode_number']; ?></td>
                <td style="font-weight: 700;"><?php echo sanitize($ep['title']); ?></td>
                <td><?php echo $ep['duration'] ? formatDuration($ep['duration']) : '-'; ?></td>
                <td><?php echo $ep['server_count']; ?></td>
                <td>
                    <div class="actions">
                        <a href="episode-edit.php?id=<?php echo $ep['id']; ?>" class="action-btn edit" title="تعديل">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="episode-delete.php?id=<?php echo $ep['id']; ?>&csrf_token=<?php echo urlencode(generateCsrfToken()); ?>" class="action-btn delete" title="حذف" onclick="return confirmDelete()">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
<?php else: ?>
<div class="empty-state">
    <i class="fas fa-tv"></i>
    <h3>اختر مسلسل لعرض حلقاته</h3>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
