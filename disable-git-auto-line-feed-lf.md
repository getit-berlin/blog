Hier ein kleines Git Schnipsel zum deaktivieren der LF Korrektur. Manchmal verursacht das Probleme, wenn man mit Windows & Unix-Derivaten arbeitet. Es mag sinnvoll sein die Auto-Korrektur zu deaktivieren.

Die Einstellung wird über den Parameter `–global` für alle Repositories festgelegt.

`git config --global core.autocrlf false`  
`git config --global core.eol lf`
