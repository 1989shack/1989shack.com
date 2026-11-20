# 1989 SHACK - Global Platform - Full Dockerfile
# PHP 8.2 + Apache - For Render
FROM php:8.2-apache

# Install PHP extensions your index.php needs
# pdo + mysqli for database, gd for images, zip + mbstring for uploads
RUN docker-php-ext-install pdo pdo_mysql mysqli \
    && docker-php-ext-enable pdo_mysql mysqli \
    && apt-get update && apt-get install -y libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd zip mbstring exif \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Set Apache to serve from /var/www/html
WORKDIR /var/www/html

# Copy ALL your files from GitHub into Apache's folder
# This is the line that was missing - it puts index.php where Apache looks
COPY . /var/www/html/

# Allow .htaccess and fix permissions so uploads + images work
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && mkdir -p /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 777 /var/www/html/uploads

# Make Apache listen on Render's PORT (Render gives us $PORT, default 10000)
# This is the 2nd missing piece - without this Render can't see Apache
RUN sed -i 's/Listen 80/Listen ${PORT}/g' /etc/apache2/ports.conf \
    && sed -i 's/:80/:${PORT}/g' /etc/apache2/sites-enabled/000-default.conf

# Tell Apache to use index.php first, not index.html
RUN echo "DirectoryIndex index.php index.html" >> /etc/apache2/apache2.conf

# Allow .htaccess overrides for pretty URLs
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

EXPOSE 10000

# Start Apache
CMD ["apache2-foreground"]