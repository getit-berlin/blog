Git erlaubt dem User: www-data nicht mehr git Kommandos auszuführen:

`fatal: unsafe repository ('/my-test-repo' is owned by someone else)`  
`To add an exception for this directory, call:`

um das zu lösen muss man das in dem Repo explizit erlauben:

`git config --global --add safe.directory /my-test-repo`

Oder man lässt es mit dem www-data User und nutzt den dafür vorgesehenen User.
