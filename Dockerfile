FROM php:8.0-apache

# Install MySQL/MariaDB support for PHP
RUN docker-php-ext-install mysqli

# Enable Apache rewrite module
RUN a2enmod rewrite

# Copy PharmacyX project into Apache web root
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Apache listens on port 80
EXPOSE 80