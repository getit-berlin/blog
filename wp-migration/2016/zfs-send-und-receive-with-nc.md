## ZFS send und receive with nc

von gRoot

am 2016-10-07

in IT-Services, Server

ZFS send & receive is a great feature and a good solution for remote backup. How to _receive_ and then(!) _send_ zfs snapshots?

Here is my codesnippet;

```
root@local:~# nc remote.dyndns.org 22553 | zfs receive -vd vol1
root@remoteserver:~# zfs send vol2/services/datastore_l@1 | nc -l -p 22553
```

works fine with OmniOS and Openindiana;

tag consulting Napp-it OmniOS ZFS zpool