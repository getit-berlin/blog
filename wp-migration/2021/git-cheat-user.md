## Git Cheat: user

von gRoot

am 2021-08-01

in DevOps

```
Anzeigen
git config --global --list
git config --global user.name
git config --global user.email
Editieren
git config --global user.name="xxx"
git config --global user.email="xx@xx.xx"
Löschen
git config --unset user.name
git config --unset user.email
```

Hier ein Codeschnipsel zur manipulation des Git-Users. Hier findet man die offiziele man-page: https://git-scm.com/docs/git-config

tag bash git