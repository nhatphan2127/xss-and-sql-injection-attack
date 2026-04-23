FROM php:8.2-apache

# Enable required PHP extensions
RUN docker-php-ext-install mysqli
RUN docker-php-ext-enable mysqli

# Enable Apache mod_rewrite if needed
RUN a2enmod rewrite

WORKDIR /var/www/html
