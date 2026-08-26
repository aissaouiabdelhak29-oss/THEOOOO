<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
$pageTitle = "شروط الاستخدام - " . getSetting('site_name', 'Yuki');
require_once 'includes/header.php';
?>
<style>
    .pro-page { background: radial-gradient(circle at top, #1e1e2f 0%, #0c0c12 100%); min-height: 80vh; padding: 80px 20px; display: flex; align-items: center; justify-content: center; font-family: 'Cairo', sans-serif; }
    .pro-card { background: rgba(26, 26, 38, 0.6); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.05); padding: 50px 40px; border-radius: 20px; max-width: 850px; width: 100%; box-shadow: 0 20px 40px rgba(0,0,0,0.5); }
    .pro-header { text-align: center; margin-bottom: 40px; }
    .pro-icon-wrap { width: 80px; height: 80px; background: linear-gradient(135deg, #b5179e, #7209b7); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 25px; box-shadow: 0 10px 20px rgba(114, 9, 183, 0.3); }
    .pro-icon-wrap i { color: #fff; font-size: 32px; }
    .pro-header h1 { color: #fff; font-size: 36px; font-weight: 800; }
    .law-list { display: flex; flex-direction: column; gap: 20px; text-align: right; }
    .law-item { background: rgba(255,255,255, 0.02); border-right: 4px solid #b5179e; padding: 20px; border-radius: 4px 12px 12px 4px; }
    .law-item h3 { color: #fff; font-size: 18px; font-weight: 600; margin-bottom: 10px; }
    .law-item p { color: #ababab; font-size: 15px; line-height: 1.8; margin: 0; }
</style>

<div class="pro-page">
    <div class="pro-card">
        <div class="pro-header">
            <div class="pro-icon-wrap"><i class="fas fa-file-contract"></i></div>
            <h1>اتفاقية شروط الاستخدام</h1>
        </div>
        <div class="law-list">
            <div class="law-item">
                <h3>1. الاستخدام العادل والشخصي</h3>
                <p>الحسابات المسجلة في منصة Yuki هي حسابات فردية مخصصة للاستخدام الشخصي فقط. يمنع بيع الحسابات أو استغلال ثغرات السيرفرات تجارياً.</p>
            </div>
            <div class="law-item">
                <h3>2. سلامة البنية التحتية</h3>
                <p>يمنع استخدام أي برامج آلية (Scrapers / Bots) لسحب الأفلام أو الضغط المكثف على روابط التشغيل لضمان استقرار المشاهدة لجميع الأعضاء الباقين دون مشاكل.</p>
            </div>
            <div class="law-item">
                <h3>3. تعديل وحظر الحسابات</h3>
                <p>تمتلك إدارة Yuki كامل الحق في تعليق أو إلغاء أي حساب يثبت تورطه في محاولات اختراق هجوم الحرمان من الخدمة (DDoS) أو التلاعب بالتوكن الأمني لحماية المنصة.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>