# Kompatibilitätstests

[English](README.md)

Nur in einer entbehrlichen WordPress-Testinstallation mit aktivem GlotPress und GP Project Export Name ausführen. Die Tests erzeugen Projekte und Übersetzungssätze. Vorhandene Namenseinstellungen werden wiederhergestellt; Testinhalte bleiben erhalten. Administrator-ID 1 muss vorhanden sein.

GP Download Name 0.6, GP Use Slug for Downloads 1.0 und GP Bulk Download Translations 1.2 unverändert als inaktive Testabhängigkeiten installieren. Alle drei sind eigenständige GPL-Plugins von Greg Ross; ihr Code ist hier nicht enthalten. Quellen: https://wordpress.org/plugins/gp-download-name/ , https://wordpress.org/plugins/gp-use-slug-for-downloads/ und https://wordpress.org/plugins/gp-bulk-download-translations/ .

```sh
GPE_TEST_SITE=1 wp eval-file wp-content/plugins/gp-project-export-name/tests/integration.php
```

Geprüft werden JSON-Validierung, signierte Änderungsprüfung und zwischenzeitliche Bearbeitung, Rücksetzbereiche, Projektprüfung, Dateinamensvarianten, native Download-Header und unveränderte Inhalte, konkurrierende Namensfilter, externe Sammel-Exporte und Datenbankabfragen. GPE_COMPAT_FIXTURES kann auf ein Verzeichnis mit den drei externen Plugin-Ordnern zeigen.

Der Repository-Workflow Compatibility führt Syntax- und Integrationstests bei Push, Pull Request, manuellem Start und wöchentlich aus. Die Matrix kombiniert WordPress 6.4 und die aktuelle stabile WordPress-Version mit PHP 8.1/8.4 sowie minimalen/aktuellen GlotPress-Versionen. Ein entbehrlicher MySQL-8-Dienst dient als Datenbank. Keine Repository-Zugangsdaten erforderlich; Releases können dadurch nicht veröffentlicht werden. Ein vorbereiteter Workflow belegt keinen erfolgreichen GitHub-Lauf; dafür das tatsächliche Jobprotokoll heranziehen.

Barrierefreiheit und Darstellung erfordern zusätzliche Browsertests. Im installierbaren Release-ZIP sind dieses Verzeichnis und der Workflow nicht enthalten.
