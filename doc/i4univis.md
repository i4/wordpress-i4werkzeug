Werkzeug `i4univis`
===================

Wir holen viele Informationen aus [UnivIS](https://univis.fau.de/), aber leider
fehlt dem [RRZE-UnivIS-Plugin](https://www.wordpress.rrze.fau.de/plugins/fau-und-rrze-plugins/rrze-univis/)
die für uns notwendige Flexibilität, weshalb wir mit dem `i4univis`-Shortcode
unsere eigene Zugriffsmöglichkeit haben.


Verwendung
----------

Der Shortcode erlaubt eine direkte Angabe aller Parameter der
[UnivIS PRG Schnittstelle](http://www.config.de/cgi-bin/prg-wizard.pl):

    [i4univis (PRG Attribute)]optionale Überschrift[/i4univis]

Es ist somit sehr einfach die univis-Tags auf der alten Webseite in das neue
Plugin zu überführen:

Aus

    <univis>
      search thesis advisor="Bernhard Heinloth" status="finished"
      show compact sort=date lang=en codeset=utf8
    </univis>

wird

    [i4univis search=“thesis“ advisor=“Bernhard Heinloth“ status=“finished“ show=“compact“ sort=“date“ lang=“en“ codeset=“utf8″][/i4univis]


Neben den von der PRG-Schnittstelle unterstützten Attributen gibt es im Plugin folgende Erweiterungen:

 * `codeset` wird bei Fehlen des Attributs automatisch auf `utf8` gesetzt.
 * `sem` kann unter Angabe des Wertes `auto` anhand der Seitenhierarchie
   automatisch das Semester wählen. Dies ist für die Lehrveranstaltungen
   (im Zusammenspiel mit dem semesterweisen Kopieren der WordPress-Seiten)
   nützlich.
