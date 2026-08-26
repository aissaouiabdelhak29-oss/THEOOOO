<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$pageTitle = 'إدارة المستخدمين';

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;

$countStmt = $pdo->query("SELECT COUNT(*) FROM users");
$total = $countStmt->fetchColumn();

$pagination = paginate($total, $perPage, $page);

// تم تعديل الاستعلام ليجلب كافة الحقول بما فيها membership_type
$stmt = $pdo->prepare("SELECT * FROM users ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->execute([$perPage, $pagination['offset']]);
$users = $stmt->fetchAll();

require_once __DIR__ . '/includes/admin-header.php';
?>

<style>
    .badge-vip { background: linear-gradient(135deg, #ffd700, #ffa500); color: #000; font-weight: bold; box-shadow: 0 0 6px rgba(255, 215, 0, 0.4); }
    .badge-regular { background: #222232; color: #a0a0b0; }
    .action-btn.vip-toggle { color: #ffd700; }
    .action-btn.vip-toggle:hover { background: rgba(255, 215, 0, 0.15); }
    .action-btn.vip-toggle.is-vip { color: #ef233c; }
    .action-btn.vip-toggle.is-vip:hover { background: rgba(239, 35, 60, 0.15); }
</style>

<div class="admin-header">
    <h1><i class="fas fa-users"></i> إدارة المستخدمين</h1>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h3><i class="fas fa-list"></i> قائمة المستخدمين (<?php echo number_format($total); ?>)</h3>
    </div>
    <table class="admin-table">
        <thead>
            <tr>
                <th>#</th>
                <th>المستخدم</th>
                <th>البريد</th>
                <th>الصلاحية</th>
                <th>العضوية</th> <th>الحالة</th>
                <th>تاريخ التسجيل</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
            <tr>
                <td><?php echo $user['id']; ?></td>
                <td style="display: flex; align-items: center; gap: 10px;">
                    <img src="<?php echo $user['avatar'] && $user['avatar'] !== 'default-avatar.png' ? '../uploads/avatars/' . sanitize($user['avatar']) : '../assets/images/default-avatar.png'; ?>" 
                         style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover;">
                    <span style="font-weight: 700;"><?php echo sanitize($user['username']); ?></span>
                </td>
                <td><?php echo sanitize($user['email']); ?></td>
                <td>
                    <span class="badge badge-<?php echo $user['role'] === 'admin' ? 'danger' : 'info'; ?>">
                        <?php echo $user['role'] === 'admin' ? 'مدير' : 'مستخدم'; ?>
                    </span>
                </td>
                <td>
                    <span id="badge-user-<?php echo $user['id']; ?>" class="badge <?php echo ($user['membership_type'] === 'vip') ? 'badge-vip' : 'badge-regular'; ?>">
                        <?php if ($user['membership_type'] === 'vip'): ?>
                            <i class="fas fa-star"></i> VIP
                        <?php else: ?>
                            عادية
                        <?php endif; ?>
                    </span>
                </td>
                <td>
                    <span class="badge badge-<?php echo $user['status'] === 'active' ? 'success' : 'danger'; ?>">
                        <?php echo $user['status'] === 'active' ? 'نشط' : 'محظور'; ?>
                    </span>
                </td>
                <td><?php echo date('Y/m/d H:i', strtotime($user['created_at'])); ?></td>
                <td>
                    <div class="actions">
                        <?php if ($user['role'] !== 'admin'): ?>
                        <a href="#" class="action-btn vip-toggle <?php echo ($user['membership_type'] === 'vip') ? 'is-vip' : ''; ?>" 
                           data-user-id="<?php echo $user['id']; ?>" 
                           title="<?php echo ($user['membership_type'] === 'vip') ? 'إلغاء الـ VIP' : 'ترقية إلى VIP'; ?>">
                            <i class="fas <?php echo ($user['membership_type'] === 'vip') ? 'fa-user-minus' : 'fa-user-plus'; ?>"></i>
                        </a>
                        <?php endif; ?>

                        <a href="user-edit.php?id=<?php echo $user['id']; ?>" class="action-btn edit" title="تعديل">
                            <i class="fas fa-edit"></i>
                        </a>
                        <?php if ($user['id'] != $currentUser['id']): ?>
                        <a href="user-toggle.php?id=<?php echo $user['id']; ?>&csrf_token=<?php echo urlencode(generateCsrfToken()); ?>" class="action-btn view" title="<?php echo $user['status'] === 'active' ? 'حظر' : 'تفعيل'; ?>">
                            <i class="fas fa-<?php echo $user['status'] === 'active' ? 'ban' : 'check'; ?>"></i>
                        </a>
                        <a href="user-delete.php?id=<?php echo $user['id']; ?>&csrf_token=<?php echo urlencode(generateCsrfToken()); ?>" class="action-btn delete" title="حذف" onclick="return confirmDelete('هل أنت متأكد من حذف هذا المستخدم؟')">
                            <i class="fas fa-trash"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if ($pagination['total_pages'] > 1): ?>
<div class="pagination">
    <?php if ($pagination['has_prev']): ?>
    <a href="?page=<?php echo $pagination['current_page'] - 1; ?>"><i class="fas fa-chevron-right"></i></a>
    <?php else: ?>
    <span class="disabled"><i class="fas fa-chevron-right"></i></span>
    <?php endif; ?>

    <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
        <?php if ($i == $pagination['current_page']): ?>
        <span class="current"><?php echo $i; ?></span>
        <?php else: ?>
        <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
        <?php endif; ?>
    <?php endfor; ?>

    <?php if ($pagination['has_next']): ?>
    <a href="?page=<?php echo $pagination['current_page'] + 1; ?>"><i class="fas fa-chevron-left"></i></a>
    <?php else: ?>
    <span class="disabled"><i class="fas fa-chevron-left"></i></span>
    <?php endif; ?>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = <?php echo json_encode(generateCsrfToken()); ?>;
    const vipToggleButtons = document.querySelectorAll('.vip-toggle');

    vipToggleButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            const currentBtn = this;
            const userId = currentBtn.getAttribute('data-user-id');
            const badge = document.getElementById('badge-user-' + userId);
            const icon = currentBtn.querySelector('i');

            // تعطيل الضغط مؤقتاً لحماية السيرفر
            currentBtn.style.pointerEvents = 'none';

            const formData = new FormData();
            formData.append('user_id', userId);
            formData.append('csrf_token', csrfToken);

            // Fetch API لإرسال الطلب لملف toggle_vip.php الموجود في مجلد ajax
            fetch('../ajax/toggle_vip.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                currentBtn.style.pointerEvents = 'auto'; // إعادة تفعيل الزر

                if (data.success) {
                    if (data.new_type === 'vip') {
                        // تحديث الواجهة لتصبح VIP
                        badge.className = 'badge badge-vip';
                        badge.innerHTML = '<i class="fas fa-star"></i> VIP';
                        
                        currentBtn.classList.add('is-vip');
                        currentBtn.setAttribute('title', 'إلغاء الـ VIP');
                        icon.className = 'fas fa-user-minus';
                    } else {
                        // تحديث الواجهة لتصبح عادية
                        badge.className = 'badge badge-regular';
                        badge.innerHTML = 'عادية';
                        
                        currentBtn.classList.remove('is-vip');
                        currentBtn.setAttribute('title', 'ترقية إلى VIP');
                        icon.className = 'fas fa-user-plus';
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
});
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>