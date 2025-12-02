FROM php:8.2-apache

# Install MySQL extensions
RUN apt-get update \
 && apt-get install -y libcurl4-openssl-dev \
 && docker-php-ext-install mysqli pdo pdo_mysql curl


# Enable Apache modules
RUN a2enmod rewrite

# Set ServerName
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

WORKDIR /var/www/html

# Copy application files
COPY . .

# Set permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80