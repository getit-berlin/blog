## Don't let "Your unqualified host name" to get on your nerves (Sunny Affairs)

von gRoot

am 2017-04-30

in IT-Services

> For that we need to edit the file /etc/inet/hosts. Now this file has entry in the following format:So to change the domain name, you need to give this file a write permission and then go on to change the second word to any FQDN eg mydomain.com Save the file and reboot, the warning should no longer appear.To change permission: chmod u+w hosts

Quelle: _[Don't let "Your unqualified host name" to get on your nerves (Sunny Affairs)](https://blogs.oracle.com/souvik/entry/my_unqualified_host_name_sleeping)_

Wie wird man diese Meldung: "Your unqualified host name" wieder los? A & O ist ein sinnvoller `/etc/inet/hosts` Eintrag.

tag FQDN server Software-defined-Storage solaris