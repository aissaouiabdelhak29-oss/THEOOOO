<?php
$fbUrl = secure_url(getSetting('facebook_url', 'https://www.facebook.com/share/14dXFFBeYYp/'));
$igUrl = secure_url(getSetting('instagram_url', 'https://www.instagram.com/kyou__999'));
$tgUrl = secure_url(getSetting('telegram_url', 'https://t.me/213557740724'));
$xUrl  = secure_url(getSetting('twitter_url', 'https://x.com/Yuki____999'));
$siteName = getSetting('site_name', 'Yuki');
?>
    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <a href="index.php" class="footer-logo">
                        <i class="fas fa-play-circle"></i>
                        <span><?php echo sanitize($siteName); ?></span>
                    </a>
                    <p class="footer-desc">منصة Yuki للبث السينمائي - شاهد أحدث الأفلام والمسلسلات بجودة عالية</p>
                    <div class="footer-social">
                        <a href="<?php echo sanitize($fbUrl); ?>" target="_blank" rel="noopener noreferrer" class="social-link facebook" title="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="<?php echo sanitize($igUrl); ?>" target="_blank" rel="noopener noreferrer" class="social-link instagram" title="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="<?php echo sanitize($tgUrl); ?>" target="_blank" rel="noopener noreferrer" class="social-link telegram" title="Telegram">
                            <i class="fab fa-telegram-plane"></i>
                        </a>
                        <a href="<?php echo sanitize($xUrl); ?>" target="_blank" rel="noopener noreferrer" class="social-link twitter" title="X (Twitter)">
                            <i class="fab fa-x-twitter"></i>
                        </a>
                    </div>
                </div>

                <div class="footer-links-col">
                    <h4>التصنيفات</h4>
                    <ul>
                        <li><a href="movies.php"><i class="fas fa-film"></i> الأفلام</a></li>
                        <li><a href="series.php"><i class="fas fa-tv"></i> المسلسلات</a></li>
                        <li><a href="category.php?slug=anime"><i class="fas fa-star"></i> أنمي</a></li>
                        <li><a href="category.php?slug=documentary"><i class="fas fa-book"></i> وثائقي</a></li>
                    </ul>
                </div>

                <div class="footer-links-col">
                    <h4>الحساب</h4>
                    <ul>
                        <li><a href="profile.php"><i class="fas fa-user"></i> الملف الشخصي</a></li>
                        <li><a href="favorites.php"><i class="fas fa-heart"></i> المفضلة</a></li>
                        <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> تسجيل الدخول</a></li>
                        <li><a href="register.php"><i class="fas fa-user-plus"></i> إنشاء حساب</a></li>
                    </ul>
                </div>

                <div class="footer-links-col">
                    <h4>عن Yuki</h4>
                    <ul>
                        <li><a href="about.php"><i class="fas fa-info-circle"></i> من نحن</a></li>
                        <li><a href="privacy.php"><i class="fas fa-shield-alt"></i> سياسة الخصوصية</a></li>
                        <li><a href="terms.php"><i class="fas fa-file-contract"></i> شروط الاستخدام</a></li>
                        <li><a href="contact.php"><i class="fas fa-envelope"></i> اتصل بنا</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo sanitize($siteName); ?>. جميع الحقوق محفوظة.</p>
            </div>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
<script>
$(document).ready(function() {
    $('.stars i').click(function() {
        var rating = $(this).data('value');
        var contentId = $('.stars').data('content-id');

        $.post('ajax/rate.php', { content_id: contentId, rating: rating }, function(response) {
            if(response.success) {
                alert('تم تسجيل تقييمك!');
                $('#avg-rating').text('متوسط التقييم: ' + response.new_average);
            } else {
                alert(response.message);
            }
        }, 'json');
    });
});
</script>
    <?php if (isset($extraJs)) echo $extraJs; ?>

</body>
</html>