WordPress Plugin `i4include`
===========================

Dieses Plugin ermöglicht das Einbinden von HTML-Inhalten in WordPress-Seiten
aus anderen Ressourcen (Webserver, Dateisystem).


**Beispiel:** Durch den Shortcode in der Wordpressseite 'Test'

    [i4include]/var/www/data/foo1.html[/i4include]

wird der Inhalt der Datei `foo.html` einfach in der Ausgabe auf

    https://server/test

inkludiert.
Dabei wird immer der zum Ausgabezeitpunkt jeweils aktuelle Inhalt angezeigt,
d.h. Änderungen an der Datei `foo.html` sind sofort auf der Webseite ersichtlich.
Die erlaubten Formate zum Einbinden werden im  Array `$i4include_ext_html`
anhand der erlaubten Dateieendung angegeben.


Neben dem statischen Einbetten des Inhalts einer konkreten Datei beherrscht
dieses Plugin jedoch noch die Möglichkeit, dynamisch Dateien aus einen ganzen
Verzeichnisbaum auszuliefern, d.h. (relative) Verlinkungen in diesen Datei
werden auf der Webseite unter Veibehaltung des WordPresslayouts unterstützt.

Nehmen wir mal an, dass im Verzeichnis `/var/www/data/` neben `bar.html` auch 
noch die Dateien `fubar.html` sowie  ein Unterorderner `images` u.a. mit der
Bilddatei `baz.jpg` liegen und `bar.html` sowohl einen Link
`<a href="fubar.html">Fubar</a>` als auch eine Grafik `<img src="images/baz.jpg">`
beinhaltet.

Wird nun auf der Wordpressseite 'Test' beim Einbetten der Parameter `dynamic`
auf wahr gestellt

    [i4include dynamic="true"]/var/www/data/bar.html[/i4include]

so wird beim Öffnen der WordPress-Webseiten-URL

    https://server/test

automatisch auf

    https://server/test/extern/bar.html

weitergeleitet. Das `extern` in der URL ist dabei ein sog. Endpoint,
wodurch die in der URL nachfolgenden Inhalte im gleichnamigen Parameter
gespeichert werden, also im Beispiel quasi `extern=bar.html`.
Durch einen Endpoint werden dynamische Inhalte auf der selben WordPress-Seite 
ermöglicht, bei uns wird dieser Trick verwendet, damit nun der Link **Fubar**
auf `fubar.html` durch den Browser zu

    https://server/test/extern/fubar.html

umgewandelt wird (mit `extern=fubar.html`), das Plugin bettet nun auf der
gleichen WordPress-Seite an der Position des Shortcodes `[i4include...]` den
Inhalt von `fubar.html` ein -- d.h. das WordPresslayout (Kopf und Fuß) bleibt
bestehen, es sieht aus wie eine reguläre WordPressseite.

Bei Binärdateien (z.B. Bilder) muss vom Plugin eine Besonderheit berücksichtigt
werden:
Hier darf nicht das [HTML-]Wordpresslayout (Kopf, Menü etc) ausgegeben werden,
sondern direkt und ausschließlich der Inhalt der Datei, zudem muss noch ein
spezieller HTTP Header übergeben werden, welcher den Dateityp (als MIME Format)
bestimmt.
So wird zum Beispiel bei

    https://server/test/extern/images/baz.jpg

mit `extern=images/baz.jpg` anhand der Dateiendung erkannt, dass hier eine 
Binärdatei direkt ausgegeben werden muss.
Die erlaubten Formate (anhand der Dateiendung) werden in `$i4include_ext_bin`
(zusammen mit dem MIME Format) spezifiziert.

Außerdem demonstriert das letzte Beispiel, dass auch Unterordner unterstützt
werden.


Anwendung
---------

Der Shortcode hat den Aufbau

    [i4include PARAMETER]PFAD[/i4include]

wobei `PFAD` entweder ein lokaler Dateipfad (sofern dieser relativ ist, 
wird von `$i4include_base_path` ausgegangen) oder eine Webressource (`https://...`)
ist.
Der Pfad muss dem in `$i4include_allowed_path` definierten regulären Ausdruck
entsprechend.


Die boolschen `PARAMETER` sind optional, folgende sind definiert:

 * `dyanmic` erlaubt das Einbinden weiterer Dokumente (`.htm`, `.html`, ...) und
   Binärdateien (derzeit Bilddateien `.png`, `.gif`, `.svg`, `.jpg`/`.jpeg`)
   welche im selben Verzeichnis der angegebenen Zieldatei liegt (oder unterhalb).
   Standardmäßig ist diese Option deaktiviert (`false`)
 * `shortcodes` lässt WordPress Shortcodes im Zieldokument interpretieren --
   rekursive Inkludes des selben Pfades sind jedoch nicht erlaubt.
   (Standard: `false`)
 * `showerrors` zeigt Fehler auf der WordPress-Webseite an (Standard: `false`)


Voraussetzung
-------------

Dieses Plugin macht sich die Eigenschaften des bei WordPress üblichen
Umschreiben der URLs zu nutze -- in der WordPress Administrationsoberfläche
auch "Permalinks" genannt (unter Menüpunkt "Einstellungen").
Allerdings muss in der Konfiguration darauf geachtet werden, dass hier
kein abschließender Schrägstrich verwendet wird -- folgende Konfiguration
wäre z.B. valid:

    /%category%/%postname%


Außerdem muss beim Shortcode immer der volle Pfad zur Datei angegeben werden,
nur der Ordner ist nicht ausreichend!


Limitierung
-----------

 * Die Verknüpfungen in der Zieldatei müssen im `dynamic`-Modus relativ sein
 * Es dürfen nicht mehrere `dynamic` includes mit unterschiedlichen Pfaden auf
   der selben Seite vorkommen (aber mehrere normale includes und maximal ein
   dynamischer sind okay).
 * Verweise auf Ordner (ohne Dateinamen) sind nicht zulässig
 * Einbinden von externen Stylesheets und JavaScript sollte vermieden werden,
   denn das kann zu Problemen führen -- und zerstört das zu vereinheitlichende 
   Look & Feel der neuen Seite.


Verwendung / Entwicklung
------------------------

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

    /%category%/%postname%

angeben & *Änderungen speichern* (je nach Berechtigungen muss nun noch
`example-wordpress/.htaccess` angepasst werden, wird aber im Interface angezeigt --
alternativ die Schreibberechtigungen mit `chmod a+w example-wordpress/.htaccess`
setzen und erneut speichern).

Nun im [Adminmenü auf Seiten](http://localhost:8000/wp-admin/edit.php?post_type=page)
wechseln und entweder eine neue Seite erstellen oder die *Beispiel-Seite* bearbeiten,
dort dann einen Shortcode einfügen, z.B.

    [i4include]/var/www/data/foo.html[/i4include]
    [i4include dynamic="true"]bar.html[/i4include]
    [i4include dynamic="true" shortcodes="true" showerrors="true"]error.html[/i4include]
    [i4include dynamic="true"]https://www4.cs.fau.de/Lehre/WS21/V_BS/Uebungen/aufgabe0/a0.shtml[/i4include]

Außerdem kann das [RRZE Elements Plugin von GitHub](https://github.com/RRZE-Webteam/rrze-elements)
(via [*Code* / *Download ZIP*](https://github.com/RRZE-Webteam/rrze-elements/archive/refs/heads/master.zip)
heruntergeladen und im [WordPress Admininterface](http://localhost:8000/wp-admin/plugins.php)
installiert und aktiviert werden, damit das Beispiel

    [i4include  shortcodes="true"]shortcode.html[/i4include]

korrekt dargestellt wird.
