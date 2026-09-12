## Change permissions, files and directories

von gRoot

am 2021-05-17

in Entwicklung

How to change all permissions on files and directories? That's easy, but how to include all directories and files in underlying directories?

It can be done like this:

```
find . -type d -exec chmod 0755 {} \;
find . -type f -exec chmod 0644 {} \;
```

tag bash linux ubuntu