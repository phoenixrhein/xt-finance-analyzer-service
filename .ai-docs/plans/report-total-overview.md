# Implementierungsplan: Gesamtübersicht in den Reports

**Status:** Abgeschlossen
**Zielgruppe:** Copilot (Implementierung)  
**Sprache:** Deutsch (technische Bezeichner unverändert)

---

## 1. Ausgangssituation und Ziel

Beim Ausführen eines Reports (`fin:report`) kann zwischen einem jährlichen und einem monatlichen Report gewählt werden. Anschließend wird ein Zeitraum festgelegt. Die bestehende Report-Ausgabe zeigt für den ausgewählten Zeitraum die jeweiligen Einnahmen und Ausgaben (bei einem monatlichen Report die Werte der einzelnen Monate).

Am Ende der bestehenden Report-Ausgabe soll zusätzlich eine **Gesamtübersicht für den gesamten ausgewählten Zeitraum** erscheinen. Diese soll drei Werte enthalten:

- Gesamteinnahmen
- Gesamtausgaben
- Saldo (Gesamteinnahmen minus Gesamtausgaben)

Die konkrete Darstellung und Formatierung obliegt der Implementierung. Die bestehende Report-Ausgabe soll ansonsten unverändert bleiben. Die Erweiterung gilt sowohl für monatliche als auch für jährliche Reports.

---

## 2. Bestätigte Erkenntnisse (keine neuen Annahmen)

### Architektur und Datenfluss

Der Report-Command `app/Console/Commands/Report/Report.php` orchestriert den Ablauf:

1. `ReportConfiguratorWizard::runWizard()` → liefert Array von Perioden (`[{from, to}, ...]`) sowie den Report-Typ (monatlich/jährlich)
2. `ReportDataProcessor::process($account, $periods, $considerExclusionIbans)` → liefert `Collection<PeriodReportData>`
3. `ReportPresenter::render($reportData, $timespanType)` → gibt die Konsolenausgabe aus

### ReportDataProcessor (berechnende Schicht)

- `process()` iteriert über alle Perioden und ruft für jede `processPeriod()` auf.
- `processPeriod()` berechnet pro Periode bereits:
  - `$totalIncome = $incomingTransactions->sum('amount')`
  - `$totalOutgoing = abs($outgoingTransactions->sum('amount'))`
  - Balance = `$totalIncome - $totalOutgoing`
- Diese Werte werden in das DTO `PeriodReportData` geschrieben.

### PeriodReportData (DTO)

`app/Dto/Report/PeriodReportData.php` enthält pro Periode bereits: `totalIncome`, `totalOutgoing`, `balance` (alle als `float`). Es gibt **noch keine** aggregierten Werte über alle Perioden hinweg.

### ReportPresenter (darstellende Schicht)

- `render()` rendert nacheinander: Einkommenstabelle, Ausgabenstabelle, optionale Sparbuchtabelle, Zusammenfassungstabelle (`renderSummary()`).
- `renderSummary()` rendert bereits eine Tabelle mit **einer Zeile pro Periode** (Einnahmen, Ausgaben, Saldo als Spalten). Dies ist die *pro-Periode*-Zusammenfassung, nicht die *gesamt*-Zusammenfassung über alle Perioden.
- `renderCategoryTable()` rendert Kategorie-Tabellen mit einer Total-Row (`label_total` + Titel) pro Tabelle.
- `ReportTableMetrics::initialize()` berechnet Spaltenbreiten und berücksichtigt eine feste Liste `additionalLabels` für die Kategorie-Spaltenbreite. Neue Labels müssen dort ergänzt werden, wenn sie länger sind als bestehende.

### Sprachdateien und Tests

- Es gibt nur eine Sprachdatei: `lang/de/cli.php` (keine englischen Dateien). Die relevanten Labels stehen unter `cli.report.presentation.*`.
- Es gibt nur einen Unit-Test: `tests/Unit/Services/Console/Report/ReportDataProcessorTest.php` (testet `calculateSplitAmounts` und `groupTransactionsByCategory`). Es gibt **keine** Tests für `ReportPresenter`.

---

## 3. Annahmen

1. **Platzierung:** Die Gesamtübersicht wird mit einer Leerzeile Abstand als zusätzliche, separate Tabelle **nach** der bestehenden Zusammenfassung (`renderSummary()`) angezeigt. Die bestehende Ausgabe bleibt unverändert.
2. **Berechnung:** Gesamteinnahmen = Summe aller `totalIncome` über alle Perioden; Gesamtausgaben = Summe aller `totalOutgoing`; Saldo = Gesamteinnahmen − Gesamtausgaben.
3. **Sparbuch:** Excluded-IBAN-Transaktionen (Sparbuch) werden nicht in die Gesamtübersicht einbezogen, da es sich um reine Einnahmen/Ausgaben handelt.
4. **Gültigkeit:** Die Gesamtübersicht gilt für monatliche und jährliche Reports gleichermaßen, da die Aggregation über die Perioden-Collection erfolgt, unabhängig vom Report-Typ.
5. **Leerer Report:** Wenn `$reportData->isEmpty()` ist, wird bereits früh in `render()` zurückgegeben ("Keine Daten"). Die Gesamtübersicht erscheint dann nicht.
6. **Darstellung:** Die Gesamtübersicht verwendet eine einzelne Spalte „GESAMT“; Periodenüberschriften werden dort nicht angezeigt und jeder Betrag erscheint genau einmal.

---

## 4. Offene Fragen (nicht blockierend)

| Frage | Empfohlene Entscheidung | Begründung |
|-------|------------------------|------------|
| Soll die Gesamtübersicht eine eigene Tabelle sein oder Teil der bestehenden `renderSummary()`? | Eigene Tabelle nach `renderSummary()` | Trennt pro-Periode-Zusammenfassung von gesamt-Zusammenfassung; bestehende Ausgabe bleibt unverändert. |
| Welche Labels sollen verwendet werden? | `title_total_overview` / `label_total_income` / `label_total_expenses` / `label_total_balance` | Konsistent mit bestehenden `label_*`-Konventionen; eindeutig unterscheidbar von den pro-Periode-Werten. |

---

## 5. Betroffene Komponenten, Klassen und Methoden

| Komponente | Datei | Rolle bei der Änderung |
|------------|-------|------------------------|
| DTO | `app/Dto/Report/PeriodReportData.php` | Keine Änderung nötig (bereits alle benötigten Felder vorhanden). |
| Datenprozessor | `app/Services/Console/Report/ReportDataProcessor.php` | Keine Änderung nötig (berechnet bereits pro Periode die benötigten Werte). |
| Präsentator | `app/Services/Console/Report/ReportPresenter.php` | Neue Methode `renderTotalOverview()` + Aufruf in `render()`. |
| Tabellenrenderer | `app/Services/Console/Report/ReportTableRenderer.php` | Rendering-Methoden für Tabellen mit einer Betragsspalte ergänzen. |
| Tabellenmetriken | `app/Services/Console/Report/ReportTableMetrics.php` | Neue Labels in `additionalLabels` ergänzen (für korrekte Spaltenbreite). |
| Sprachdatei | `lang/de/cli.php` | Neue Labels unter `cli.report.presentation.*` ergänzen. |
| Command | `app/Console/Commands/Report/Report.php` | Keine Änderung nötig (übergebenes `$reportData` reicht aus). |

---

## 6. Notwendige Änderungen (im Detail)

### 6.1 Sprachdatei (`lang/de/cli.php`)

Unter `cli.report.presentation` folgende Labels ergänzen (alphabetisch/logisch gruppiert nach den bestehenden):

```php
'title_total_overview' => 'GESAMTÜBERSICHT',
'label_total_income'   => 'Gesamteinnahmen',
'label_total_expenses' => 'Gesamtausgaben',
'label_total_balance'  => 'Gesamtsaldo',
```

Begründung: Die bestehenden Labels folgen dem Muster `label_*` und `title_*`. Die neuen Labels unterscheiden sich durch das Präfix `total_` von den pro-Periode-Labels (`label_income`, `label_expenses`, `label_balance`).

### 6.2 Tabellenmetriken (`ReportTableMetrics.php`)

In `initialize()` die Liste `$additionalLabels` um die neuen Labels erweitern, damit die Kategorie-Spaltenbreite berechnet wird:

```php
__('cli.report.presentation.label_total_income'),
__('cli.report.presentation.label_total_expenses'),
__('cli.report.presentation.label_total_balance'),
__('cli.report.presentation.title_total_overview'),
```

### 6.3 Präsentator (`ReportPresenter.php`)

Nach der bestehenden Zusammenfassung wird mit einer Leerzeile Abstand `renderTotalOverview()` aufgerufen. Die Methode summiert `totalIncome` und `totalOutgoing` über alle Perioden und berechnet daraus den Gesamtsaldo.

Die Gesamtübersicht wird als kompakte Tabelle mit einer einzigen Betragsspalte „GESAMT“ gerendert. Dafür ergänzt `ReportTableRenderer` passende Methoden für Header, Trennlinien und Datenzeilen mit einer Betragsspalte. Die Periodenheader der bisherigen Tabellen erscheinen in dieser Tabelle nicht. Der Gesamtsaldo wird analog zur bestehenden Zusammenfassung hervorgehoben. Die Beträge verwenden weiterhin `CurrencyFormatterTrait::formatAmount()`.

---

## 7. Zusammenspiel der Änderungen

```
ReportCommand.process()
  └─ ReportDataProcessor.process()  → Collection<PeriodReportData>  (unverändert, liefert bereits totalIncome/totalOutgoing/balance pro Periode)
        └─ ReportPresenter.render($reportData)
              ├─ renderCategoryTable()  (Einnahmen, unverändert)
              ├─ renderCategoryTable()  (Ausgaben, unverändert)
              ├─ renderSavingsTable()   (optional, unverändert)
              ├─ renderSummary()        (pro-Periode-Zusammenfassung, unverändert)
              └─ renderTotalOverview()  ← NEU: aggregiert über alle Perioden, Ausgabe in einer Gesamtspalte
                    ├─ sum(totalIncome)   → Gesamteinnahmen
                    ├─ sum(totalOutgoing) → Gesamtausgaben
                    └─ difference        → Gesamtsaldo
```

Die Änderung ist nachgelagert: Der `ReportDataProcessor` liefert bereits alle benötigten Rohdaten. Nur der `ReportPresenter` muss die Werte aggregieren und ausgeben. Keine Änderungen am Command, am DTO oder am Prozessor notwendig.

---

## 8. Tests

### Bestehende Tests

- `tests/Unit/Services/Console/Report/ReportDataProcessorTest.php` — testet bereits `calculateSplitAmounts` und `groupTransactionsByCategory`. Keine Änderung nötig, da der Prozessor unverändert bleibt.

### Neue Tests (empfohlen)

Da es keine Presenter-Tests gibt, sollte eine neue Testklasse erstellt werden:

**`tests/Unit/Services/Console/Report/ReportPresenterTotalOverviewTest.php`** (oder ähnlicher Name)

Testfälle:
1. **Summe über mehrere Perioden:** Gegebene `Collection` mit 3 Perioden (z. B. Einkommen 1000, 2000, 1500; Ausgaben 400, 600, 800) → Erwartet: Gesamteinnahmen 4500, Gesamtausgaben 1800, Gesamtsaldo 2700, jeweils einmal angezeigt und ohne Periodenheader.
2. **Einzelne Periode:** Eine Periode → Gesamtwerte entsprechen den Einzelwerten.
3. **Leere Collection:** `render()` mit leerer Collection → frühzeitiger Rückgabe („Keine Daten“), keine Gesamtübersicht. (Bereits durch bestehende `render()`-Logik abgedeckt, aber als Test dokumentieren.)

Die Tests sollten die Ausgabe auf den Konsole-Output prüfen oder, falls der Presenter schwer direkt instantiierbar ist, die Berechnungslogik isoliert testen. Da der Presenter `AbstractIOService` erweitert und `CurrencyFormatterTrait` nutzt, könnte ein Test über das `TestCase`-Gerüst mit Mocked Output erfolgen, oder die Aggregationslogik wird als separate private Methode ausgekapselt und direkt getestet.

**Alternativ (einfacher):** Eine Testmethode in der bestehenden `ReportDataProcessorTest`-ähnlichen Struktur, die die Aggregation über eine `Collection<PeriodReportData>` verifiziert. Da `PeriodReportData` ein einfaches DTO ist, lässt sich dies ohne DB testen.

---

## 9. Abnahmekriterien

- [x] Die bestehende Report-Ausgabe (Einnahmen-, Ausgaben-, Sparbuch-, Zusammenfassungstabelle) ist für monatliche und jährliche Reports unverändert.
- [x] Am Ende der Ausgabe erscheint eine neue Tabelle „GESAMTÜBERSICHT“.
- [x] Die Tabelle enthält die drei Zeilen: Gesamteinnahmen, Gesamtausgaben, Gesamtsaldo.
- [x] Gesamtsaldo = Gesamteinnahmen − Gesamtausgaben.
- [x] Die Werte sind über alle Perioden des gewählten Zeitraums summiert.
- [x] Jeder Gesamtbetrag wird in einer einzelnen „GESAMT“-Spalte genau einmal angezeigt.
- [x] Zwischen der bisherigen Zusammenfassung und der Gesamtübersicht befindet sich eine Leerzeile.
- [x] Die Formatierung (Währungsformat, Ausrichtung) ist konsistent mit den bestehenden Tabellen.
- [x] Bestehende Unit-Tests laufen weiterhin grün.
- [x] Neue Tests für die Gesamtübersicht werden hinzugefügt und laufen grün.

---

## 10. Risiken und Hinweise

- **Keine DB-Änderungen:** Die Änderung erfordert keine Migrationen oder Schema-Änderungen.
- **Keine Command-Änderungen:** Die Signatur des `fin:report`-Commands bleibt unverändert.
- **Sprachdatei:** Es existiert nur `lang/de/cli.php`. Sollte das Projekt mehrsprachig werden, müssen die Labels in weitere Sprachdateien übernommen werden.
- **Presenter-Tests fehlen bislang:** Es gibt noch keine Testabdeckung für `ReportPresenter`. Neue Tests sollten hier vorsichtig eingeführt werden, da der Presenter von `AbstractIOService` abhängt.