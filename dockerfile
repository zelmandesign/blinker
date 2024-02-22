# Use the official PHP 8.1 image with Apache as the base image
FROM php:8.1-apache

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install unzip and git
RUN apt-get update && apt-get install -y unzip git

# Enable Apache modules and configure document root
RUN a2enmod rewrite && \
    echo "ServerName localhost" >> /etc/apache2/apache2.conf && \
    sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf && \
    sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/default-ssl.conf

# Set the document root to /var/www/html/public
WORKDIR /var/www/html/public

# Copy composer.json to the /var/www/html/public directory
COPY composer.json /var/www/html/public

# Install Composer dependencies inside /var/www/html/public
RUN composer install

# Adjust ownership and permissions for the vendor directory and its contents
RUN chown -R www-data:www-data /var/www/html/public/vendor \
    && find /var/www/html/public/vendor -type d -exec chmod 755 {} \; \
    && find /var/www/html/public/vendor -type f -exec chmod 644 {} \;

# Copy your PHP application files to the container
COPY . /var/www/html/public

# Make the test script executable
COPY run-tests.sh /var/www/html/public/run-tests.sh
RUN chmod +x /var/www/html/public/run-tests.sh

# Adjust ownership and permissions during the image build
RUN chown -R www-data:www-data /var/www/html/public \
    && find /var/www/html/public -type d -exec chmod 755 {} \; \
    && find /var/www/html/public -type f -exec chmod 644 {} \; \
    && find /var/www/html/public -type f -name "*.sh" -exec chmod +x {} \;

# Set the DirectoryIndex directive to include index.php
RUN echo "DirectoryIndex index.php" >> /etc/apache2/apache2.conf

# Expose port 80 for Apache
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
