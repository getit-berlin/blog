## Wie lange läuft ein ZFS Storagesystem?

von gRoot

am 2017-01-15

in Server

Wie lange läuft ein ZFS Storagesystem? Hier ein Livesystem;

```
user@hostname> uname -a
SunOS hostname 5.8 Generic_108528-07 sun4u sparc SUNW,Ultra-2

user@hostname> uptime
  1:10pm  up 5241 day(s),  3:54,  1 user,  load average: 0.01, 0.01, 0.01

user@hostname> psrinfo -v
Status of processor 0 as of: 09/16/16 13:11:49
  Processor has been on-line since 05/12/02 09:15:42.
  The sparcv9 processor operates at 200 MHz,
        and has a sparcv9 floating point processor.
Status of processor 1 as of: 09/16/16 13:11:49
  Processor has been on-line since 05/12/02 09:15:47.
  The sparcv9 processor operates at 200 MHz,
        and has a sparcv9 floating point processor.

user@hostname> prtconf -vp | grep Mem
Memory size: 512 Megabytes
```

Dank an reddit: https://www.reddit.com/r/zfs/

tag OmniOS server Software defined Storage ZFS