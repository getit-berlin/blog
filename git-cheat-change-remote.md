Hier ein Codeschnipsel zum Wechseln des GIT Servers/Remote Repo: 

`git remote rename origin old-origin`  
`git remote add origin ssh:[//git@git.....t]`  

`git push -u origin --all`  

`git remote -v`  

falls das nicht funktioniert, dann kann es an der Verbindung zum GIT Server liegen. Dann lohnt ein blick in die: 

 `~./ssh keys`

Hier findet man die offiziele man-page: https://git-scm.com/docs/git-remote
