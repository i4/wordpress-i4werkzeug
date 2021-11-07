Werkzeug `i4include`
====================

Dieses Plugin ermöglicht das Einbinden von HTML-Inhalten (entweder einzeln
Dateien oder einen ganzen Verzeichnisbaum) in WordPress-Seiten aus anderen
Ressourcen (Webserver, Dateisystem) -- ganz ohne `iframe`s!


**Beispiel:** Durch den Shortcode in der WordPress-Seite 'Test'

    [i4include]/proj.stand/i4wp/extern/foo1.html[/i4include]

wird der Inhalt der Datei `foo.html` einfach in der Ausgabe auf

    https://server/test

inkludiert.
Dabei wird immer der zum Ausgabezeitpunkt jeweils aktuelle Inhalt angezeigt,
d.h. Änderungen an der Datei `foo.html` sind sofort auf der Webseite ersichtlich.
Die erlaubten Formate zum Einbinden werden im Array `EXT_EMBED`
anhand der erlaubten Dateieendung angegeben.

**Achtung:** Per `i4include` eingebundene Dateien unterliegen im Normalfall
_nicht_ eventuell vorhandenen Zugriffskontrollregeln am Webserver, die den
Zugriff auf einzelne Dateien oder Unterverzeichnisse verbieten. Insbesondere
bei Verwendung der dynamischen Einbettung (siehe weiter unten) ist der Zugriff
auf alle Dateien mit einer in `EXT_EMBED` aufgeführten Dateiendung
möglich.

Neben dem statischen Einbetten des Inhalts einer konkreten Datei beherrscht
dieses Plugin jedoch noch die Möglichkeit, dynamisch Dateien aus einen ganzen
Verzeichnisbaum auszuliefern, d.h. (relative) Verlinkungen in diesen Datei
werden auf der Webseite unter Veibehaltung des WordPress-Layouts unterstützt.

Nehmen wir mal an, dass im Verzeichnis `/proj.stand/i4wp/extern/` neben `bar.html`
auch noch die Dateien `fubar.html` sowie  ein Unterorderner `images` u.a. mit der
Bilddatei `baz.jpg` liegen und `bar.html` sowohl einen Link
`<a href="fubar.html">Fubar</a>` als auch eine Grafik `<img src="images/baz.jpg">`
beinhaltet.

Wird nun auf der Wordpress-Seite 'Test' beim Einbetten der Parameter `dynamic`
auf wahr gestellt

    [i4include dynamic="true"]/proj.stand/i4wp/extern/bar.html[/i4include]

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
Inhalt von `fubar.html` ein -- d.h. das WordPress-Layout (Kopf und Fuß) bleibt
bestehen, es sieht aus wie eine reguläre WordPress-Seite.

Bei Binärdateien (z.B. Bilder) muss vom Plugin eine Besonderheit berücksichtigt
werden:
Hier darf nicht das [HTML-]Wordpress-Layout (Kopf, Menü etc) ausgegeben werden,
sondern direkt und ausschließlich der Inhalt der Datei, zudem muss noch ein
spezieller HTTP Header übergeben werden, welcher den Dateityp (als MIME Format)
bestimmt.
So wird zum Beispiel bei

    https://server/test/extern/images/baz.jpg

mit `extern=images/baz.jpg` anhand der Dateiendung erkannt, dass hier eine 
Binärdatei direkt ausgegeben werden muss.
Die erlaubten Formate (anhand der Dateiendung) werden in `EXT_BINARY`
(zusammen mit dem MIME Format) spezifiziert.

Außerdem demonstriert das letzte Beispiel, dass auch Unterordner unterstützt
werden.


Anwendung
---------

Der Shortcode hat den Aufbau

    [i4include PARAMETER]PFAD[/i4include]

wobei `PFAD` entweder ein lokaler Dateipfad (sofern dieser relativ ist, 
wird von `FILESYSTEM_BASE` ausgegangen) oder eine Webressource (`https://...`)
ist.
Der Pfad muss dem in `ALLOWED_PATH_REGEX` definierten regulären Ausdruck
entsprechend.


Die boolschen `PARAMETER` sind optional, folgende sind definiert:

 * `course` Angabe einer Lehrveranstaltung (Kurzform, welche auch dem Link
   entspricht, z.B. `bs` für Betriebssysteme).
   Es wird nicht geprüft ob die Veranstaltung oder der dazugehörige Pfad auch
   tatsächlich (bereits) existiert.
 * `semester` explizite Angabe des Semesters (z.B. `WS21`) oder `current` für
   das bei Webseitbetrachtung laufende Semester.
   Wird dieser Angabe weggelassen, so wird versucht aus der Seitenhierarchie das
   aktuelle Semester zu bekommen (eine Unterseite vom Lehre / Sommmersemester 2022
   verweist automatisch auf Kurse des Semester `ss22`), andernfalls wird auf das
   aktuelle Semester umgeschwenkt (z.B. bei einer Unterseite von Forschung).
 * `dynamic` erlaubt das Einbinden weiterer Dokumente (`.htm`, `.html`, ...) und
   Binärdateien (derzeit Bilddateien `.png`, `.gif`, `.svg`, `.jpg`/`.jpeg`)
   welche im selben Verzeichnis der angegebenen Zieldatei liegt (oder unterhalb).
   Standardmäßig ist diese Option deaktiviert (`false`)
 * `shortcodes` lässt WordPress Shortcodes im Zieldokument interpretieren --
   rekursive Inkludes des selben Pfades sind jedoch nicht erlaubt.
   (Standard: `false`)
 * `showerrors` zeigt hilfreiche Fehler auf der WordPress-Webseite an (Standard: `false`)
   Dies kann für die Fehlersuche bei der Seitenerstellung genutzt werden,
   ohne den Parameter wird lediglich Einbettung fehlgeschlagen bei einem Fehler
   angezeigt (die Details werden dann im Webserver geschrieben).


Voraussetzung
-------------

Dieses Plugin macht sich die Eigenschaften des bei WordPress üblichen
Umschreiben der URLs zu nutze -- in der WordPress Administrationsoberfläche
auch "Permalinks" genannt (unter Menüpunkt "Einstellungen").
Allerdings muss in der Konfiguration darauf geachtet werden, dass hier
kein abschließender Schrägstrich verwendet wird -- folgende Konfiguration
wäre z.B. valid:

     /%year%/%monthnum%/%day%/%postname%


Außerdem muss beim Shortcode immer der volle Pfad zur Datei angegeben werden,
nur der Ordner ist nicht ausreichend!

Damit größere Binärinhalte nicht über WordPress/PHP, sondern direkt über den
Webserver ausgeliefert werden können, sollte die Webserverkonfiguration das
Verzeichnis `FILESYSTEM_BASE` per Web zugreifbar machen & in der Variable
`URL_BASE` den notwendige URL Prefix definieren. 

*Beispiel:* Falls das in `FILESYSTEM_BASE` definierte Verzeichnis
`/proj.stand/i4wp/extern` auch via `https://example.com/extern` erreichbar ist,
muss `const URL_BASE = "/extern";` gesetzt werden.


Limitierung
-----------

 * Die Verknüpfungen (`<a href="...">`) in der Zieldatei müssen im `dynamic`-Modus
   relativ sein und müssen innerhalb des Verzeichnisses der Pfaddatei sein,
   damit die Zielseiten auch eingebettet werden.
   Sonst kommt wahrscheinlich eine Fehlerseite!
 * Es dürfen nicht mehrere `dynamic` includes mit unterschiedlichen Pfaden auf
   der selben Seite vorkommen (aber mehrere normale includes und maximal ein
   dynamischer sind okay).
 * Verweise auf Ordner (ohne Dateinamen) sind nicht zulässig
 * Die eingebundenen Inhalte sind nicht über die Suche zu finden, deshalb
   sollte der Mechanismus mit Maß eingesetzt werden.
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

    [i4include]/proj.stand/i4wp/extern/foo.html[/i4include]
    [i4include dynamic="true"]bar.html[/i4include]
    [i4include dynamic="true" shortcodes="true" showerrors="true"]error.html[/i4include]
    [i4include dynamic="true"]https://www4.cs.fau.de/Lehre/WS21/V_BS/Uebungen/aufgabe0/a0.shtml[/i4include]

Außerdem kann das [RRZE Elements Plugin von GitHub](https://github.com/RRZE-Webteam/rrze-elements)
(via [*Code* / *Download ZIP*](https://github.com/RRZE-Webteam/rrze-elements/archive/refs/heads/master.zip)
heruntergeladen und im [WordPress Admininterface](http://localhost:8000/wp-admin/plugins.php)
installiert und aktiviert werden, damit das Beispiel

    [i4include shortcodes="true"]shortcode.html[/i4include]

korrekt dargestellt wird.
