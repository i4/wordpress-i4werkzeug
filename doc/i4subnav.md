Werkzeug `i4subnav`
=====================

Auf der alten Website konnte das Sidebar-Menü explizit durch ein
SSI-Include händisch gepflegt werden. Dies ermöglichte sowohl
externe als auch seitenlokale Links im Lehrveranstaltungsmenü.
Diese Funktionalität wird nun durch `i4subnav` abgefackelt.
(Es handelt sich um das Menü auf der linken Seite des Seitentemplates
"Inhaltsseite mit Navi")

Verwendung
----------

Der Shortcode kann wie folgt verwendet werden:

	[i4nav (optionale Attribute)](Optionaler Text)[/i4nav]

Die unterstützten Attribute für diesen Shortcode sind:

 * `name`: String, zugehöriger im Menü angezeigtes Label/Bezeichner
 * `order`: Integer, bestimmt die Position des Eintrags im Menü.
   Interagiert passend mit den Einträgen "Reihenfolge" regulärer Seiten
   und besitzt dieselbe Semantik (kleinere Zahl = frühere Position,
   negative Zahlen möglich, Standardwert: 0. Einträge gleicher `order`
   werden gemäß Auftrittsreihenfolge in der jeweiligen Seite angezeigt).
 * `href`: String, Linkziel, für externe Links. Wechselseitiger Ausschluss mit `anchor`.
 * `anchor`: String, seitenlokaler Anker, bestimmt den Wert des `id` Attributs
   des generierten Ankers. Wechselseitiger Ausschluss mit `href`.

Für als seitenlokaler Anker genutzte Shortcodes sind alle Attribute optional,
da `name` und `id` hier bei Bedarf aus dem Text zwischen den Shortcode-Tags
generiert werden können. Explizite Attributwerte bieten hier jedoch mehr
Kontrolle. Für generische Einträge mittels `href` hingegen muss zwingend
ein Menübezeichner mittels `name`-Attribut gesetzt werden.

Beispiele:

	<h2>[i4nav anchor="aufgaben" name="Aufgaben"]Übungsaufgaben[/i4nav]</h2>
	[i4nav href="http://example.org" name="Example.org"][/i4nav]

Führt in der gerenderten Seite zu:

	<h2><a id="aufgaben">Übungsaufgaben</a></h2>

Sowie den Menüeinträgen "Aufgaben" und "Example.org".

Einschränkungen
---------------

Da `i4nav`-Shortcodes zum Speicherzeitpunkt einer Seite ausgewertet werden,
können diese Einträge weder aus inkludierten Dateien übernommen noch durch
andere Shortcodes (wie etwa die Funktionalitäten in `i4list` oder
`hidden-text`) modifiziert oder verborgen werden.

Aktuell ist das Plugin explizit nur im Lehre-Unterbaum aktiv und versteckt dort
ferner alle Einträge oberhalb einer Lehrveranstaltung.
