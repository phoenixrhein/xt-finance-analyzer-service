# Glossary

> Enthält projektspezifische Fachbegriffe und die dafür verwendeten
> englischen Bezeichnungen im Code.
>
> Fachliche Begriffe werden grundsätzlich auf Deutsch verwendet.
> Code-Bezeichnungen werden auf Englisch verwendet.
>
> Variablen und Methoden verwenden `camelCase`.
> Datenbankspalten verwenden `snake_case`.
>
> Nur bestätigte Begriffe aufnehmen. Unbekannte Begriffe als
> `[NOCH NICHT FESTGELEGT]` oder `[NOCH ZU KLÄREN]` kennzeichnen.

## General Conventions

| Deutsch | Englisch / Code-Begriff |
|---|---|
| Buchung | `transaction` |
| Girokonto | `bankAccount` |
| Kategorie | `category` |
| Regel | `rule` |
| Bedingung | `condition` |
| Aktion | `action` |
| Cashflow | `cashflow` |
| Report | `report` |

## Transactions

| Deutsch | Englisch / Code-Begriff |
|---|---|
| Buchung | `transaction` |
| Buchungstag | `transactionDate` |
| Valutadatum | `exchangeDate` |
| Buchungstext | `transactionType` |
| Verwendungszweck | `reasonForPayment` |
| Begünstigter / Zahlungspflichtiger | `beneficiaryPayee` |
| Betrag | `amount` |
| Währung | `currency` |
| Auftragskonto | `bankAccountIban` |
| Notiz | `note` |
| Prüfcodes | `checksCode` |
| nicht zugewiesen | `unmatched` |
| nicht zuweisbar | `UNCATEGORISABLE` |
| Umbuchung | `Transaction Adjustment` |
| Split-Buchung | `Transaction Split` |
| Bareinzahlung | `Cash Deposit` |

### Transaction

`Transaction` bezeichnet in erster Linie eine Bankbuchung bzw. Kontobewegung.

### Transaction Adjustment

Eine `Transaction Adjustment` verändert die ursprüngliche `Transaction` nicht, sondern ermöglicht eine abweichende Berücksichtigung für die Auswertung.

### Transaction Split

Ein `Transaction Split` ermöglicht die Aufteilung einer `Transaction` auf mehrere Kategorien.

### unmatched

`unmatched` bezeichnet eine Buchung, der noch keine Kategorie zugewiesen wurde.

### UNCATEGORISABLE

`UNCATEGORISABLE` bezeichnet eine Buchung, die bewusst als nicht zuweisbar gekennzeichnet wurde und deshalb nicht weiter bearbeitet werden muss.

## Accounts

### BankAccount

`BankAccount` bezeichnet ein Girokonto.

Besondere Konten wie beispielsweise Sparbücher werden davon begrifflich getrennt behandelt.

## Categories

### Category

`Category` bezeichnet eine Kategorie für die Zuordnung und Auswertung von Buchungen.

Kategorien können hierarchisch als Ober- und Unterkategorien organisiert sein.

### Cashflow

`Cashflow` bezeichnet die Richtung einer Einnahme oder Ausgabe.

| Wert | Bedeutung |
|---|---|
| `in` | Einnahmen |
| `out` | Ausgaben |

Ein Kategorienbaum gehört jeweils zu einem Cashflow (`in` oder `out`).

## Rules

### Rule

Eine `Rule` dient der regelbasierten Zuordnung von Buchungen.

Eine `Rule` besitzt eine oder mehrere `Condition` und ist mit einer `Action` verknüpft.

### Condition

Eine `Condition` beschreibt eine Bedingung innerhalb einer `Rule`.

### Action

Eine `Action` ist mit einer `Category` verknüpft und legt die durch eine `Rule` ausgelöste Zuordnung fest.

## Reports

### Report

Ein `Report` ist eine monatliche oder jährliche Auswertung der Finanzdaten.

Reports können mit vergangenen Zeiträumen verglichen werden.

### isCompletedTarget

`isCompletedTarget` kennzeichnet, ob ein Zeitraum als vollständig importiert gilt und damit für die entsprechende Auswertung berücksichtigt werden kann.