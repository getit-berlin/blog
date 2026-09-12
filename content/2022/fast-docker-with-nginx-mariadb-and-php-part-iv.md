## Fast docker with Nginx, MariaDB and Php, Part IV

von gRoot

am 2022-02-01

in IT-Services, Server, Entwicklung, DevOps

Nach dem erstellen der :

- DB Container auf Basis von _mariadb:10.1.48_
- PHP Container auf Basis von _php:7.4-fpm_
- SCP Container auf Basis von _atmoz/sftp_

benötigen wir noch einen Nginx Container der zum Host Port 80 freigibt.

Die Konfiguration für den Nginx Container inkl. der Anhängigkeiten zu den Containern aus Part I-III sieht wiefolgt aus:

```
version: '3'
services:
  web:
    image: nginx:latest
    depends_on:
      - sftp
      - php
      - db
    ports:
      - '80:80'
    volumes:
      - template-html:/var/www/html
      - ./container/web/nginx.conf:/etc/nginx/conf.d/default.conf
```

Ebenso wie der Php Container und der Sftp Container mountet der Nginx Container das **template-html** Volume.

Das Config-File **./container/web/nginx.conf** wird in das Host-Dateisystem gemounet um einfach editiert zu werden.

Das gesamte Projekt befindet hier auf https://github.com/getit-berlin/fast-docker-php-template

tag atmoz/sftp docker docker-compose.yaml mariadb:10.1.48 nginx php