# Yuki Movie Streaming Platform

منصة Yuki للبث السينمائي - مشروع PHP كامل

## المتطلبات
- PHP 7.4+
- MySQL 5.7+
- Apache مع mod_rewrite

## التثبيت

1. انسخ الملفات إلى مجلد المشروع
2. أنشئ قاعدة بيانات باسم `yuki`
3. استورد ملف `database/yuki.sql`
4. عدل إعدادات الاتصال في `includes/db.php`
5. تأكد من صلاحيات المجلدات:
   - `uploads/` ومجلداتها الفرعية: 755
   - `assets/images/`: 755

## تسجيل الدخول الافتراضي
- البريد: admin@yuki.com
- كلمة المرور: admin123

## هيكل المشروع
```
yuki/
├── index.php          # الصفحة الرئيسية
├── login.php          # تسجيل الدخول
├── register.php       # إنشاء حساب
├── movies.php         # الأفلام
├── series.php         # المسلسلات
├── watch.php          # مشاهدة المحتوى
├── favorites.php      # المفضلة
├── profile.php        # الملف الشخصي
├── search.php         # البحث
├── category.php       # تصفح التصنيفات
├── logout.php         # تسجيل الخروج
├── ajax/
│   └── favorite.php   # AJAX المفضلة
├── includes/
│   ├── db.php         # الاتصال بقاعدة البيانات
│   ├── auth.php       # المصادقة
│   ├── functions.php  # الدوال المساعدة
│   ├── header.php     # ترويسة الموقع
│   └── footer.php     # تذييل الموقع
├── admin/             # لوحة التحكم
│   ├── index.php      # لوحة التحكم الرئيسية
│   ├── content.php    # إدارة المحتوى
│   ├── content-add.php
│   ├── content-edit.php
│   ├── content-delete.php
│   ├── episodes.php   # إدارة الحلقات
│   ├── episode-add.php
│   ├── episode-edit.php
│   ├── episode-delete.php
│   ├── categories.php
│   ├── genres.php
│   ├── users.php      # إدارة المستخدمين
│   ├── user-edit.php
│   ├── user-toggle.php
│   ├── user-delete.php
│   ├── settings.php   # إعدادات الموقع
│   └── includes/
│       ├── admin-header.php
│       └── admin-footer.php
├── assets/
│   ├── css/style.css  # التنسيقات
│   ├── js/main.js     # الجافاسكريبت
│   └── images/        # الصور الافتراضية
├── uploads/           # مجلد الرفع
│   ├── posters/
│   ├── backdrops/
│   ├── episodes/
│   └── avatars/
└── database/
    └── yuki.sql       # قاعدة البيانات
```

## المميزات
- ✅ تصميم داكن حديث (Dark Theme)
- ✅ دعم كامل للغة العربية (RTL)
- ✅ مشغل فيديو متعدد السيرفرات (StreamHG, EarnVids, Mixdrop, DoodStream)
- ✅ نظام مفضلة
- ✅ إدارة محتوى كاملة
- ✅ إدارة حلقات المسلسلات
- ✅ إدارة المستخدمين
- ✅ إعدادات الموقع
- ✅ حماية CSRF
- ✅ Prepared Statements
- ✅ XSS Protection
- ✅ Responsive Design

## روابط التواصل الاجتماعي
- Facebook: https://www.facebook.com/share/14dXFFBeYYp/
- Instagram: https://www.instagram.com/kyou__999
- Telegram: https://t.me/213557740724
- X (Twitter): https://x.com/Yuki____999
