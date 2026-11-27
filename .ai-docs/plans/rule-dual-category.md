# Entwicklungsplan: Regel mit Einnahmen- und Ausgabenkategorie

**Status:** Abgeschlossen
**Datum:** 2026-11-26  
**Betroffene Anforderung:** Eine Regel kann eine Einnahmen- und/oder Ausgabenkategorie besitzen; mindestens eine ist erforderlich.

---

## 1. Übersicht der Änderungen

| Bereich | Änderung |
|---------|----------|
| Datenbank | Tabelle `action`: nullable Spalte `cashflow_type` hinzufügen (`in`/`out`) – SQL manuell ausgeführt, Schema dokumentiert |
| Modell `Action` | Fillable und String-Cast für `cashflow_type` |
| Modell `Rule` | Beziehung `actions()` von `HasOne` auf `HasMany` ändern |
| `RuleDataManager` | `saveRuleForTarget()` akzeptiert zwei Kategorie-IDs statt einer |
| `RuleAssignerWorkflow` | Interaktive Auswahl beider Kategorien (gemeinsamer Schritt) |
| `RefreshTransactionRuleIndexService` | Cashflow-Filter pro Kategorie statt pro Regel |
| `RuleListService` | `actions.category` lädt nun mehrere Actions |
| `RuleList` Command | Zwei separate Spalten für Einnahmen- und Ausgabenkategorie |
| `ReportDataProcessor` | Verwendet passende Kategorie je nach Betragszeichen der Transaktion |
| `Rule::boot()` | Löschlogik für mehrere Actions anpassen |
| Datenbankänderung | Manuelle SQL-Änderung entsprechend der Projektkonvention |

---

## 2. Detaillierte Arbeitsschritte

### Schritt 1: Datenbankschema erweitern

- Keine Laravel-Migration erstellen; die Datenbank wird im Projekt manuell per SQL gepflegt.
- SQL für die Spalte `cashflow_type` (enum: `in`, `out`, nullable) in Tabelle `action` bereitstellen und vom Anwender ausführen lassen.
- `.ai-docs/databaseSchema.sql` aktualisieren (Dokumentation)

**Abnahmekriterien:** Die SQL-Änderung ist ausgeführt und die Schema-Dokumentation aktuell.

### Schritt 2: Modell `Action` anpassen

- `cashflow_type` zu `$fillable` hinzufügen
- String-Cast auf `cashflow_type` hinzufügen
- Beziehung zu `Category` bleibt bestehen

**Abnahmekriterien:** `Action`-Modell akzeptiert und castet `cashflow_type`.

### Schritt 3: Modell `Rule` anpassen

- Beziehung `actions()` von `HasOne` auf `HasMany` ändern
- Löschlogik in `boot()` anpassen: Mehrere Actions löschen statt einer einzelnen

**Abnahmekriterien:** `$rule->actions` liefert eine Collection statt eines einzelnen Objekts. Löschung entfernt alle Actions.

### Schritt 4: `RuleDataManager` anpassen

- `saveRuleForTarget()` Signatur ändern: Akzeptiert `?int $incomeCategoryId` und `?int $outcomeCategoryId` statt `int $categoryId`
- Validierung: Mindestens eine der beiden Kategorie-IDs muss gesetzt sein (`Category::findOrFail` für jede gesetzte ID)
- Zwei `Action`-Datensätze erstellen (einer mit `cashflow_type = in`, einer mit `cashflow_type = out`), sofern die jeweilige Kategorie-ID gesetzt ist
- Bestehender Aufruf `saveRule()` (einzelne Kategorie) muss angepasst oder als Wrapper beibehalten werden

**Abnahmekriterien:** Regel kann mit zwei Kategorien gespeichert werden. Mindestvalidierung funktioniert.

### Schritt 5: `RuleAssignerWorkflow` anpassen

- `assignRule()`: Nach Ermittlung des Cashflow-Typs:
  - Wenn eindeutig Einnahmen: Nur Einnahmen-Kategorie abfragen, Ausgaben-Kategorie bleibt leer
  - Wenn eindeutig Ausgaben: Nur Ausgaben-Kategorie abfragen, Einnahmen-Kategorie bleibt leer
  - Wenn unbestimmt: Beide Kategorien optional abfragen
  - Validierung: Mindestens eine Kategorie muss belegt sein; beide dürfen gesetzt werden
- `saveRule()` Aufruf an neue Signatur von `RuleDataManager` anpassen
- Für `TRANSACTION_SPLIT` bleibt das bisherige Verhalten (eine Kategorie) unverändert, da die Anforderung nur TRANSACTION betrifft

**Abnahmekriterien:** Interaktiver Auswahlfluss funktioniert für alle drei Cashflow-Fälle. Mindestvalidierung greift.

### Schritt 6: `RefreshTransactionRuleIndexService` anpassen

- `applyCashflowFilter()` muss pro Kategorie erfolgen statt pro Regel
- Wenn Regel eine Einnahmen-Kategorie hat: Filter `amount >= 0` und ordne diese Kategorie zu
- Wenn Regel eine Ausgaben-Kategorie hat: Filter `amount < 0` und ordne diese Kategorie zu
- `isCashflowInRule()` und `addRuleTranscations()`/`addRuleTargets()` entsprechend anpassen
- Ausgabe in `refreshAll()` zeigt beide Kategorien an (falls beide gesetzt)

**Abnahmekriterien:** Einnahmen-Transaktionen erhalten die Einnahmen-Kategorie, Ausgaben-Transaktionen die Ausgaben-Kategorie.

### Schritt 7: `RuleListService` anpassen

- `actions.category` Eager Loading liefert nach der Umstellung auf `HasMany` mehrere Actions
- Bei `getRulesWithExpression()` und `getRuleWithExpression()`: `$rule['actions']` ist nun ein Array statt eines einzelnen Objekts

**Abnahmekriterien:** Mehrere Actions pro Regel werden korrekt geladen.

### Schritt 8: `RuleList` Command anpassen

- Tabelle um zweite Spalte erweitern (Option 2 aus Entscheidungen)
- Spalten: ID, Name, Expression, Einnahmen-Kategorie, Ausgaben-Kategorie
- Bei Regel mit nur einer Kategorie: Die andere Spalte ist leer

**Abnahmekriterien:** `fin:rule-list` zeigt beide Kategorien in separaten Spalten an.

### Schritt 9: `ReportDataProcessor` anpassen

- `groupTransactionsByCategory()`: Für eingehende Transaktionen (`amount > 0`) die Einnahmen-Kategorie der Regel verwenden, für ausgehende (`amount < 0`) die Ausgaben-Kategorie
- Aktuell wird `$transactionMappings->first()->category_id` verwendet – muss ersetzt werden durch logik, die je nach Betragszeichen die passende Kategorie wählt
- Gleiches für Splits über `rule_transaction_split → action`

**Abnahmekriterien:** Reports ordnen Einnahmen und Ausgaben korrekt den jeweiligen Kategorien zu.

### Schritt 10: `Rule::boot()` Löschlogik anpassen

- Aktuell: `if (!is_null($rule->actions)) { $rule->actions->delete(); }` (einzelnes Objekt)
- Neu: Collection von Actions durchlaufen und jedes löschen

**Abnahmekriterien:** Beim Löschen einer Regel werden alle zugehörigen Actions entfernt.

### Schritt 11: Rückwärtskompatibilität / Migration bestehender Regeln

- Bestehende Regeln haben eine `Action` ohne `cashflow_type`
- Bei der Indexierung: Wenn `cashflow_type` null ist, wird der Cashflow-Typ aus der Kategorie-Hierarchie abgeleitet
- In Reports werden bestehende Actions ohne `cashflow_type` als Legacy-Zuordnung verwendet
- Alternativ: Einmaliges Skript/Command zur Backfill-Setzung von `cashflow_type` auf Basis der bestehenden Kategorie

**Abnahmekriterien:** Bestehende Regeln funktionieren weiterhin ohne Änderung.

### Schritt 12: Tests

- Tests für `RuleDataManager::saveRuleForTarget()` mit zwei Kategorien und Mindestvalidierung
- Report-Test für Ein-/Ausgangskategorien sowie bestehende Actions ohne `cashflow_type`
- Regressionstest für die Zuordnungsvalidierung von Split-Regeln mit mehreren Actions
- Bestehende Tests weiterhin erfolgreich

**Abnahmekriterien:** PHPUnit, PHPStan und die Formatprüfung der hinzugefügten Regressionstests erfolgreich.

---

## 3. Abhängigkeiten und Risiken

| Risiko | Beschreibung | Gegenmaßnahme |
|--------|--------------|---------------|
| `HasMany` statt `HasOne` | Alle Stellen, die `$rule->actions` als einzelnes Objekt behandeln, müssen angepasst werden | Gesamtsuche nach `->actions` im Codebase |
| UNIQUE-Constraint `transaction_id` | Eine Transaktion kann nur einer Regel zugeordnet sein – bei Dual-Kategorie-Regel bleibt dies bestehen, da Einnahme ≠ Ausgabe | Keine Änderung nötig, da Transaktionen nach Betragszeichen getrennt werden |
| Reports mit erster Kategorie | `groupTransactionsByCategory` nutzt `first()->category_id` – muss ersetzt werden | Sorgfältige Anpassung an Betragszeichen-Logik |
| Bestehende Regeln ohne `cashflow_type` | Müssen bei Indexierung weiterhin funktionieren | Fallback auf Kategorie-Hierarchie bei null-Wert |


---

## Wichtiger Hinweis zur Datenbankänderung

Da im Projekt **keine Laravel-Migrationen** für die eigenen Tabellen existieren (nur `database.schema.sql` als Dokumentation), muss der Entwickler die erforderlichen SQL-Statements separat erstellen und Ihnen zur manuellen Ausführung zur Verfügung stellen.

Der Entwickler ist verantwortlich dafür:

1. Die finalen SQL-Statements für die Änderung der Tabelle `action` (Hinzufügen der Spalte `cashflow_type`) zu liefern.
2. Sie zur Ausführung durch Sie bereitzustellen.

Die SQL-Änderung wurde vor der PHP-Implementierung durch den Anwender ausgeführt:

```sql
ALTER TABLE `action`
    ADD COLUMN `cashflow_type` ENUM('in', 'out') NULL AFTER `category_id`;
```

---

## 4. Offene Fragen (nach dieser Planung noch offen)

Keine – alle 4 Entscheidungsfragen sind geklärt.

---

## 5. Abschluss

Die Dual-Kategorie-Unterstützung wurde umgesetzt. Die PHPUnit-Tests, PHPStan-Analyse und Laravel Pint-Prüfung waren erfolgreich.