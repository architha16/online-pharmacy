FROM php:8.0-apache

# Install MySQL/MariaDB support for PHP
RUN docker-php-ext-install mysqli

# Make sure only one Apache MPM is enabled
RUN a2dismod mpm_event mpm_worker mpm_auto || true \
    && a2enmod mpm_prefork rewrite

# Copy PharmacyX project into Apache web root
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Apache listens on port 80
EXPOSE 80