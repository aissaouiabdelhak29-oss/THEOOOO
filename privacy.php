<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
$pageTitle = "سياسة الخصوصية - " . getSetting('site_name', 'Yuki');
require_once 'includes/header.php';
?>
<style>
    .pro-page { background: radial-gradient(circle at top, #1e1e2f 0%, #0c0c12 100%); min-height: 80vh; padding: 80px 20px; display: flex; align-items: center; justify-content: center; font-family: 'Cairo', sans-serif; }
    .pro-card { background: rgba(26, 26, 38, 0.6); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.05); padding: 50px 40px; border-radius: 20px; max-width: 850px; width: 100%; box-shadow: 0 20px 40px rgba(0,0,0,0.5); }
    .pro-header { text-align: center; margin-bottom: 40px; }
    .pro-icon-wrap { width: 80px; height: 80px; background: linear-gradient(135deg, #00f2fe, #4facfe); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 25px; box-shadow: 0 10px 20px rgba(79, 172, 254, 0.3); }
    .pro-icon-wrap i { color: #fff; font-size: 32px; }
    .pro-header h1 { color: #fff; font-size: 36px; font-weight: 800; }
    .law-list { display: flex; flex-direction: column; gap: 20px; text-align: right; }
    .law-item { background: rgba(255,255,255, 0.02); border-right: 4px solid #4facfe; padding: 20px; border-radius: 4px 12px 12px 4px; }
    .law-item h3 { color: #fff; font-size: 18px; font-weight: 600; margin-bottom: 10px; }
    .law-item p { color: #ababab; font-size: 15px; line-height: 1.8; margin: 0; }
</style>

<div class="pro-page">
    <div class="pro-card">
        <div class="pro-header">
            <div class="pro-icon-wrap"><i class="fas fa-shield-alt"></i></div>
            <h1>سياسة الخصوصية حماية البيانات</h1>
        </div>
        <div class="law-list">
            <div class="law-item">
                <h3>1. جمع واستخدام البيانات</h3>
                <p>نقوم بجمع الحد الأدنى من البيانات الشخصية مثل اسم المستخدم والبريد الإلكتروني فقط لإدارة الجلسات وحفظ سجل المفضلة الشخصية، ولا يتم تتبع أي نشاط خارج المنصة.</p>
            </div>
            <div class="law-item">
                <h3>2. سرية الحسابات تامة</h3>
                <p>تُخزن جميع كلمات المرور عبر خوارزميات تشفير أحادية الاتجاه قوية جداً. لا يستطيع أي موظف أو طرف ثالث قراءة أو كشف تفاصيل دخولك بأي شكل من الأشكال.</p>
            </div>
            <div class="law-item">
                <h3>3. ملفات تعريف الارتباط (Cookies)</h3>
                <p>تستخدم المنصة ملفات تعريف الارتباط المحلية فقط للإبقاء على حسابك متصلاً وتفضيلات التصفح نشطة، لضمان عدم اضطرارك لتسجيل الدخول في كل زيارة.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>