# In fünf Schritten zum passenden Dateinamen

[English](QUICKSTART.md)

GlotPress benennt Downloads nach seiner Projektstruktur. Dieser Name passt nicht immer zur späteren Verwendung der Übersetzung. GP Project Export Name setzt den Downloadnamen aus Projektname, Sprachcode und Sprachvariante zusammen. So entfällt häufig das Umbenennen von Hand.

### Beispiel: eine Plugin-Übersetzung für WordPress

- Typischer GlotPress-Download: `my-plugin-stable-de.po`
- Mit einem hier festgelegten Projektnamen: `my-plugin-de_DE.po`

Beispiel für das Projekt my-plugin/stable mit my-plugin als festgelegtem Namen und dem empfohlenen Namensmuster. Die Sprache stammt aus der exportierten Übersetzung, nicht aus der Sprache des WordPress-Benutzerkontos.

1. **Mit dem empfohlenen Namensmuster starten**

   Bei aktivem GlotPress verwendet das Plugin bereits den letzten Teil des Projektpfads und die exportierte Sprache. Empfohlen: Projekt und Sprache beibehalten, sofern kein anderer Aufbau benötigt wird. Endet ein Projektpfad auf stable, beginnt auch der Dateiname mit stable, bis in Schritt 2 ein anderer Projektname festgelegt wird.

2. **Bei Bedarf den Projektnamen festlegen**

   Unter Projekte suchen und Dateinamen festlegen nach Projektname oder Pfad suchen. In Projektname im Dateinamen den gewünschten Namen ohne Sprachcode und Dateiendung eingeben, zum Beispiel my-plugin. Diesen Namen vormerken wählen. Für ein WordPress-Plugin eignet sich dessen tatsächliche Textdomain: die Kennung, die das Plugin für seine Übersetzungen verwendet. Bei Unklarheit hilft die Dokumentation des jeweiligen Plugins.

3. **Den Dateinamen vor dem Speichern prüfen**

   In der Ergebniszeile des Projekts Vorschau wählen. Angezeigt werden alle zugehörigen Übersetzungssätze, einschließlich Sprachvarianten. Deutsch verwendet normalerweise de_DE; ein deutscher Satz namens formal erhält automatisch de_DE_formal. Die Vorschau erklärt den Aufbau der Namen, speichert aber nichts und lädt keine Datei herunter.

4. **Die gewählten Regeln speichern**

   Änderungen speichern übernimmt den Entwurf für künftige Downloads dieser Website. Die Vorschau allein aktiviert keine Änderungen. Wird ein weiteres Namensplugin erkannt, unter Welches Plugin bestimmt die Dateinamen? bewusst auswählen, welche Regeln gelten sollen.

5. **Wie gewohnt in GlotPress exportieren**

   Den Übersetzungssatz in GlotPress öffnen und den dortigen Export-Link verwenden. Dort auch das benötigte Format wählen. PO dient meist zum Bearbeiten von Übersetzungen; MO ist ein kompiliertes Format, das WordPress verwendet. Das gewählte Format liefert die Endung .po, .mo oder eine andere Endung. Diese Einstellungsseite lädt keine Übersetzungen herunter.

   Ein passender Dateiname installiert oder aktiviert noch keine Übersetzung. Die Zielanwendung benötigt weiterhin das richtige Dateiformat, den passenden Sprachcode und den richtigen Ablageort.

**Nur bei Bedarf:** Eigene Sprachcodes, andere Namensmuster, Sicherungen und Zurücksetzen sind optional. Meist genügen das empfohlene Muster, ein Projektname und eine Vorschau. Erscheint eine Nummer mit --set-, wären sonst zwei Namen innerhalb des Projekts gleich; die Nummer unterscheidet diese Übersetzungssätze.

