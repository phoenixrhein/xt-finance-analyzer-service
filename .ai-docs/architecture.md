# Architecture

> Beschreibt die bestehende Architektur des Projekts und die grundsätzliche
> Richtung der weiteren Entwicklung.
>
> Da es sich um ein Brownfield-Projekt handelt, werden bestehende
> Implementierung und gewünschte zukünftige Entwicklung getrennt betrachtet.
>
> Diese Datei beschreibt keine vollständige technische Dokumentation aller
> Klassen, sondern die wesentlichen Verantwortlichkeiten und Abhängigkeiten.
>
> Nur bestätigte Informationen aufnehmen. Unbekannte Angaben als
> `[NOCH NICHT FESTGELEGT]` oder `[NOCH ZU KLÄREN]` kennzeichnen.

## Architecture Overview

Die Anwendung ist als Laravel-basierte CLI-Anwendung aufgebaut.

Die zentrale fachliche Verarbeitung verteilt sich aktuell auf mehrere Ebenen:

* Commands bilden den Einstiegspunkt für CLI-Aufrufe und enthalten die interaktiven Abläufe.
* Services kapseln zunehmend fachliche Logik, technische Verarbeitung und wiederverwendbare Abläufe.
* Models bilden die Datenstrukturen und deren Eloquent-Beziehungen ab und enthalten teilweise fachliche Hilfsmethoden sowie Löschlogik.
* DTOs dienen der strukturierten Übergabe von Daten zwischen Komponenten.
* Rules, Enums, Exceptions, Helpers und Traits unterstützen die übrigen Komponenten.

Die Architektur befindet sich in einer Übergangsphase. Businesslogik wurde im Laufe der Entwicklung schrittweise aus den Commands in Services ausgelagert. Dieser Prozess ist aktuell noch nicht vollständig abgeschlossen.

Für die Version 1.0 ist keine nachträgliche Vereinheitlichung oder grundlegende Umstrukturierung der bestehenden Architektur vorgesehen.

## Commands

Die Commands unter `app/Console/Commands` bilden den Einstiegspunkt für die CLI-Anwendung.

Sie sind nach fachlichen Bereichen organisiert, unter anderem:

* Account
* User
* ExclusionList
* Rule
* Transaction
* TransactionAdjustment
* TransactionSplit
* Category
* Report

Commands übernehmen insbesondere:

* Benutzerinteraktion
* Eingabe und Auswahl von Daten
* Bestätigungen
* Ausgabe von Tabellen, Meldungen und Fehlern
* Aufruf von Services
* teilweise auch fachliche Verarbeitung und Datenbankzugriffe

Die bestehende Implementierung ist dabei nicht vollständig einheitlich.

Einige Commands sind überwiegend orchestrierend und delegieren wesentliche Verarbeitung an Services. Andere Commands enthalten noch umfangreiche fachliche Logik.

Besonders umfangreiche Logik befindet sich unter anderem in:

* `TransactionList`
* `TransactionImport`
* `CashDetector`
* `SplitUpsert`
* `SimpleReport`

Andere Commands, beispielsweise Listen- oder einfache Verwaltungs-Commands, enthalten vergleichsweise wenig eigene Logik.

### FinCommand

`FinCommand` ist die gemeinsame abstrakte Basisklasse der Commands.

Sie stellt unter anderem gemeinsame CLI-Funktionalität bereit und übernimmt die Initialisierung von Übersetzungen für Command-Signaturen und Beschreibungen.

Der eigentliche Command-Ablauf wird über `handle()` und die vorgesehenen Verarbeitungsmethoden bzw. `__invoke()` ausgeführt.

## Services

Unter `app/Services` befindet sich die zunehmend ausgebaute Service-Schicht.

Services übernehmen bereits heute unterschiedliche Verantwortlichkeiten. Dazu gehören insbesondere:

* fachliche Verarbeitung
* Workflow-Orchestrierung
* Datenzugriff
* Datenaggregation
* Validierung
* Transformation
* Regelverarbeitung
* Importverarbeitung
* Reportverarbeitung
* CLI-Interaktion und CLI-Ausgabe
* technische Hilfslogik

Die Service-Schicht ist deshalb nicht ausschließlich auf reine Businesslogik beschränkt.

### Services\Console

`Services\Console` enthält Services für CLI-nahe Abläufe.

Dazu gehören unter anderem:

* interaktive Auswahl und Validierung
* standardisierte Console-Ausgabe
* Report-Konfiguration und Report-Präsentation
* Kategorieverwaltung
* Regelzuweisung
* Vorbereitung von Transaktionen für Reports

Beispiele:

* `AbstractIOService`
* `SelectTargetPeriodAction`
* `ConsoleOutput`
* `NullOutput`
* `ReportConfiguratorWizard`
* `ReportPresenter`
* `ReportDataProcessor`
* `RuleAssignerWorkflow`
* `ManageConsoleService`
* `TreeViewConsoleService`

Diese Services zeigen, dass interaktive Abläufe nicht grundsätzlich auf Commands beschränkt sind.

### Services\Import

`Services\Import` kapselt die Verarbeitung von Transaktionsimporten.

Die Importverarbeitung ist in mehrere Verantwortlichkeiten aufgeteilt:

* Erkennung des Dateityps
* Erkennung des Dateiformats
* Dateivalidierung
* Parsing
* Transformation in DTOs
* Speicherung der importierten Daten
* Fortschrittsanzeige

Der zentrale Orchestrator ist `ImportTransactionService`.

Die konkrete Verarbeitung des aktuell unterstützten CSV-Formats CAMT.52 V8 erfolgt durch entsprechende spezialisierte Parser.

Die Struktur ermöglicht grundsätzlich die spätere Unterstützung weiterer Importformate.

### Services\Rule

`Services\Rule` enthält wesentliche Bestandteile der regelbasierten Zuordnung von Buchungen.

Dazu gehören unter anderem:

* Aufbau und Verarbeitung von FinQuery-Ausdrücken
* Parsen von Ausdrücken
* Umwandlung von Regeln in Bedingungen
* Aktualisierung von Regelzuweisungen
* Validierung von Regelzuweisungen
* Verwaltung von Regeln und zugehörigen Daten
* Darstellung von Regeln

Beispiele:

* `RefreshTransactionRuleIndexService`
* `RuleDataManager`
* `RuleListService`
* `RuleToConditionTransformer`
* `RuleTransactionAssignmentsValidator`

### Services\FinQuery

`Services\FinQuery` kapselt die Verarbeitung der projektspezifischen FinQuery-Syntax.

Die Services übernehmen unter anderem:

* Definition verfügbarer Felder
* Definition verfügbarer Operatoren
* Validierung
* Übersetzung von FinQuery-Operatoren in SQL-Operatoren
* Aufbau und Verarbeitung von Bedingungen

Die eigentliche Feld- und Operatorlogik ist über spezialisierte Klassen strukturiert.

### Services\Query

`Services\Query` enthält wiederverwendbare Query-bezogene Komponenten.

Beispielsweise stellt `AccountListQuery` eine Query für die Auswahl bzw. Auflistung von Bankkonten bereit.

### Root Services

Unter `app/Services` existieren außerdem Services für fachliche Abläufe, die keinem der genannten Unterbereiche zugeordnet sind.

Beispiel:

* `UnmatchedTransactionsService`

Dieser Service stellt nicht zugeordnete Buchungen und häufig auftretende Zahlungspartner für die weitere Verarbeitung bzw. Darstellung bereit.

## Models

Die Models unter `app/Models` sind Eloquent-orientiert und bilden die Datenstrukturen und Beziehungen der Anwendung ab.

Zu den aktuell vorhandenen Models gehören unter anderem:

* `User`
* `BankAccount`
* `Cashflow`
* `Category`
* `Rule`
* `Action`
* `Condition`
* `ConditionLink`
* `ExclusionList`
* `Transactions`
* `TransactionSplit`
* `TransactionAdjustment`

Die Models enthalten insbesondere:

* Eloquent-Beziehungen
* Datenbankzugriff über Eloquent
* teilweise Validierungsregeln
* teilweise fachliche Hilfsmethoden
* teilweise Lösch- bzw. Cascade-Logik

Die Models sind nicht als reine Datenobjekte ohne Logik ausgelegt.

### Beziehungen

Wichtige Beziehungen sind unter anderem:

* `User` ↔ `BankAccount` über `bank_account_user`
* `BankAccount` → `User`
* `BankAccount` → `Rule`
* `BankAccount` → `Cashflow`
* `BankAccount` → `ExclusionList`
* `BankAccount` → `Transactions`
* `Cashflow` → Kategorien
* `Category` → übergeordnete Kategorie und Unterkategorien
* `Rule` → `Action`
* `Rule` → `ConditionLink`
* `Rule` ↔ `Transactions`
* `Rule` → `BankAccount`
* `ExclusionList` → `BankAccount`
* `Transactions` → `BankAccount`
* `Transactions` → `TransactionAdjustment`
* `Transactions` → `TransactionSplit`
* `Transactions` ↔ `Rule`

## Business Logic in Models

Ein Teil der fachlichen Logik befindet sich auch in den Models.

Beispiele:

* `Cashflow::createWithCategories()` erzeugt die zugehörigen Ein- und Ausgangskategorien.
* `Category::getCashflow()` und `Category::getCashflowCategory()` ermitteln den zugehörigen Cashflow über die Kategoriehierarchie.
* `Category::ancestors()` liefert die übergeordneten Kategorien.
* `BankAccount::getRules()` und `Category::getRules()` stellen Validierungsregeln bereit.
* `User::$rules` enthält Validierungsregeln für Benutzer.

Diese bestehende Verteilung wird für Version 1.0 nicht grundsätzlich refaktoriert.

## Model-side Deletion Logic

Einige Models enthalten `boot()`-Hooks, über die abhängige Datensätze beim Löschen entfernt werden.

Unter anderem bestehen folgende Abhängigkeiten:

* Beim Löschen eines `BankAccount` werden zugehörige `Cashflow`, `ExclusionList` und `Transactions` entfernt.
* Beim Löschen eines `Cashflow` werden die zugehörigen Ein- und Ausgangskategorien entfernt.
* Beim Löschen einer `Category` werden deren Unterkategorien entfernt.
* Beim Löschen eines `Rule` werden zugehörige `Action` und `ConditionLink` entfernt.
* Beim Löschen eines `ConditionLink` werden abhängig vom Typ zugehörige Conditions bzw. Gruppen entfernt.
* Beim Löschen einer `Transactions` werden zugehörige `TransactionAdjustment` und `TransactionSplit` entfernt.

Die Löschlogik ist damit teilweise in den Models und nicht ausschließlich über Datenbank-Foreign-Keys umgesetzt.

## TransactionSplit

`TransactionSplit` dient zur Aufteilung einer bestehenden `Transaction`.

Der Split-Betrag wird immer als positiver Betrag gespeichert. Das Vorzeichen bzw. der Cashflow wird von der übergeordneten `Transaction` bestimmt.

Aktuell sind folgende Split-Typen vorgesehen:

* `cash_payout`
* `other`

Ein `cash_payout` stellt einen Bargeldanteil einer bestehenden Transaction dar und wird im Report der Kategorie `Bargeld` zugeordnet.

Ein `other`-Split stellt einen sonstigen Anteil einer Transaction dar, der einer eigenen Kategorie zugeordnet werden kann.

Die Summe aller Splits einer Transaction darf deren absoluten Transaction-Betrag nicht überschreiten.

Die Prüfung erfolgt bereits beim Erstellen und Bearbeiten eines Splits.

Eine eigenständige Bargeldkasse wird derzeit nicht abgebildet. Bargeldeinnahmen ohne zugrunde liegende Bankbuchung werden daher derzeit nicht erfasst.

## Reports

Der aktuelle Report befindet sich in:

`app/Console/Commands/Report/Report.php`

`app/Console/Commands/Report/SimpleReport.php` ist ein alter bzw. Legacy-Report und wird für die aktuelle Reportentwicklung nicht berücksichtigt.

Der aktuelle Report berücksichtigt für eine `Transaction` das effektive Buchungsdatum:

* vorhandene `Transaction Adjustment` → Datum des Adjustments
* kein Adjustment → ursprüngliches `transaction_date`

Weitere fachliche Reportregeln werden im Rahmen der jeweiligen Weiterentwicklung ergänzt.

## DTOs

DTOs werden für die strukturierte Übergabe von Daten verwendet.

Ein bekanntes Beispiel ist `TransactionImportDto`, das während des Transaktionsimports verwendet wird.

Auch im Bereich der FinQuery-Verarbeitung werden DTOs wie `Condition` und `ConditionList` verwendet.

Die DTOs dienen insbesondere dazu, strukturierte Daten zwischen Parsern, Services und weiteren Verarbeitungskomponenten zu übertragen.

## Rules

Unter `app/Rules` befinden sich Laravel- bzw. anwendungsbezogene Validierungsregeln.

Sie unterstützen die Validierung von Eingaben und fachlichen Daten.

Die konkrete Zuordnung und Verwendung der einzelnen Rules ist `[NOCH NICHT VOLLSTÄNDIG DOKUMENTIERT]`.

## Enums

Unter `app/Enums` befinden sich projektspezifische Enumerationen.

Sie repräsentieren definierte Wertebereiche innerhalb der Anwendung, beispielsweise für:

* Transaktionstypen
* Prüfcodes
* Währungen bzw. Währungscodes
* weitere fachliche Zustände

Die vollständige fachliche Bedeutung aller vorhandenen Enums ist `[NOCH NICHT VOLLSTÄNDIG DOKUMENTIERT]`.

## Exceptions

Unter `app/Exceptions` befinden sich projektspezifische Exception-Klassen.

Sie dienen der strukturierten Behandlung von Fehlerfällen.

Die vollständige Zuordnung der Exceptions zu den einzelnen fachlichen Bereichen ist `[NOCH NICHT VOLLSTÄNDIG DOKUMENTIERT]`.

## Helpers

Unter `app/Helpers` befinden sich Hilfsfunktionen bzw. Hilfsklassen.

Sie unterstützen wiederkehrende technische oder fachliche Aufgaben.

Die vollständige Verantwortungsverteilung der vorhandenen Helpers ist `[NOCH NICHT VOLLSTÄNDIG DOKUMENTIERT]`.

## Traits

Unter `app/Traits` befinden sich wiederverwendbare Traits.

In der bestehenden Implementierung werden Traits unter anderem von Commands verwendet, beispielsweise für wiederkehrende CLI-Auswahl- und Eingabeabläufe.

Die bestehende Verwendung von Traits wird im Rahmen der Version 1.0 nicht grundsätzlich refaktoriert.

Für neue gemeinsame Command-Funktionalität soll nicht automatisch ein Trait verwendet werden. Die konkrete Lösung soll abhängig von der Verantwortung und Wiederverwendbarkeit gewählt werden.

## Http

Unter `app/Http` befinden sich Laravel-HTTP-Komponenten.

Die Anwendung ist aktuell als CLI-Anwendung ausgelegt. Eine vollständige HTTP-basierte Benutzeroberfläche ist `[NOCH NICHT FESTGELEGT]`.

## Providers

Unter `app/Providers` befinden sich Laravel Service Provider.

Sie dienen der Registrierung bzw. Konfiguration von Anwendungskomponenten innerhalb des Laravel-Frameworks.

Die vollständige projektspezifische Provider-Konfiguration ist `[NOCH NICHT VOLLSTÄNDIG DOKUMENTIERT]`.

## Responsibility Distribution

Die bestehende Verantwortungsverteilung lässt sich vereinfacht wie folgt beschreiben:

| Bereich | Bestehende Verantwortung |
|---|---|
| Commands | CLI-Einstiegspunkt, Interaktion, teilweise Businesslogik, Orchestrierung |
| Services | Businesslogik, Workflows, Datenverarbeitung, Validierung, technische Verarbeitung und teilweise CLI-Interaktion |
| Models | Datenmodell, Beziehungen, teilweise Businesslogik und Löschlogik |
| DTOs | Strukturierte Datenübertragung |
| Rules | Validierung |
| Enums | Definierte Wertebereiche |
| Exceptions | Fehlerbehandlung |
| Helpers | Wiederverwendbare Hilfsfunktionen |
| Traits | Wiederverwendbare bestehende Funktionalität |
| Providers | Laravel-Registrierung und Konfiguration |

Die Grenzen zwischen Commands, Services und Models sind in der bestehenden Implementierung nicht vollständig einheitlich.

## Dependencies

Die wichtigsten Abhängigkeitsrichtungen der bestehenden Implementierung sind:

* Commands verwenden Services und Models.
* Services verwenden Models und weitere Services.
* Models verwenden andere Models über Eloquent-Beziehungen.
* Commands und Services verwenden DTOs, Enums, Rules, Exceptions, Helpers und Traits.
* Services können direkt auf Datenbank- bzw. Eloquent-Funktionalität zugreifen.

Die Anwendung verwendet damit derzeit keine vollständig durchgesetzte Schichtenarchitektur mit strikt einseitigen Abhängigkeiten.

## Architecture Development

Die Architektur hat sich während der Entwicklung schrittweise verändert.

Ursprünglich befand sich ein größerer Teil der Businesslogik direkt in den Commands.

Im weiteren Verlauf wurde begonnen, diese Logik in Services auszulagern. Dadurch entstand die heutige gemischte Struktur aus Commands mit eigener Logik und Commands, die wesentliche Verarbeitung an Services delegieren.

Dieser Prozess ist noch nicht vollständig abgeschlossen.

Für Version 1.0 ist keine nachträgliche vollständige Bereinigung oder Vereinheitlichung der bestehenden Commands vorgesehen.

Stattdessen soll bei zukünftigen Änderungen jeweils bewusst entschieden werden, wo neue Logik sinnvollerweise umgesetzt wird.

## Development Direction

Für die zukünftige Entwicklung gilt als grundsätzliche Richtung:

* Neue Businesslogik soll möglichst in Services gekapselt werden.
* Commands sollen grundsätzlich den CLI-Einstiegspunkt und den jeweiligen Ablauf darstellen.
* Bestehende Commands werden jedoch nicht allein zur Herstellung einer einheitlichen Architektur refaktoriert.
* Wiederverwendbare Abläufe sollen als Services umgesetzt werden, wenn dies eine sinnvolle Wiederverwendung ermöglicht.
* Services können neben fachlicher Logik auch Interaktionen kapseln, wenn diese Interaktionen Teil eines wiederverwendbaren Ablaufs sind.
* Eine starre Regel, nach der sämtliche Benutzerinteraktion ausschließlich innerhalb von Commands stattfinden darf, ist daher nicht als Architekturvorgabe festgelegt.
* Bei neuen Funktionen soll die Zuordnung zu Command, Service oder Model bewusst anhand der jeweiligen Verantwortung getroffen werden.
* Bestehende Implementierungsentscheidungen dürfen nicht automatisch als Zielarchitektur für neue Funktionen verstanden werden.

Die gewünschte langfristige Richtung ist damit eine stärkere Kapselung der Businesslogik in Services, ohne die bestehende Implementierung für Version 1.0 grundsätzlich umzubauen.