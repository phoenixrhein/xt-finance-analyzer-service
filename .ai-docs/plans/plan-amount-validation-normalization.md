# Fehlerbehebungsplan: Deutsche Geldbetrags-Validierung und -Normalisierung

## Status

Abgeschlossen

---

## 1. Ursache

Beim manuellen Anlegen einer Regel über den Workflow `RuleAssignerWorkflow` (Command `fin:rule-assign`) wird der eingegebene Wert für das Feld `Betrag` nicht normalisiert.

`AmountField::validate()` prüft nur die Laravel-Validierungsregel `numeric` (`is_numeric`). Dadurch wird `"000"` als gültig akzeptiert und unverändert zurückgegeben. Zudem werden deutsche Beträge mit Komma als Dezimaltrenner (z. B. `12,50`) abgelehnt, weil `is_numeric("12,50") === false` ist.

**Betroffene Datenflüsse:**

* **Manueller Pfad:** `ConditionByManualCreator::promptInput()` → `AmountField::validate($value)` → Wert wird unverändert in `Condition` übernommen → über `RuleDataManager::saveCondition()` in Tabelle `conditions` gespeichert.
* **FinQuery-Pfad:** `ExpressionSyntaxParser::buildCondition()` → `$field->validate($value)` → Wert wird unverändert in `new Condition(...)` übernommen.

---

## 2. Fachliche Anforderungen

| Eingabe | Erwartetes Verhalten | Begründung |
|---------|---------------------|------------|
| `000` | Akzeptiert, normalisiert zu `0` | Führende Nullen entfernen |
| `12` | Akzeptiert, bleibt `12` | Ganze Zahl ohne Komma, keine Ergänzung |
| `12,50` | Akzeptiert, normalisiert zu `12.50` | Komma als Dezimaltrenner, intern Punkt für SQL |
| `12,5` | Akzeptiert, normalisiert zu `12.50` | Eine Dezimalstelle wird mit `0` ergänzt |
| `0,5` | Akzeptiert, normalisiert zu `0.50` | Einstellige Dezimalstelle ergänzt |
| `007` | Akzeptiert, normalisiert zu `7` | Führende Nullen entfernen |
| `12,555` | Abgelehnt (Fehlermeldung) | Mehr als zwei Dezimalstellen nicht erlaubt |
| `abc` | Abgelehnt (Fehlermeldung) | Nicht numerisch |

---

## 3. Geplante Änderungen

Keine neue Abhängigkeit, keine Datenbankänderung.

| Datei | Änderung |
|-------|----------|
| `app/Services/FinQuery/Fields/BaseField.php` | Neue Methode `normalizeValue(mixed $value): mixed` hinzufügen. Default-Implementierung gibt `$value` unverändert zurück. |
| `app/Services/FinQuery/Fields/Transaction/AmountField.php` | `validate()` erweitern, um deutsche Beträge mit Komma und maximal zwei Dezimalstellen zu akzeptieren. `normalizeValue()` überschreiben: bei Komma → Komma durch Punkt ersetzen, `sprintf('%.2f', ...)`; ohne Komma → `(string)(float)$value` (führende Nullen entfernen). |
| `app/Traits/Command/View/ConditionByManualCreator.php` | In `promptInput()`: Nach erfolgreichem `$field->validate($value)` den Wert mit `$field->normalizeValue($value)` normalisieren. |
| `app/Services/Rule/Expression/ExpressionSyntaxParser.php` | In `buildCondition()`: Nach dem Validierungs-Block (vor `new Condition(...)`) den Wert mit `$field->normalizeValue($value)` normalisieren. |

**Begründung für beide Stellen:** Der manuelle Pfad (`ConditionByManualCreator`) und der FinQuery-Pfad (`ExpressionSyntaxParser`) übernehmen den Wert unterschiedlich in die `Condition`. Beide müssen normalisieren, damit der Fehler an keiner der beiden Stellen auftritt.

---

## 4. Abnahmekriterien (überprüfbar)

| # | Kriterium | Prüfweise |
|---|-----------|-----------|
| 1 | Manuelle Eingabe `000` im Workflow „Regel zuweisen“ → „Normale Buchung“ → Feld „Betrag“ → akzeptiert, gespeicherter Wert ist `0` | Command `php artisan fin:rule-assign <id>`, manuelle Durchführung |
| 2 | Manuelle Eingabe `12` → akzeptiert, gespeicherter Wert bleibt `12` | Manuelle Durchführung |
| 3 | Manuelle Eingabe `12,50` → akzeptiert, gespeicherter Wert ist `12.50` | Manuelle Durchführung |
| 4 | Manuelle Eingabe `12,5` → akzeptiert, gespeicherter Wert ist `12.50` | Manuelle Durchführung |
| 5 | Manuelle Eingabe `abc` → abgelehnt mit Fehlermeldung, erneute Eingabe erforderlich | Manuelle Durchführung |
| 6 | Manuelle Eingabe `12,555` → abgelehnt mit Fehlermeldung | Manuelle Durchführung |
| 7 | FinQuery-Pfad: Ausdruck `amount > 000` akzeptiert, Wert normalisiert zu `0` | Manuelle Eingabe FinQuery-Ausdruck |
| 8 | FinQuery-Pfad: Ausdruck `amount > 12,5` akzeptiert, Wert normalisiert zu `12.50` | Manuelle Eingabe FinQuery-Ausdruck |
| 9 | Bestehende PHPUnit-Tests laufen fehlerfrei durch | `vendor/bin/phpunit` |

---

## 5. Risiken

* Bereits gespeicherte Regeln mit Werten wie `000` oder `12,5` in der Datenbank bleiben unverändert (keine Migration vorgesehen).
* Negativwerte: `-000` wird zu `0`, `-0,50` zu `-0.50`. Fachlich unkritisch.
* Der normalisierte Wert bleibt ein String (für die SQL-Erzeugung), nicht ein Float.

---

## 6. Nächste Schritte

Die deutsche Betragsvalidierung und -normalisierung wurde in den manuellen und FinQuery-Pfad integriert.
Die Regressionstests für gültige und ungültige Beträge sowie die bestehende PHPUnit-Suite laufen fehlerfrei.
