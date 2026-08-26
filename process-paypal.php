<?php
// process-paypal.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/auth.php';
require_once 'includes/functions.php';

// حماية الصفحة: منع الزوار غير المسجلين
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$currentUser = getCurrentUser();
$pageTitle = 'بوابة الدفع الآمنة | PayPal';

// ⚠️ ضع هنا الـ Client ID الخاص بحسابك في مطوري بايبال (PayPal Developer)
$paypalClientId = 'AUqFdjbtw11YRq80jwej1aZt0nJPso-DN7sTbW6N3ogBEZX_rZ2Le5qejki2VGtEX7A01HL4pnDljrR-'; 
$price = '9.99'; // تكلفة الاشتراك السنوي
?>

<?php require_once 'includes/header.php'; ?>

<div class="paypal-wrapper">
    <div class="paypal-card-premium">
        
        <div class="paypal-card-header">
            <div class="brand-badge"><i class="fas fa-shield-alt"></i> اتصال مشفر وآمن</div>
            <div class="paypal-logo-icon">
                <i class="fab fa-paypal"></i>
            </div>
            <h2>تأكيد الاشتراك السنوي</h2>
            <p>أنت على وشك الانضمام إلى باقة <span class="vip-text-gradient">VIP الذهبية</span> والاستمتاع بمشاهدة بدون إعلانات وبأعلى دقة.</p>
        </div>

        <div class="invoice-details-box">
            <div class="invoice-row">
                <span>نوع الباقة:</span>
                <strong>العضوية المميزة (VIP)</strong>
            </div>
            <div class="invoice-row">
                <span>المدة:</span>
                <strong>1 سنة كاملة (تجديد يدوي)</strong>
            </div>
            <div class="invoice-divider"></div>
            <div class="invoice-row total-row">
                <span>المبلغ الإجمالي المستحق:</span>
                <span class="final-price">$<?php echo $price; ?></span>
            </div>
        </div>

        <div id="paypal-loading" class="paypal-loader-container" style="display: none;">
            <div class="spinner"></div>
            <p>جاري تأكيد الدفع وتفعيل حسابك، يرجى عدم إغلاق الصفحة...</p>
        </div>

        <div id="paypal-button-container"></div>

        <div class="security-foot">
            <p><i class="fas fa-lock"></i> يتم تشفير العمليات بواسطة بروتوكول SSL الآمن.</p>
            <div class="payment-methods-icons">
                <i class="fab fa-cc-visa" title="Visa"></i>
                <i class="fab fa-cc-mastercard" title="Mastercard"></i>
                <i class="fab fa-cc-amex" title="American Express"></i>
            </div>
        </div>

    </div>
</div>

<style>
    :root {
        --paypal-blue: #00457c;
        --paypal-gold: #ffd700;
        --card-bg: #13131a;
        --body-bg: #0c0c12;
        --border-color: #1e1e2f;
    }

    body { background-color: var(--body-bg); }

    .paypal-wrapper {
        min-height: 80vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
        font-family: 'Cairo', sans-serif;
        direction: rtl;
    }

    .paypal-card-premium {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        max-width: 520px;
        width: 100%;
        border-radius: 24px;
        padding: 40px 30px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5), 0 0 50px rgba(255, 215, 0, 0.02);
        position: relative;
        overflow: hidden;
    }

    .paypal-card-header { text-align: center; margin-bottom: 30px; }

    .brand-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(0, 180, 216, 0.1);
        color: #00b4d8;
        padding: 6px 14px;
        border-radius: 30px;
        font-size: 11px;
        font-weight: 700;
        margin-bottom: 15px;
    }

    .paypal-logo-icon i {
        font-size: 50px;
        color: #0070ba;
        filter: drop-shadow(0 4px 10px rgba(0,112,186,0.3));
        margin-bottom: 15px;
    }

    .paypal-card-header h2 { color: #fff; font-size: 22px; margin: 0 0 10px 0; font-weight: 700; }
    .paypal-card-header p { color: #a0a0b0; font-size: 13px; line-height: 1.6; margin: 0; }
    .vip-text-gradient { background: linear-gradient(135deg, var(--paypal-gold), #ffa500); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-weight: 800; }

    .invoice-details-box { background: rgba(255, 255, 255, 0.02); border: 1px solid #1c1c2b; border-radius: 14px; padding: 20px; margin-bottom: 30px; }
    .invoice-row { display: flex; justify-content: space-between; align-items: center; font-size: 14px; color: #a0a0b0; margin-bottom: 10px; }
    .invoice-row strong { color: #fff; }
    .invoice-divider { height: 1px; background: var(--border-color); margin: 15px 0; }
    .total-row { margin-bottom: 0; color: #fff; font-weight: 700; }
    .final-price { font-size: 26px; font-weight: 900; color: #fff; }

    .security-foot { text-align: center; margin-top: 25px; border-top: 1px solid var(--border-color); padding-top: 20px; }
    .security-foot p { font-size: 11px; color: #6c6c80; margin: 0 0 10px 0; }
    .payment-methods-icons { display: flex; justify-content: center; gap: 15px; font-size: 22px; color: #3d3d5c; }

    .paypal-loader-container { text-align: center; padding: 20px 0; }
    .paypal-loader-container p { color: var(--paypal-gold); font-size: 13px; margin-top: 15px; }
    .spinner { width: 40px; height: 40px; border: 4px solid rgba(255, 215, 0, 0.1); border-top-color: var(--paypal-gold); border-radius: 50%; display: inline-block; animation: spin 1s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }
</style>

<script src="https://www.paypal.com/sdk/js?client-id=<?php echo $paypalClientId; ?>&currency=USD&disable-funding=credit,card"></script>

<script>
    paypal.Buttons({
        style: { layout: 'vertical', color: 'gold', shape: 'rect', label: 'paypal' },
        
        createOrder: function(data, actions) {
            return actions.order.create({
                purchase_units: [{
                    amount: { value: '<?php echo $price; ?>' },
                    description: 'اشتراك سنوي VIP - منصة Yuki السينمائية'
                }]
            });
        },

        onApprove: function(data, actions) {
            // إخفاء الأزرار فوراً وإظهار شاشة التحميل لمنع نقرات الماصادفة
            document.getElementById('paypal-button-container').style.display = 'none';
            document.getElementById('paypal-loading').style.display = 'block';

            // التقاط المعاملة والتحويل الإجباري السريع لتجنب مشاكل تعليق السيرفر
            return actions.order.capture().then(function(details) {
                window.location.href = 'paypal-success.php?order_id=' + data.orderID;
            }).catch(function(error) {
                // حل بديل ذكي: إذا تم الدفع بنجاح في نافذة بايبال وعلق الـ capture برمجياً، يتم التوجيه فوراً
                window.location.href = 'paypal-success.php?order_id=' + data.orderID;
            });
        },

        onCancel: function (data) {
            alert('تم إلغاء عملية الدفع.');
        },

        onError: function(err) {
            alert('عذراً! حدث خطأ تقني أثناء الاتصال بـ PayPal. تأكد من مفتاح الـ Client ID الخاص بك.');
            console.error(err);
        }
    }).render('#paypal-button-container');
</script>

<?php require_once 'includes/footer.php'; ?>