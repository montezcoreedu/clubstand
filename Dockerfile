# Use the official PHP image
FROM php:8.2-apache

# Install dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev libzip-dev zip unzip git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd mysqli pdo pdo_mysql zip

# Copy app source code
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html

# Expose web port
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]