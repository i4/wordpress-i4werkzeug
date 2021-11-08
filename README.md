WordPress Plugin `i4werkzeug`
=============================

Da WordPress nicht ganz zu dem typischen Arbeitsablauf eines Systemlehrstuhls
passt, haben wir nun ein eigenes Plugin, dass den [neuen Webauftritt](https://sys.cs.fau.de/)
an unsere Bedürfnisse anpasst, insbesondere:

 * Dateien (z.B. PDF-Folien) sollen über NFS bereitgestellt werden, da diese
   häufig mit Skriptunterstützung generiert und platziert werden — ein
   händischer Upload in der WordPress-Adminweboberfläche soll verhindert werden.
 * Zusätzlich müssen häufiger Listen mit Verweise auf solche Dateien angepasst
   werden, diese sollen ebenfalls via NFS eingebunden werden können.
 * Lehrveranstaltungswebseiten eines Semesters sollen persistent bleiben,
   d.h. auch nach dem Ablauf des Semesters noch abrufbar sein.
   Es wird jedes neue Semester eine Kopie der Seiten erstellt,
   die Verlinkungen sollen dabei automatisch angepasst werden.
 * Unter Umständen müssen ganze Verzeichnisbäume integriert werden,
   beispielsweise via Doxygen generierte Dokumentationen.
   Das soll auch automatisch vom NFS gehen, alle HTML Dokumente sollen in die
   Webseite integriert werden.

Dieses Plugin stellt sogenannte Shortcodes zur Verfügung, welche insbesondere
mit dem NFS-Pfad `/proj/i4wp/extern` interagieren.

Folgende Shortcodes sind implementiert:

 * [i4semester](doc/i4semester.md) zur dynamischen Anzeige des Semesternamens
 * [i4link](doc/i4link.md) genieriert Verweise aus relativen Pfadangaben
 * [i4include](doc/i4include.md) erlaubt das einbetten von externen Inhalten
 * [i4list](doc/i4list.md) erstellt eine WordPress-typische Folien oder
   Aufgabenübersicht aus einer Vorgabe in einem einfachen und übersichtlichen Format
 * [i4code](doc/i4code.md) für Quelltextbeispiele in Wordpress

Details und Beispiele sind auf der Demowebseite unter
[sys.cs.fau.de/lehre/ss22/demo](https://sys.cs.fau.de/lehre/ss22/demo) zu finden.

Für die Entiwcklung/Erweiterung sollte nicht auf der produktiven WordPress-Instanz
gearbeitet werden, sonderen eine [lokale Instanz (z.B. via Docker)](doc/dev.md)
verwendet werden.

Außerdem gibt es in diesem Repi mit [`bin/wp-copy.sh`](doc/wp-copy.md) ein
kleines Werkzeug um Seitenbäume von Lehrveranstaltungen in ein neues Semester
zu kopieren.
