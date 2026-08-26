<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
$pageTitle = "من نحن - " . getSetting('site_name', 'Yuki');
require_once 'includes/header.php';
?>
<style>
    .pro-page { background: radial-gradient(circle at top, #1e1e2f 0%, #0c0c12 100%); min-height: 80vh; padding: 80px 20px; display: flex; align-items: center; justify-content: center; font-family: 'Cairo', sans-serif; }
    .pro-card { background: rgba(26, 26, 38, 0.6); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.05); padding: 50px 40px; border-radius: 20px; max-width: 850px; width: 100%; box-shadow: 0 20px 40px rgba(0,0,0,0.5); text-align: center; }
    .pro-icon-wrap { width: 80px; height: 80px; background: linear-gradient(135deg, #ff2a5f, #ff7e40); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 30px; box-shadow: 0 10px 20px rgba(255, 42, 95, 0.3); }
    .pro-icon-wrap i { color: #fff; font-size: 32px; }
    .pro-card h1 { color: #fff; font-size: 36px; font-weight: 800; margin-bottom: 25px; }
    .pro-content { color: #cacaca; line-height: 2; font-size: 17px; text-align: right; }
    .pro-content p { margin-bottom: 20px; }
    .feature-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 40px; }
    .feature-item { background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.02); padding: 20px; border-radius: 12px; transition: 0.3s; }
    .feature-item:hover { background: rgba(255, 255, 255, 0.07); transform: translateY(-5px); }
    .feature-item i { font-size: 24px; color: #ff2a5f; margin-bottom: 12px; }
    .feature-item h4 { color: #fff; font-size: 16px; margin-bottom: 8px; font-weight: 600; }
    .feature-item p { color: #8a8a9a; font-size: 13px; margin: 0; }
</style>

<div class="pro-page">
    <div class="pro-card">
        <div class="pro-icon-wrap"><i class="fas fa-info-circle"></i></div>
        <h1>من نحن</h1>
        <div class="pro-content">
            <p>مرحباً بك في <strong>Yuki</strong>، المنصة الرائدة والشغوفة بتقديم تجربة ترفيهية استثنائية متكاملة لعشاق السينما، المسلسلات، والأنمي في العالم العربي.</p>
            <p>انطلق مشروع Yuki برؤية واضحة: كسر القيود والتعقيدات التقليدية في مواقع البث، من خلال تقديم واجهة مستخدم فائقة السرعة، ذكية، ومتجاوبة بالكامل لتلائم كافة شاشاتك وأجهزتك الذكية.</p>
        </div>
        <div class="feature-grid">
            <div class="feature-item">
                <i class="fas fa-bolt"></i>
                <h4>سرعة فائقة</h4>
                <p>سيرفرات سحابية متطورة تضمن لك مشاهدة بدون تقطيع.</p>
            </div>
            <div class="feature-item">
                <i class="fas fa-expand-arrows-alt"></i>
                <h4>جودة كاملة</h4>
                <p>دعم دقات متعددة تتناسب ديناميكياً مع سرعة إنترنتك.</p>
            </div>
            <div class="feature-item">
                <i class="fas fa-fingerprint"></i>
                <h4>خصوصية وأمان</h4>
                <p>تشفير صارم وحماية تامة لكافة بيانات حسابك الشخصي.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>