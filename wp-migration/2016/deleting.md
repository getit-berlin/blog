## Deleting with ZFS

von gRoot

am 2016-11-08

in IT-Services, Server

Why are deletions slow?

- Deleting a file requires a several steps. The file metadata must be marked as 'deleted', and eventually it must be reclaimed so the space can be reused. ZFS is a 'log structured filesystem' which performs best if you only ever create things, never delete them. The log structure means that if you delete something, there's a gap in the log and so other data must be rearranged (defragmented) to fill the gap. This is invisible to the user but generally slow.
- The changes must be made in such a way that if power were to fail partway through, the filesystem remains consistent. Often, this means waiting until the disk confirms that data really is on the media; for an SSD, that can take a long time (hundreds of milliseconds). The net effect of this is that there is a lot more bookkeeping (i.e. disk I/O operations).
- All of the changes are small. Instead of reading, writing and erasing whole flash blocks (or cylinders for a magnetic disk) you need to modify a little bit of one. To do this, the hardware must read in a whole block or cylinder, modify it in memory, then write it out to the media again. This takes a long time.

What can be done?

```
zfs create -o compression=on -o exec=on -o setuid=off zroot/tmp ;

chmod 1777 /zroot/tmp ;

zfs set mountpoint=/tmp zroot/tmp

copy to /zroot/tmp

zfs delete zroot/tmp
```

source: http://serverfault.com/questions/801074/delete-10m-files-from-zfs-effectively

tag OmniOS ZFS zpool