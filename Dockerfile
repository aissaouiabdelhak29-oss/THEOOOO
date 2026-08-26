FROM php:8.2-apache

# تثبيت إضافة PostgreSQL
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# تفعيل mod_rewrite
RUN a2enmod rewrite

# نسخ المشروع
COPY . /var/www/html/

# تحديد مجلد yuki كـ DocumentRoot
ENV APACHE_DOCUMENT_ROOT /var/www/html/yuki
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# صلاحيات
RUN chown -R www-data:www-data /var/www/html
