## Fast docker with Nginx, MariaDB and Php, Part III

von gRoot

am 2022-01-01

in IT-Services, Server, Entwicklung, DevOps

Die Anwendungsdaten unter **/var/www/html** werden nicht in das Hostsystem gemountet. Das wurde im [Fast Docker Part II](https://www.getit-berlin.de/fast-docker-with-nginx-mariadb-and-php-part-ii/) beschrieben. Das Directory /var/www/html wird in das volume gemountet:

```
    volumes:
      - template-html:/var/www/html
```

Um in dem Volume zu schreiben benötigen wir einen weiteren container. Dieses Verfahren beschleunigt radikal die Funktionsweise der Container. Daten werden nicht in das Hostsystem gemountet sondern bleiben im Container und im Besten Fall sogar im Cache und Arbeitsspeicher.

Die Konfiguration des sftp / scp Dienstes sieht wiefolgt aus:

```
  sftp:
    image: atmoz/sftp
    volumes:
      - template-html:/home/${SFTP_USER}/upload
    ports:
        - "${SFTP_PORT}:22"
    command: ${SFTP_USER}:${SFTP_PASS}:33
```

Als Basis für den Container wird **atmoz/sftp** verwendet.

Die entsprechenden Parameter werden über die .env Datei festgelegt.

```
SFTP_HOST=127.0.0.1
SFTP_PORT=2222
SFTP_USER=runner
SFTP_PASS=pass
```

Die Zeile:

```
command: ${SFTP_USER}:${SFTP_PASS}:33
```

legt fest welcher Systemuser den angemeldeten SCP /SFTP User representiert.

Das entsprechende Github Projekt kann man unter https://github.com/atmoz/sftp finden.

Das gesamte Projekt befindet hier auf https://github.com/getit-berlin/fast-docker-php-template

tag atmoz/sftp docker Docker Desktop docker-compose.yaml Github scp sftp