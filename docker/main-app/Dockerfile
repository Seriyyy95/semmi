FROM php:8.0
RUN apt-get update && apt-get install -y \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libmcrypt-dev \
        libpng-dev \
        zlib1g-dev \
        libxml2-dev \
        libzip-dev \
        libonig-dev \
        graphviz \
        libpq-dev \
        curl \
        vim \
        git \
        libzip-dev \
        zip

RUN docker-php-ext-install pdo pdo_mysql mbstring pcntl zip

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

ENV TZ=Europe/Kiev
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

WORKDIR /var/www
CMD /usr/local/bin/php artisan migrate && /usr/local/bin/php artisan queue:work database --queue gsc_data --timeout 0

