<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
$pageTitle = "اتصل بنا - " . getSetting('site_name', 'Yuki');
require_once 'includes/header.php';
?>
<style>
    .pro-page { background: radial-gradient(circle at top, #1e1e2f 0%, #0c0c12 100%); min-height: 80vh; padding: 80px 20px; display: flex; align-items: center; justify-content: center; font-family: 'Cairo', sans-serif; }
    .pro-card { background: rgba(26, 26, 38, 0.6); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.05); padding: 50px 40px; border-radius: 20px; max-width: 650px; width: 100%; box-shadow: 0 20px 40px rgba(0,0,0,0.5); text-align: center; }
    .pro-icon-wrap { width: 80px; height: 80px; background: linear-gradient(135deg, #00b4d8, #0077b6); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 25px; box-shadow: 0 10px 20px rgba(0, 119, 182, 0.3); }
    .pro-icon-wrap i { color: #fff; font-size: 32px; }
    .pro-card h1 { color: #fff; font-size: 36px; font-weight: 800; margin-bottom: 15px; }
    .pro-card p { color: #ababab; font-size: 16px; margin-bottom: 35px; line-height: 1.6; }
    .contact-email-box { background: rgba(0, 180, 216, 0.05); border: 1px dashed rgba(0, 180, 216, 0.3); padding: 20px; border-radius: 12px; margin-bottom: 30px; display: flex; align-items: center; justify-content: center; gap: 15px; transition: 0.3s; }
    .contact-email-box:hover { background: rgba(0, 180, 216, 0.1); transform: scale(1.02); }
    .contact-email-box i { font-size: 28px; color: #00b4d8; }
    .contact-email-box a { color: #fff; font-size: 19px; font-weight: bold; text-decoration: none; letter-spacing: 0.5px; }
    .support-note { font-size: 14px; color: #727282; margin: 0; }
</style>

<div class="pro-page">
    <div class="pro-card">
        <div class="pro-icon-wrap"><i class="fas fa-envelope"></i></div>
        <h1>الدعم الفني والاتصال</h1>
        <p>هل واجهتك مشكلة في تشغيل السيرفرات؟ أو لديك اقتراح رائع لتطوير المنصة أو إضافة أعمال سينمائية جديدة؟ فريق الدعم متواجد للرد عليك دائماً.</p>
        
        <div class="contact-email-box">
            <i class="pointer-icon fas fa-paper-plane"></i>
            <a href="mailto:yukitv.support@gmail.com">yukitv.support@gmail.com</a>
        </div>

        <p class="support-note"><i class="fas fa-clock"></i> نقوم بالرد على كافة رسائلكم الواردة في غضون 12 إلى 24 ساعة كحد أقصى.</p>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>