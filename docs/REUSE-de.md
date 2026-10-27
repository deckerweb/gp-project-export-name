# Die Einstiegsanleitung wiederverwenden

[English](REUSE.md)

Die Anleitung mit fünf Schritten wird in den `quickstart`-Bereichen von `content.json` gepflegt. Das Plugin liest diese Bereiche für sein Admin-Modal. Nach Änderungen an der Quelle aktualisiert `python3 tools/build-docs.py` im Repository die übrigen Formate.

- `quickstart.html` / `quickstart-de.html`: eigenständige lokale Anleitungen mit `guide.css`.
- `QUICKSTART.md` / `QUICKSTART-de.md`: Markdown für GitHub oder andere Dokumentation.
- `quickstart-fragment.html` / `quickstart-fragment-de.html`: HTML-Abschnitte zum Einfügen in eine Webseite. `quickstart.css` liefert Zahlenkreise und Layout. Die Stile gelten nur innerhalb von `.ddw-gpe-quick-guide`; Seitenhintergrund und Hauptlayout der Website werden nicht zurückgesetzt.
- `guide.html` / `guide-de.html`: die vollständige Anleitung mit Fragen nach Themen, ebenfalls aus `content.json` erzeugt.

Die Webabschnitte enthalten keine Skripte, Admin-Links, Kontodaten oder Bildabhängigkeiten. Den passenden Sprachabschnitt im Seiteninhalt einfügen. Er beginnt mit einer h2-Überschrift und verwendet h3 für die Schritte. Die nur im Admin sinnvollen Sprunglinks zu Einstellungen sind in den übertragbaren Fassungen bewusst nicht enthalten.

Die gemeinsame Quelle ändern, statt erzeugte Kopien unabhängig zu bearbeiten. Deutsch mit Du- und Sie-Anrede verwendet denselben neutralen Anleitungstext. Die erzeugten Dateien sind vorbereitete Inhalte; ihre Erzeugung veröffentlicht oder aktualisiert keine Website und kein GitHub-Wiki.
