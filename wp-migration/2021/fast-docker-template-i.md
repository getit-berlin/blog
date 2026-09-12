## Fast docker with Nginx, MariaDB and Php, Part I

von gRoot

am 2021-11-01

in IT-Services, Server, Entwicklung, DevOps

Nach vielen Versuchen und Messungen der Perfomance unter Last habe ich folgendes **docker-compose.yaml** erstellt.

Hier ist erstmal die Definition des DB Containers:

```
  db:
    image: mariadb:10.1.48
    container_name: ${DB_HOST}
    restart: always
    ports:
      - '3306:3306'
    volumes:
        - template-db:/var/lib/mysql
    command: ['mysqld', '--character-set-server=${DB_CHARSET}', '--collation-server=${DB_COLLATION}']
    environment:
      MYSQL_USER: ${DB_USER}
      MYSQL_PASSWORD: ${DB_PASS}
      MYSQL_DATABASE: ${DB_NAME}
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASS}

volumes:
  template-db:
```

Die verschiedenen Variablen werden via .env File festgelegt.

```
DB_HOST=deployment-db-2
DB_USER=run
DB_PASS=ldisfk24esf
DB_ROOT_PASS=ldiamQa943
DB_NAME=my_db
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
```

Die Datenbank ist vom Host unter dem dem Port: **3306 und dem localhost** erreichbar. Innerhalb der Docker-Container ist die Datenbank unter dem Host **deployment-db-2** erreichbar.

tag docker Docker-Desktop docker-compose.yaml MariaDB nginx php