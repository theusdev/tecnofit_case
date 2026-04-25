FROM php:8.1-fpm

# Instala dependencias do sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# Limpa cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Instala extensoes PHP
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Instala Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Define diretorio de trabalho
WORKDIR /var/www

# Define permissoes
RUN chown -R www-data:www-data /var/www

# Expoe porta 9000
EXPOSE 9000

CMD ["php-fpm"]
