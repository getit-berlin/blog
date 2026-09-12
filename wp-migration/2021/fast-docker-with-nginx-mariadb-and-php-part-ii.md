## Fast docker with Nginx, MariaDB and Php, Part II

von gRoot

am 2021-12-01

in IT-Services, Server, Entwicklung, DevOps

Im letzen Beitrag zum Thema [fast docker Part I](https://www.getit-berlin.de/fast-docker-template-i/) habe ich einen einfachen DB Container erstellt. Hier folgt jetzt der dazugehörige Php Container:

```
  php:
    ports:
      - '22:22'
    build: ./container/php/.
    volumes:
      - template-html:/var/www/html
      - ./container/php/php.ini:/usr/local/etc/php/php.ini
      - ./container/php/log.conf:/usr/local/etc/php-fpm.d/zz-log.conf
    environment:
      PHP_OPCACHE_VALIDATE_TIMESTAMPS: 1
volumes:
  template-html:
```

Die Datei **php.ini** wurde nach "außen" in den Ordner container/php/ gelegt. Damit ist sie im auch nach dem Build und Deploy leicht veränderbar.

Das Volume **template-html** wird alle Anwendungsdaten beinhalten. Es entspricht dem **/var/www/html** directory.

Das Volume wird **nicht** nach außen gemountet.

Das Docker Container File **Dockerfile** beinhaltet weitere Pakete für den php container, wie z.B. zip, mysql, opcache, gd, composer, git, wget, apcu, imagemagick und basiert auf PHP 7.4-fpm:

```
FROM php:7.4-fpm
USER root
RUN apt-get update --fix-missing
RUN apt-get install --yes libicu-dev
RUN apt-get install --yes libjpeg62-turbo-dev
RUN apt-get install --yes libfreetype6-dev
RUN apt-get install --yes libpng-dev

RUN docker-php-ext-install -j$(nproc) mysqli pdo_mysql opcache
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install -j$(nproc) gd
RUN docker-php-ext-configure intl && \
    docker-php-ext-install -j$(nproc) intl

ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS="0"
ADD opcache.ini "$PHP_INI_DIR/conf.d/opcache.ini"
RUN cd /usr/src && \
    curl -sS http://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer

RUN apt-get install --yes sudo

RUN apt-get install --yes unzip
RUN apt-get install --yes zip
RUN apt-get install --yes git
RUN apt-get install --yes bzip2
RUN apt-get install --yes libbz2-dev
RUN docker-php-ext-install bz2
RUN apt-get install --yes wget
RUN apt-get install -y default-mysql-client
RUN apt-get install -y \
        libzip-dev \
        zip \
  && docker-php-ext-install zip
# APCU
RUN pecl channel-update pecl.php.net
RUN pecl install apcu igbinary
RUN docker-php-ext-enable apcu igbinary
# IMAGICK
RUN apt-get update && apt-get install -y libmagickwand-dev --no-install-recommends && rm -rf /var/lib/apt/lists/*
RUN printf "\n" | pecl install imagick
RUN docker-php-ext-enable imagick
RUN apt-get update && apt-get install -y imagemagick
RUN docker-php-source delete
#USER www-data
USER root
```

Die Datei **opcache.ini** wird für den opcache gebraucht.

Das gesamte Projekt befindet hier auf https://github.com/getit-berlin/fast-docker-php-template

tag apcu composer docker Docker-Desktop docker-compose.yaml git imagemagick opcache php wget zip