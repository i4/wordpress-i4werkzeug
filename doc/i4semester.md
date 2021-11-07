Werkzeug `i4semester`
=====================

Auf der alten Webseite war über SSI-Variablen ein Zugriff auf die
Semesterbezeichnungen möglich, diese Funktionalität wird nun durch 
`i4semester` abgefackelt.

Verwendung
----------

Der Shortcode kann wie folgt verwendet werden:

    [i4link (optionale Attribute)]Semester[/i4link]

Die unterstützten Attribute für diesen Shortcode sind:

 * `delta` gibt die Jahre (auch halbe Jahre möglich) an, um vom gegebenen
   Semester abzuweichen — nützlich um z.B. auf das kommende Semester zu
   verweisen (durch Angabe von `+0.5`)
 * `format` gibt an, wie der Semestername ausgegeben werden soll.
   Mögliche Parameter für das Attribut sind:
    * `long` für komplett ausgeschrieben (**Wintersemester 2021/22**)
    * `short` für die (unverwerfliche) Kurzform (**WiSe 2021/22**)
    * `abbr` für die ganz kurze Form (**WS21**)
    * `link` für eine klein geschriebene kurze Form (**ws21**),
      welche so auch in WordPress URLs verwendet werden kann
    * und, standardmäßig (bei keiner oder ungültiger Angabe):
      Die Kurzform mit voller Jahreszahl (**WS 2021/22**)

Als Semesterangabe für den Shortcode kann entweder ein beliebiges der oben 
genannten Formate verwendet werden, oder current (oder ein beliebig anderer Text,
welcher nicht dem Format entspricht), um das derzeit aktuelle Semester
(anhand des Datums) auszugeben.
Wird das Semesterfeld leer gelassen, also kein Text angegeben,
so wird versucht aus der Seitenhierarchie das Semester zu extrahieren,
d.h. eine Lehrveranstaltung unterhalb von `/lehre/ws21/...` wird zu 
**Wintersemester 2021/22** ausgewertet.
