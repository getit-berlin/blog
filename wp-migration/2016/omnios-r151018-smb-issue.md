## OmniOS r151018 SMB issue

von gRoot

am 2016-09-25

in IT-Services, Server

Wenn man SMB produktiv mit OmniOS benutzt, dann könnte man auf folgendes Issue stoßen.

Wenn man auf das OmniOS SMB Share speichern will, mit "Speichern unter", dann kommt es zum einfrieren der Aktion. Das betrifft beliebige Dateiengrößen.

Zwischen der OmniOS Version r151016-r151018 gabe es ein Paket, welches dieses Problem reingebracht hat. Also downgraden oder man kann das Problem lösen indem man den opslock disabled:

```
svccfg -s network/smb/server setprop smbd/oplock_enable=false
```

opslock ist Opportunistic Locking und bezieht sich auf das Sperren von Dateizugriffen, wenn mehrere auf eine Datei zugreifen.

opslock könnte man auch auf der Windows-Seite verändern (Registry), das macht aber nicht so viel Sinn.

tag consulting OmniOS smb ZFS