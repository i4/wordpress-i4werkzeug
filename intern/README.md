Interner Bereich
================

Diese kleine Erweiterung erlaubt den Zugriff auf Dateien in einem gesonderten Verzeichnis für (in WordPress) angemeldete Benutzer oder Zugriffe aus einem bestimmten Netz.


Installation
------------

In der Datei `dl_intern.php` ggf. die `define`s anpassen und danach in das WordPress Wurzelverzeichnis kopieren (z.B. `/var/www/wordpress/`).
Die dort liegende `.htaccess`-Datei um einen Eintrag erweitern, welcher Zugriffe in einem (virtuellen) Unterordner an das zuvor kopierte PHP Skript weiter reicht:
```
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteRule ^intern/(.*)$ /dl-intern.php?file=$1 [QSA,L]
</IfModule>
```

**Wichtig:** Der Zielordner (z.B. `/proj/i4wp/intern`) wird **nicht** via Apache ausgeliefert, sondern nur über das obige Skript.
Entsprechend braucht der Benutzer unter dem der Webserver läuft leseberechtigungen für den Zielordner.
