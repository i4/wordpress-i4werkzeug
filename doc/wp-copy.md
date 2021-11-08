Werkzeug `wp-copy.sh`
=====================

Mit `bin/wp-copy.sh` haben wir ein Werkzeug, um einen Seitenbaum einer
Lehrveranstaltung von einem Semester in ein anderes Semester zu kopieren.

Verwendung
----------

Auf der `i4wp` den Befehl

    ./bin/wp-copy.sh ws21 spic ss22

ausführen um SPiC inklusive aller Unterseiten vom Wintersemester 2021/22
in das Sommersemester 2022 zu kopieren:

    WP-CLI 2.5.0
     - Quellsemester ws21 ID: 314
     - Quellseiten spic ID: 602
     - Zielsemester ss22 ID: 317
    Seitenbaum:
      602: spic
      -> 770 (neue Kopie auf Eltern ID 317)
        656: evaluation
        -> 771 (neue Kopie auf Eltern ID 770)
        652: pruefung
        -> 772 (neue Kopie auf Eltern ID 770)
        618: uebung
        -> 773 (neue Kopie auf Eltern ID 770)
          627: spicboard
          -> 774 (neue Kopie auf Eltern ID 773)
            650: projekte
            -> 775 (neue Kopie auf Eltern ID 774)
            646: anleitung
            -> 776 (neue Kopie auf Eltern ID 774)
            644: faq
            -> 777 (neue Kopie auf Eltern ID 774)
            642: spicsim
            -> 778 (neue Kopie auf Eltern ID 774)
            640: spic-ide
            -> 779 (neue Kopie auf Eltern ID 774)
            638: libapi
            -> 780 (neue Kopie auf Eltern ID 774)
            635: zuhause
            -> 781 (neue Kopie auf Eltern ID 774)
            633: cip
            -> 782 (neue Kopie auf Eltern ID 774)
        607: vorlesung
        -> 783 (neue Kopie auf Eltern ID 770)

Wird das Ziel (`ss22`) weggelassen, so wird nur der Verzeichnisbaum angezeigt.
