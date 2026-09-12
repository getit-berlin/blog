## Disable git auto line feed LF

von gRoot

am 2022-12-01

in DevOps

Hier ein kleines Git Schnipsel zum deaktivieren der LF Korrektur. Manchmal verursacht das Probleme, wenn man mit Windows & Unix-Derivaten arbeitet. Es mag sinnvoll sein die Auto-Korrektur zu deaktivieren.

Die Einstellung wird über den Parameter `–global` für alle Repositories festgelegt.

`git config --global core.autocrlf false`  
`git config --global core.eol lf`

tag bash git