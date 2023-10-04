Git nutzt unter anderem das SSH Protokoll. Manchmal kann man trotz korrektem ssh-key oder entsprechenden Credentials kein Repository pushen oder pullen. Hier ein pratischer Weg zum troubleshooting der Verbindung:

`ssh -vT git@github.com`

`ssh -vvT git@github.com`

`ssh -vvvT git@github.com`

Der -v Parameter kann noch angepasst werden und gibt die Debug Level 1-3 aus. Die Ausgabe sieht dann wiefolgt aus.

`debug3: remaining preferred: keyboard-interactive,password`  
`debug3: authmethod_is_enabled publickey`  
`debug1: Next authentication method: publickey`  
`debug1: Trying private key: C:\\Users\\Xxxx/.ssh/id_rsa`  

Falls man den SSH Befehl den GIT benutzt, anpassen muss, kann das über den GIT_SSH_COMMAND Parameter gemacht werden. Mehr Informationen hierzu: https://git-scm.com/docs/git#Documentation/git.txt-codeGITSSHCOMMANDcode

Die Anpassung des `GIT_SSH_COMMAND` mag für den Zugriff von einem beschränkten Webhoster-System nötig sein (wie z.T. 1&1)
