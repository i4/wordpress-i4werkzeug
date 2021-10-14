WordPress Plugin `i4include`
===========================

Dieses Plugin ermöglicht das Einbinden einer Datei.

Beispiel: Durch den Shortcode in der Wordpressseite 'Test'

    [i4include]/var/www/data/foo1.html[/i4include]

wird der Inhalt der Datei `foo1.html` einfach in der Ausgabe auf

    https://server/test

inkludiert.
Dabei wird immer der zum Ausgabezeitpunkt jeweils aktuelle Inhalt angezeigt,
d.h. Änderungen an der Datei `foo1.html` sind sofort auf der Webseite ersichtlich.
Die erlaubten Formate zum Einbinden werden im  Array `$i4include_ext_html`
anhand der erlaubten Dateieendung angegeben.


Neben dem statischen Einbetten des Inhalts einer konkreten Datei beherrscht
dieses Plugin jedoch noch die Möglichkeit, dynamisch Dateien aus einen ganzen
Verzeichnisbaum auszuliefern, d.h. (relative) Verlinkungen in diesen Datei
werden auf der Webseite unter Veibehaltung des WordPresslayouts unterstützt.

Nehmen wir mal an, dass im Verzeichnis `/var/www/data/` noch die Dateien `bar.html`
sowie  ein Unterorderner `images` mit der Bilddatei `baz.png` liegen
und `foo2.html` sowohl einen Link `<a href="bar.html">Bar</a>` als auch eine
Grafik `<img src="images/baz.png">` beinhaltet.

Wird nun auf der Wordpressseite 'Test' beim Einbetten der Parameter `dynamic` auf wahr gestellt

    [i4include dynamic="true"]/var/www/data/foo2.html[/i4include]

so wird beim Öffnen der Webseiten-URL

    https://server/test

automatisch auf

    https://server/test/extern/foo2.html

weitergeleitet. Das `extern` in der URL ist dabei ein sog. Endpoint,
wodurch die in der URL nachfolgenden Inhalte im gleichnamigen Parameter gespeichert werden,
also im Beispiel quasi `extern=foo2.html`.
Durch einen Endpoint werden dynamische Inhalte auf der selben Wordpressseite ermöglicht,
bei uns wird dieser Trick verwendet, damit nun der Link auf `bar.html` durch den Browser zu

    https://server/test/extern/bar.html

umgewandelt wird (`extern=bar.html`), das Plugin bettet nun auf der gleichen WordPressseite
an der Position des Shortcodes `[i4include...]` den Inhalt von `bar.html` ein --
d.h. das WordPresslayout (Kopf und Fuß) bleibt bestehen, es sieht aus wie eine reguläre WordPressseite.

Bei Binärdateien (z.B. Bilder) muss vom Plugin eine Besonderheit berücksichtigt werden:
Hier darf nicht das [HTML-]Wordpresslayout (Kopf, Menü etc) ausgegeben werden,
sondern direkt und ausschließlich der Inhalt der Datei, zudem muss noch ein
spezieller HTTP Header übergeben werden, welcher den Dateityp (als MIME Format) bestimmt.
So wird zum Beispiel bei

    https://server/test/extern/images/baz.png

mit `extern=images/baz.png` anhand der Dateiendung erkannt, dass hier eine 
Binärdatei direkt ausgegeben werden muss.
Die erlaubten Formate (anhand der Dateiendung) werden in `$i4include_ext_bin`
(zusammen mit dem MIME Format) spezifiziert.

Außerdem demonstriert das letzte Beispiel, dass auch Unterordner unterstützt werden.


Besonderheiten:
Dieses Plugin macht sich die Eigenschaften des bei WordPress üblichen
Umschreiben der URLs zu nutze -- in der WordPress Administrationsoberfläche
auch "Permalinks" genannt (unter Menüpunkt "Einstellungen").
Allerdings muss in der Konfiguration darauf geachtet werden, dass hier
kein abschließender Schrägstrich verwendet wird -- folgende Konfiguration
wäre z.B. valid:

    /%category%/%postname%


Außerdem muss beim Shortcode immer der volle Pfad zur Datei angegeben werden,
nur der Ordner ist (auch nicht mit der Option `dir="true"`) nicht ausreichend!
