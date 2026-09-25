FROM php:8.0-apache

# Install MySQL/MariaDB support
RUN docker-php-ext-install mysqli

# Remove every enabled Apache MPM configuration
RUN rm -f /etc/apache2/mods-enabled/mpm_*.load \
          /etc/apache2/mods-enabled/mpm_*.conf

# Enable only one MPM
RUN a2enmod mpm_prefork rewrite

# Verify Apache MPM configuration during Docker build
RUN echo "===== APACHE MPM CHECK =====" \
    && apachectl -M 2>&1 | grep mpm

# Copy PharmacyX project
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80