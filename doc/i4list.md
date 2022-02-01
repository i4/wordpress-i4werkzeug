Werkzeug `i4list`
=================

Ein häufiger Anwendungsfall in der Lehre ist das zeitgesteuerte Anzeigen von
Vorlesungs-/Übungsfolien und Aufgaben.
Bisher mussten dafür vor allem große (breite) Tabellen herhalten,
allerdings sollte darauf aufgrund des [responsiven Webdesign](https://de.wikipedia.org/wiki/Responsive_Webdesign)
zugunsten von [Accordions](https://www.wordpress.rrze.fau.de/plugins/fau-und-rrze-plugins/elements/accordion/)
verzichtet werden.

Außerdem wurde je nach Veranstaltung teilweise mittels SSI ein zeitgesteuertes
Anzeigen implementiert. Diese Funktionalität (bzw. genauer: ein Verstecken)
wird über den [i4Hidden-Text Shortcode](i4hiddentext.md) ermöglicht.

Diese Shortcodes sind allerdings verhältnismäßig umständlich zu schreiben (und nicht sehr fehlertolerant):

    [collapsibles]
    [i4hidden-text end="2021-10-17"]
    [collapse title="1. Vorlesung" name="vl-1"]
    Vorlesung am Montag, 18. Oktober 2021
    
    <i>Bitte an 3G Nachweis denken!</i>
    [accordion]
    [accordion-item title="Organisation" name="vl-1-1"]
    <a href="[i4link extern=true]vl1a.pdf[/i4link]">Folie zur Organisation</a>
    [/accordion-item]
    [accordion-item title="Einführung" name="vl-1-2"]
    <a href="[i4link extern=true]vl1b.pdf[/i4link]">Folie zur Einführung</a>
    [/accordion-item]
    [/accordion]
    [/collapse]
    [/i4hidden-text]
    [i4hidden-text end="2021-10-24"]
    [collapse title="2. Vorlesung" name="vl-2"]
    Vorlesung am Montag, 25. Oktober 2021
    ...
    [/collapse]
    [/i4hidden-text]
    [/collapsibles]

Der Shortcode `i4list` schafft hier Abhilfe, in dem er die gleiche Ausgabe
wie folgt erstellen kann:

    [i4list name="Vorlesung" showdate=true uncover=1]
    2021-10-18 1. Vorlesung
    
    *Bitte an 3G Nachweis denken!*
    
    ## Organisation
    [Folie zur Organisation](vl1a.pdf)
    
    ## Einführung
    [Folie zur Einführung](vl1b.pdf)
    
    
    2021-10-25 2. Vorlesung
    ...
    [/i4list]

Die unterstützten Attribute für diesen Shortcode sind:

 * `name` Der Name für die Veranstaltung, wird für internes ID-Feld verwendet.
 * `showdate` ist ein boolsches Attribut, welches, wenn wahr, bei Einträgen mit
   Datumsnotation zuerst den namen gefolgt von einem lesbaren Datum in der Form
   *Wochentag, Tag. Monat Jahr* ausgibt.
 * `uncover` blendet, falls angegben, den Eintrag die angegbenen Anzahl an
   Tagen vor der Veranstaltung ein.
   So bedeutet `uncover=2`, dass der Eintrag 2 Tage vor dem angegebenen Datum
   sichtbar sein soll.

Als Inhalt wird kann eine sehr (sehr) vereinfachte Untermenge von Markdown
verwendet werden:

    [i4list]
    
    # Markdown Codes
    
    [absoluter Link](https://www4.cs.fau.de/Lehre/SS21/V_BST/Evaluation/SS13_bst_v.pdf)
    [relativer Link](../bst/evaluation/SS13_bst_v.pdf)
    
    Ich wollte mal __unterstreichen__, dass das ziemlich **fett** und voll *schräg* ist!
    
    ---
    
    Einkaufsliste:
     * Eier
     * Schmalz
     * Zucker
     * Salz
     * Milch
     * Mehl
     * Safran
    
    ---
    
     1. Foo
     2. Bar
        Baz
     3. usw
    
    # Videos
    Es reicht einfach die URL einzufügen, und schon wird ein Video daraus
    https://www.fau.tv/clip/id/36104
    
    [/i4list]

