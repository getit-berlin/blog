## Github und 1&1

von gRoot

am 2020-03-16

in IT-Services, Entwicklung

Seit Github seine Politik mit internen Projekten geändert hat, setzen wir es exzessiv ein. *träum* Siehe auch unser [Getit-Berlin Github](https://github.com/getit-berlin). Oder meine eigene [JWalczak Github](https://github.com/q-u-o-s-a/).

Wie man vielleicht schon mal schmerzlich feststellen musste, kann man von bestimmten 1&1 Verträgen (unser geehrter Kunde hat da ein Paket) nicht so einfach zu Github "pull"en.

Folgender Codesnippsel erleichtert uns die Arbeit ...

```
pl.sh
GIT_SSH=/kunden/homepages/yy/dxxxxxx/htdocs/ssh git pull
```

und

```
pu.sh
GIT_SSH=/kunden/homepages/yy/dxxxxxx/htdocs/ssh git push --set-upstream origin master
```

Man schau sich blos den Dateinamen an 😉 "pu.sh"

Natürlich muss man noch die ssh Datei hinzufügen. Die enhält folgendes:

```
ssh -o UserKnownHostsFile=/dev/null -i /kunden/homepages/yy/dxxxxxx/htdocs/id_rsa -o StrictHostKeyChecking=no $*
```

Die id_rsa Datei enthält unseren Github ssh-key. Et voilà push und pull funktionieren wie gewohnt. *puhhh*

NACHTRAG: Natürlich noch chown Rechte beachten.

tag 1&1 bash git Github ssh