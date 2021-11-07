Verwendung / Entwicklung
========================

Docker-Umgebung
---------------

Am einfachsten mit Docker (und [Docker Compose](https://docs.docker.com/compose/install/)):
Repo klonen, in den Ordner wechseln und

    docker-compose up -d

ausführen und im Webbrowser

    http://localhost:8000/

öffnen & die WordPress Installation durchklicken (geht schnell) und anmelden.

Danach vom [RRZE-Webteam GitHub das FAU-Techfak Theme](https://github.com/RRZE-Webteam/FAU-Techfak/releases)
als `.zip` herunter laden, und im [WordPress Administrator unter Design / Themes](http://localhost:8000/wp-admin/theme-install.php)
auf *Theme hochladen* gehen, und die gerade eben geladene `FAU-Techfak-1.xx.zip`
auswählen und *Jetzt installieren*.
Danach auf der [Theme Übersichtsseite](http://localhost:8000/wp-admin/themes.php)
noch *FAU-Techfak* aktivieren.

Nun im [WordPress Administrator unter Plugins](http://localhost:8000/wp-admin/plugins.php)
*i4include* aktivieren, zu [Einstellung / Permalinks](http://localhost:8000/wp-admin/options-permalink.php)
wechseln und als *Individuelle Struktur* 

    /%year%/%monthnum%/%day%/%postname%

angeben & *Änderungen speichern* (je nach Berechtigungen muss nun noch
`example-wordpress/.htaccess` angepasst werden, wird aber im Interface angezeigt --
alternativ die Schreibberechtigungen mit `chmod a+w example-wordpress/.htaccess`
setzen und erneut speichern).


Unter Umständen müssen Änderungen an den Rewrite-Rules noch erneuert werden,
dazu zum Beispiel in `function rewrite_endpoint()` (Datei `plugin/includes/i4include.php`)
die auskommentierte Zeile 

    flush_rewrite_rules();

temporär aktivieren, Seite neu laden & wieder auskommentieren.


Verwendung
----------

Nun im [Adminmenü auf Seiten](http://localhost:8000/wp-admin/edit.php?post_type=page)
wechseln und entweder eine neue Seite erstellen oder die *Beispiel-Seite* bearbeiten,
dort dann einen Shortcode einfügen, z.B.

    [i4include dynamic="true"]bar.html[/i4include]
    [i4include dynamic="true" shortcodes="true" showerrors="true"]error.html[/i4include]

Außerdem kann das [RRZE Elements Plugin von GitHub](https://github.com/RRZE-Webteam/rrze-elements)
(via [*Code* / *Download ZIP*](https://github.com/RRZE-Webteam/rrze-elements/archive/refs/heads/master.zip)
heruntergeladen und im [WordPress Admininterface](http://localhost:8000/wp-admin/plugins.php)
installiert und aktiviert werden, damit das Beispiel

    [i4include  shortcodes="true"]shortcode.html[/i4include]

korrekt dargestellt wird.


i4 WordPress
------------

**Nur in Absprache mit Bernhard oder Christian aktualisieren!**

Den Inhalt des `plugin`-Ordners nach

    /var/www/wordpress/wp-content/plugins/i4werkzeug/

kopieren.
