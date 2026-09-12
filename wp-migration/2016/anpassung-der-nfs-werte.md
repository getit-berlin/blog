## Anpassung der NFS Werte

von gRoot

am 2016-03-22

in IT-Services, Server

Nach einigen Problemen mit der ZFS Pool Performance habe ich paar NFS Werte verändert ... und schon fühlt dich der Pool nicht mehr so langsam an:

```
root@Napp:~# sharectl get nfs
servers=512
lockd_listen_backlog=256
lockd_servers=256
lockd_retransmit_timeout=5
grace_period=90
server_versmin=2
server_versmax=3
client_versmin=2
client_versmax=3
server_delegation=on
nfsmapid_domain=
max_connections=-1
protocol=ALL
listen_backlog=32
device=
```

tag Napp-it nfs Performance sharectl ZFS zpool