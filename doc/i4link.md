Werkzeug `i4link`
=================

Der Shortcode `i4link` erlaubt bequemes Verlinken von Unterseiten ohne die in
WordPress sonst übliche Verwendung von absoluten URLs. Der Vorteil ist, dass
somit insbesondere Seiten von Lehrveranstaltungen bequem (ohne händische
Anpassung) in neue Semester kopiert werden können.

Verwendung
----------

Der Shortcode kann wie folgt verwendet werden:

    [i4link (optionale Attribute)]Pfad[/i4link]

Die unterstützten Attribute für diesen Shortcode sind:

 * `course` Angabe einer Lehrveranstaltung (Kurzform, welche auch dem Link
   entspricht, z.B. `bs` für Betriebssysteme).
   Es wird nicht geprüft ob die Veranstaltung auch tatsächlich (bereits)
   existiert.
 * `semester` explizite Angabe des Semesters (z.B. `WS21`) oder `current`
   für das bei Webseitbetrachtung laufende Semester.
   Wird dieser Angabe weggelassen, so wird versucht aus der Seitenhierarchie das
   aktuelle Semester zu bekommen (eine Unterseite vom * Lehre / Sommmersemester 2022*
   verweist automatisch auf Kurse dieses Semester), andernfalls wird auf das
   aktuelle Semester umgeschwenkt (z.B. bei einer Unterseite von Forschung).
 * `name` gibt den anzuzeigenden Text des Links an. Ohne dieses Attribut wird
   schlicht der Pfad (oder, falls leer, Link) angezeigt.
 * `extern` ist ein boolsches Attribut, welches, wenn wahr, nicht auf WordPress,
   sondern auf Dateien im NFS (/proj/i4wp/extern/) verweist und sich somit z.B.
   für Folien im PDF-Format eignet.
   Dies ist unkompliziert möglich, da die WordPress-Seiten-Adressen und die
   Pfade im NFS bei Lehrveranstaltungen identisch sind.
 * `full` ist ein boolsches Attribut, welches, wenn wahr, die volle URL
   inklusive Protokoll und Domain erstellt.
 * `raw` ist ein boolsches Attribut, welches, wenn wahr, dafür sorgt, dass nicht
   ein Hyperlink erstellt wird, sondern nur die Zieladresse ausgegeben wird
   (entsprechend wird in diesem Fall das Attribut name ignoriert).

Der Pfad (unabhängig ob absolut oder relativ) wird bei Angabe der Attribute
`semester` und/oder `course` von der Semester- bzw. Lehrveranstaltungsseite
aus interpretiert.
Sofern diese beiden Attribute fehlen, wird ein ein absoluter Pfad (beginnend
mit `/`) als Wurzel verstanden, ein relativer Pfad hingegen von der aktuellen
Seite aus.
