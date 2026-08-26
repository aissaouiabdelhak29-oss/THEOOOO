<?php
// upgrade-vip.php
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// التأكد من تسجيل الدخول
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// جلب المعرف الخاص بالمستخدم الحالي من الجلسة
$user_id = $_SESSION['user_id'] ?? null;
$is_vip = false;

// جلب حالة العضوية الحالية مباشرة وبأمان من قاعدة البيانات
if ($user_id) {
    $stmt = $pdo->prepare("SELECT membership_type FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_membership = $stmt->fetchColumn();
    
    if ($user_membership === 'vip') {
        $is_vip = true;
    }
}

$pageTitle = 'ترقية الحساب إلى VIP';

// استدعاء الهيدر حسب المسار التقليدي للموقع
require_once 'includes/header.php';
?>

<div class="container" style="min-height: 80vh; padding: 40px 20px; font-family: 'Cairo', sans-serif; direction: rtl; max-width: 1200px; margin: 0 auto;">
    
    <?php if ($is_vip): ?>
        <!-- واجهة المستخدم الذي يمتلك بالفعل عضوية VIP -->
        <div class="vip-already-active">
            <div class="crown-icon">
                <i class="fas fa-crown"></i>
            </div>
            <h1>مرحباً بك في نادي النخبة! ✨</h1>
            <p class="status-text">حسابك حالياً نشط بميزة: <span class="gold-text">عضوية VIP المميزة</span></p>
            <div class="features-list">
                <div class="feature-item"><i class="fas fa-check-circle"></i> شعار ذهبي براق بجانب اسمك في كافة التعليقات.</div>
                <div class="feature-item"><i class="fas fa-check-circle"></i> أولوية قصوى ودعم فني خاص.</div>
                <div class="feature-item"><i class="fas fa-check-circle"></i> تصفح كامل للمنصة بدون أي قيود.</div>
            </div>
            <a href="index.php" class="back-home-btn">العودة للرئيسية ومشاهدة الأفلام</a>
        </div>

    <?php else: ?>
        <!-- واجهة اختيار طرق الدفع للترقية -->
        <div class="upgrade-container">
            <div class="upgrade-header">
                <h1>تفعيل العضوية الذهبية <span class="gold-text">VIP</span></h1>
                <p>اختر طريقة الدفع المناسبة لك لترقية حسابك فوراً والانضمام للمميزين</p>
            </div>

            <div class="payment-grid">
                <!-- 1. طريقة الدفع: بريدي موب BaridiMob (مخصصة للجزائر) -->
                <div class="payment-card dz-payment">
                    <div class="card-badge">موصى به في الجزائر 🇩🇿</div>
                    <div class="payment-icon"><i class="fas fa-money-bill-wave" style="color: #00b4d8;"></i></div>
                    <h3>بريدي موب (BaridiMob)</h3>
                    <p class="price">2000 د.ج / سنويًا</p>
                    <div class="payment-details">
                        <p><strong>RIP:</strong> مش لحين يا حلو</p>
                        <p class="note">بعد تحويل المبلغ، يرجى إرسال صورة الوصل لتفعيل الـ VIP فوراً.</p>
                    </div>
                    <a href="https://wa.me/213557740724?text=اريد%20تفعيل%20VIP%20بريدي%20موب" target="_blank" class="pay-btn ccp-btn">
                        <i class="fab fa-whatsapp"></i> تأكيد وإرسال الوصل
                    </a>
                </div>

                <!-- 2. طريقة الدفع: بايبال PayPal -->
                <div class="payment-card">
                    <div class="payment-icon"><i class="fab fa-paypal" style="color: #00457c;"></i></div>
                    <h3>PayPal</h3>
                    <p class="price">$9.99 / سنويًا</p>
                    <div class="payment-details">
                        <p>دفع آمن ومباشر عبر حسابك في PayPal أو البطاقات البنكية الدولية.</p>
                    </div>
                    <a href="process-paypal.php" class="pay-btn paypal-btn">
                        <i class="fas fa-credit-card"></i> ادفع الآن بواسطة PayPal
                    </a>
                </div>

                <!-- 3. طريقة الدفع: العملات الرقمية USDT -->
                <div class="payment-card">
                    <div class="payment-icon"><i class="fas fa-coins" style="color: #26a17b;"></i></div>
                    <h3>Crypto (USDT TRC20)</h3>
                    <p class="price">10 USDT / سنويًا</p>
                    <div class="payment-details">
                        <p class="crypto-wallet"><code>TA4roMWofLoLNcuczgSuCcHXCq5sfDg1hj</code></p>
                        <p class="note">شبكة Tron (TRC-20) فقط.</p>
                    </div>
                    <a href="https://wa.me/213557740724?text=اريد%20تفعيل%20VIP%20USDT" target="_blank" class="pay-btn crypto-btn">
                        <i class="fab fa-whatsapp"></i> تأكيد عبر الـ واتساب
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>

<!-- التنسيقات الفخمة والمظلمة لصفحة الترقية وطرق الدفع -->
<style>
    body { background-color: #0c0c12; }
    
    /* تنسيقات العضو الـ VIP الحالي */
    .vip-already-active { background: #13131a; border: 1px solid #ffd700; max-width: 650px; margin: 40px auto; padding: 40px 30px; border-radius: 20px; text-align: center; box-shadow: 0 0 30px rgba(255, 215, 0, 0.15); }
    .crown-icon i { font-size: 60px; color: #ffd700; animation: float 3s ease-in-out infinite; margin-bottom: 20px; }
    .gold-text { background: linear-gradient(135deg, #ffd700, #ffa500); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-weight: 800; }
    .status-text { color: #fff; font-size: 18px; margin-bottom: 30px; }
    .features-list { text-align: right; max-width: 450px; margin: 0 auto 35px auto; background: #0c0c12; padding: 20px; border-radius: 12px; border: 1px solid #1e1e2f; }
    .feature-item { color: #cacaca; font-size: 14px; margin-bottom: 12px; display: flex; align-items: center; gap: 10px; }
    .feature-item i { color: #ffd700; }
    .back-home-btn { display: inline-block; background: linear-gradient(135deg, #ffd700, #ffa500); color: #000; padding: 12px 30px; border-radius: 8px; font-weight: bold; text-decoration: none; transition: 0.3s; }
    .back-home-btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(255, 215, 0, 0.3); }

    /* تنسيقات كروت الدفع */
    .upgrade-container { max-width: 1200px; margin: 0 auto; }
    .upgrade-header { text-align: center; margin-bottom: 50px; color: #fff; }
    .upgrade-header p { color: #a0a0b0; font-size: 15px; }
    
    .payment-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 30px; }
    .payment-card { background: #13131a; border: 1px solid #1e1e2f; border-radius: 16px; padding: 30px 20px; text-align: center; display: flex; flex-direction: column; justify-content: space-between; position: relative; transition: 0.3s ease; }
    .payment-card:hover { transform: translateY(-5px); border-color: #3d3d5c; }
    
    .dz-payment { border: 1px solid #00b4d8; background: rgba(0, 180, 216, 0.02); }
    .card-badge { position: absolute; top: -12px; left: 50%; transform: translateX(-50%); background: #00b4d8; color: #fff; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; }
    
    .payment-icon i { font-size: 45px; margin-bottom: 15px; display: inline-block; }
    .payment-card h3 { color: #fff; font-size: 20px; margin: 10px 0; }
    .price { font-size: 22px; font-weight: 800; color: #fff; margin-bottom: 20px; }
    
    .payment-details { background: #0c0c12; padding: 15px; border-radius: 10px; margin-bottom: 25px; text-align: right; border: 1px solid #1c1c2b; }
    .payment-details p { margin: 5px 0; color: #a0a0b0; font-size: 13px; }
    .payment-details strong { color: #fff; }
    .crypto-wallet code { background: #222; padding: 3px 6px; border-radius: 4px; color: #ffb703; font-size: 12px; word-break: break-all; display: block; text-align: center; }
    .note { font-size: 11px !important; color: #ff2a5f !important; margin-top: 8px !important; }

    .pay-btn { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px; border-radius: 8px; font-weight: bold; text-decoration: none; font-size: 14px; transition: 0.3s; color: #fff; }
    .ccp-btn { background: #00b4d8; color: #fff; }
    .ccp-btn:hover { background: #0077b6; }
    .paypal-btn { background: #00457c; }
    .paypal-btn:hover { background: #002d50; }
    .crypto-btn { background: #26a17b; }
    .crypto-btn:hover { background: #1e7e60; }

    @keyframes float {
        0% { transform: translateY(0px); }
        50% { transform: translateY(-10px); }
        100% { transform: translateY(0px); }
    }
</style>

<?php require_once 'includes/footer.php'; ?>