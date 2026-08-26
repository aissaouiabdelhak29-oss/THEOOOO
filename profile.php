<?php
$pageTitle = 'الملف الشخصي';
require_once 'includes/header.php';
require_once 'includes/functions.php';

requireLogin();

// جلب بيانات المستخدم مع نقاط الـ XP والمستوى مباشرة من قاعدة البيانات لتحديث القيمة الحقيقية
$userId = $_SESSION['user_id'] ?? ($currentUser['id'] ?? ($_SESSION['user']['id'] ?? 0));
$stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$stmtUser->execute([$userId]);
$currentUser = $stmtUser->fetch(PDO::FETCH_ASSOC);

if (!$currentUser) {
    $currentUser = getCurrentUser(); // كاحتياط في حال لم يتم العثور عليه بالطريقة السابقة
}

$errors = [];
$success = '';

// تحديد مسار الصورة الثابتة الافتراضية
$defaultAvatarPath = 'uploads/avatars/default-avatar.png';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'خطأ في التحقق من الأمان.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($username) || strlen($username) < 3) {
            $errors[] = 'اسم المستخدم يجب أن يكون 3 أحرف على الأقل';
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'البريد الإلكتروني غير صالح';
        }

        $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ? LIMIT 1");
        $stmt->execute([$username, $email, $currentUser['id']]);
        if ($stmt->fetch()) {
            $errors[] = 'اسم المستخدم أو البريد الإلكتروني مستخدم بالفعل';
        }

        if (!empty($newPassword)) {
            if (strlen($newPassword) < 6) {
                $errors[] = 'كلمة المرور الجديدة يجب أن تكون 6 أحرف على الأقل';
            }
            if ($newPassword !== $confirmPassword) {
                $errors[] = 'كلمتا المرور غير متطابقتين';
            }

            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$currentUser['id']]);
            $userData = $stmt->fetch();
            if (!password_verify($currentPassword, $userData['password'])) {
                $errors[] = 'كلمة المرور الحالية غير صحيحة';
            }
        }

        if (empty($errors)) {
            $avatar = $currentUser['avatar'];
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
                try {
                    $avatarName = yuki_upload_image($_FILES['avatar'], __DIR__ . '/uploads/avatars/');
                    $avatar = $avatarName;
                } catch (RuntimeException $e) {
                    $errors[] = 'فشل رفع الصورة: ' . $e->getMessage();
                }
            }

            if (!empty($newPassword)) {
                $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ?, avatar = ? WHERE id = ?");
                $stmt->execute([$username, $email, $hashedPassword, $avatar, $currentUser['id']]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, avatar = ? WHERE id = ?");
                $stmt->execute([$username, $email, $avatar, $currentUser['id']]);
            }

            $success = 'تم تحديث الملف الشخصي بنجاح!';
            
            // إعادة جلب البيانات لتحديث العرض بعد التعديل
            $stmtUser->execute([$currentUser['id']]);
            $currentUser = $stmtUser->fetch(PDO::FETCH_ASSOC);
        }
    }
}

// جلب الإحصائيات الخاصة بالمستخدم
$favCount = count(getUserFavorites($currentUser['id']));
$stmt = $pdo->prepare("SELECT COUNT(*) FROM watch_history WHERE user_id = ?");
$stmt->execute([$currentUser['id']]);
$watchCount = $stmt->fetchColumn();

// جلب الأوسمة المفتوحة للمستخدم من جدول user_achievements و badges باستخدام rank_id
$stmtRanks = $pdo->prepare("
    SELECT b.*, ua.unlocked_at 
    FROM user_achievements ua
    JOIN badges b ON ua.rank_id = b.id
    WHERE ua.user_id = ?
    ORDER BY ua.unlocked_at DESC
");
$stmtRanks->execute([$currentUser['id']]);
$userRanks = $stmtRanks->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
.profile-container {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 15px;
    box-sizing: border-box;
}

/* الكارت الرئيسي الأعلى */
.profile-header-card {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    background: #181824;
    border-radius: 16px;
    padding: 25px;
    box-sizing: border-box;
    overflow: hidden;
}

.profile-avatar-section {
    flex: 0 0 auto;
}

.profile-avatar-wrapper {
    position: relative;
    width: 90px;
    height: 90px;
}

.profile-avatar-img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
}

.profile-info-section {
    flex: 1 1 200px;
    min-width: 180px;
}

.profile-name {
    margin: 0 0 5px 0;
    font-size: 1.6rem;
    color: #fff;
}

.profile-email {
    margin: 0 0 10px 0;
    color: #a0a0b0;
    font-size: 0.9rem;
}

.profile-role-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    background: rgba(255, 0, 85, 0.15);
    color: #ff0055;
    font-weight: bold;
}

/* شبكة الإحصائيات */
.profile-stats-section {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
    gap: 12px;
    flex: 2 1 400px;
    width: 100%;
}

.profile-stat-box {
    background: #212130;
    padding: 12px 8px;
    border-radius: 12px;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.stat-icon-box {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 6px;
    font-size: 1rem;
}

.stat-number {
    font-size: 1.1rem;
    font-weight: bold;
    color: #fff;
    word-break: break-all;
}

.stat-label {
    font-size: 0.75rem;
    color: #a0a0b0;
    margin-top: 2px;
}

/* تنسيق قسم الأوسمة */
.profile-achievements-card {
    background: #181824;
    border-radius: 16px;
    padding: 25px;
    box-sizing: border-box;
    margin-top: 25px;
    margin-bottom: 25px;
}

.ranks-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.rank-card {
    background: #212130;
    border: 1px solid rgba(255, 255, 255, 0.05);
    padding: 15px;
    border-radius: 12px;
    text-align: center;
    transition: transform 0.2s ease;
}

.rank-card:hover {
    transform: translateY(-3px);
}

.rank-icon-wrapper {
    width: 50px;
    height: 50px;
    line-height: 50px;
    background: rgba(231, 76, 60, 0.15);
    color: #e74c3c;
    border-radius: 50%;
    margin: 0 auto 10px auto;
    font-size: 22px;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* التجاوب مع الشاشات الصغيرة */
@media (max-width: 768px) {
    .profile-header-card {
        flex-direction: column;
        text-align: center;
    }
    .profile-info-section {
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    .profile-stats-section {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<div class="profile-container">
    <div class="profile-card profile-header-card">
        <div class="profile-avatar-section">
            <div class="profile-avatar-wrapper">
                <?php 
                if (!empty($currentUser['avatar']) && $currentUser['avatar'] !== 'default-avatar.png' && file_exists('uploads/avatars/' . $currentUser['avatar'])) {
                    $avatarSrc = 'uploads/avatars/' . sanitize($currentUser['avatar']);
                } else {
                    $avatarSrc = $defaultAvatarPath;
                }
                ?>
                <img src="<?php echo $avatarSrc; ?>?t=<?php echo time(); ?>" 
                     alt="<?php echo sanitize($currentUser['username']); ?>" class="profile-avatar-img">
                <div class="profile-avatar-badge">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>
        <div class="profile-info-section">
            <h1 class="profile-name"><?php echo sanitize($currentUser['username']); ?></h1>
            <p class="profile-email"><i class="fas fa-envelope"></i> <?php echo sanitize($currentUser['email']); ?></p>
            <div class="profile-role-badge <?php echo $currentUser['role']; ?>">
                <i class="fas fa-<?php echo $currentUser['role'] === 'admin' ? 'shield-alt' : 'user'; ?>"></i>
                <?php echo $currentUser['role'] === 'admin' ? 'مدير الموقع' : 'عضو'; ?>
            </div>
        </div>
        <div class="profile-stats-section">
            <!-- عرض نقاط الـ XP الحقيقية مباشرة من قاعدة البيانات -->
            <div class="profile-stat-box">
                <div class="stat-icon-box yellow" style="background: rgba(241, 196, 15, 0.15); color: #f1c40f;"><i class="fas fa-bolt"></i></div>
                <div class="stat-number"><?php echo number_format($currentUser['xp'] ?? 0); ?></div>
                <div class="stat-label">نقاط XP</div>
            </div>
            <div class="profile-stat-box">
                <div class="stat-icon-box red"><i class="fas fa-heart"></i></div>
                <div class="stat-number"><?php echo $favCount; ?></div>
                <div class="stat-label">المفضلة</div>
            </div>
            <div class="profile-stat-box">
                <div class="stat-icon-box blue"><i class="fas fa-eye"></i></div>
                <div class="stat-number"><?php echo $watchCount; ?></div>
                <div class="stat-label">مشاهدات</div>
            </div>
            <div class="profile-stat-box">
                <div class="stat-icon-box green"><i class="fas fa-calendar"></i></div>
                <div class="stat-number">
                    <?php 
                    if (!empty($currentUser['created_at']) && $currentUser['created_at'] !== '0000-00-00 00:00:00') {
                        echo date('Y/m/d', strtotime($currentUser['created_at'])); 
                    } else {
                        echo "غير متوفر"; 
                    }
                    ?>
                </div>
                <div class="stat-label">تاريخ التسجيل</div>
            </div>
        </div>
    </div>

    <!-- قسم الرتب والأوسمة المفتوحة -->
    <div class="profile-achievements-card">
        <div class="profile-form-header" style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
            <i class="fas fa-award" style="color: #e74c3c; font-size: 20px;"></i>
            <h2 style="margin: 0; color: #fff; font-size: 1.2rem;">الأوسمة والإنجازات</h2>
        </div>
        
        <?php if (!empty($userRanks)): ?>
            <div class="ranks-grid">
                <?php foreach ($userRanks as $rank): 
                    $iconClass = $rank['badge_icon'] ?? $rank['icon'] ?? 'fa-award';
                ?>
                    <div class="rank-card">
                        <div class="rank-icon-wrapper">
                            <i class="fas <?php echo sanitize($iconClass); ?>"></i>
                        </div>
                        <h4 style="margin: 5px 0; color: #fff; font-size: 16px; font-weight: 600;"><?php echo sanitize($rank['title'] ?? ''); ?></h4>
                        <p style="font-size: 12px; color: #a0a0a0; margin: 0 0 10px 0; line-height: 1.4;"><?php echo sanitize($rank['description'] ?? ''); ?></p>
                        <span style="display: inline-block; font-size: 11px; color: #e74c3c; background: rgba(231, 76, 60, 0.1); padding: 3px 8px; border-radius: 6px;">
                            تم الفتح: <?php echo date('Y/m/d', strtotime($rank['unlocked_at'])); ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 25px 10px; color: #888;">
                <i class="fas fa-lock" style="font-size: 32px; margin-bottom: 10px; display: block; opacity: 0.5;"></i>
                <p style="margin: 0; font-size: 0.9rem;">لم تحصل على أي أوسمة بعد. تفاعل في المنصة لجمع نقاط الـ XP وفتح الأوسمة!</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- كارت تعديل بيانات الملف الشخصي -->
    <div class="profile-card profile-form-card" style="background: #181824; border-radius: 16px; padding: 25px;">
        <div class="profile-form-header" style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
            <i class="fas fa-user-edit" style="color: #3498db; font-size: 20px;"></i>
            <h2 style="margin: 0; color: #fff; font-size: 1.2rem;">تعديل الملف الشخصي</h2>
        </div>

        <?php if ($success): ?>
        <div class="profile-alert profile-alert-success">
            <i class="fas fa-check-circle"></i> <?php echo sanitize($success); ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $err): ?>
            <div class="profile-alert profile-alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo sanitize($err); ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data" class="profile-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

            <div class="profile-form-grid">
                <div class="form-group">
                    <label class="form-label">اسم المستخدم</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" name="username" class="form-input" value="<?php echo sanitize($currentUser['username']); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">البريد الإلكتروني</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" name="email" class="form-input" value="<?php echo sanitize($currentUser['email']); ?>" required>
                    </div>
                </div>

                <div class="form-group full-width">
                    <label class="form-label">الصورة الشخصية</label>
                    <div class="file-input-wrapper">
                        <input type="file" name="avatar" id="avatar" class="file-input" accept="image/*">
                        <label for="avatar" class="file-label">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <span>اختر صورة جديدة</span>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">كلمة المرور الحالية</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="current_password" class="form-input" placeholder="لتغيير كلمة المرور فقط">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">كلمة المرور الجديدة</label>
                    <div class="input-wrapper">
                        <i class="fas fa-key input-icon"></i>
                        <input type="password" name="new_password" class="form-input" placeholder="6 أحرف على الأقل" minlength="6">
                    </div>
                </div>

                <div class="form-group full-width">
                    <label class="form-label">تأكيد كلمة المرور الجديدة</label>
                    <div class="input-wrapper">
                        <i class="fas fa-check-double input-icon"></i>
                        <input type="password" name="confirm_password" class="form-input" placeholder="أعد كتابة كلمة المرور">
                    </div>
                </div>
            </div>

            <div class="form-actions" style="margin-top: 20px;">
                <button type="submit" class="btn-save">
                    <i class="fas fa-save"></i> حفظ التغييرات
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>