# Active Context

> Beschreibt ausschließlich den aktuellen Arbeitsstand des Projekts.
>
> Hier gehören hinein:
> - aktueller Fokus
> - aktuelles Ziel
> - aktuell offene Aufgaben
> - für die aktuelle Arbeit relevante Entscheidungen
> - aktuelle Blocker
> - der nächste konkrete Schritt
>
> Dieser Inhalt darf sich häufig ändern und soll den tatsächlichen aktuellen
> Stand widerspiegeln.
>
> Nicht hier dokumentieren:
> - grundlegende Projektziele → `projectBrief.md`
> - technische Rahmenbedingungen → `techContext.md`
> - Fachbegriffe → `glossary.md`
> - dauerhafte Architektur und Entscheidungen → `architecture.md`
> - langfristige Meilensteine → `progress.md`
>
> Nur Informationen aufnehmen, die für die aktuelle Arbeit relevant sind.
> Erledigte oder nicht mehr relevante Punkte entfernen bzw. aktualisieren,
> statt sie als Historie stehen zu lassen.

## Current Focus

Weiterentwicklung des bestehenden Brownfield-Projekts in Richtung Version 1.0.

Aktuell liegt der Fokus auf der Planung und Umsetzung weiterer Entwicklungsarbeiten im bestehenden Projekt, wobei die vorhandene Architektur grundsätzlich beibehalten wird.

## Current Goal

Die für Version 1.0 noch erforderlichen Funktionen und Änderungen schrittweise umzusetzen.

Bei neuen Entwicklungsarbeiten soll bewusst entschieden werden, welche Logik in Commands, Services oder Models gehört.

Businesslogik soll bei neuen Funktionen möglichst in Services gekapselt werden. Wiederverwendbare Abläufe und Interaktionen können ebenfalls in Services liegen, wenn dies eine sinnvolle Wiederverwendung ermöglicht.

## Open Tasks

* [ ] Weitere für Version 1.0 erforderliche Funktionen und Änderungen umsetzen
* [ ] Neue Entwicklungsarbeiten hinsichtlich ihrer Verantwortungsverteilung zwischen Command, Service und Model bewusst beurteilen
* [ ] Änderungen durch Tests und automatisierte Prüfungen absichern
* [ ] Projekt auf aktuelle stabile Versionen von PHP, Laravel und den verwendeten Paketen aktualisieren

## Relevant Decisions

* Die Umbenennung von `IgnoreList` zu `ExclusionList` wurde umgesetzt.
* Die Umbenennung von `CashDeposit` zu `CashTransaction` wurde umgesetzt.
* `CashTransaction` besitzt einen Betrag und einen Kommentar.
* `CashTransaction` kann optional über `transaction_id` mit einer bestehenden `Transaction` verknüpft werden.
* Für Version 1.0 wird die bestehende Architektur nicht grundsätzlich refaktoriert oder vereinheitlicht.
* Neue Businesslogik soll möglichst in Services gekapselt werden.
* Services dürfen bei sinnvoller Wiederverwendung auch Interaktionen kapseln.
* Eine starre Trennung, nach der Interaktionen ausschließlich in Commands stattfinden müssen, ist nicht vorgesehen.

## Blockers

Keine.

## Next Step

Die nächste konkrete Version-1.0-Entwicklungsaufgabe planen und anschließend umsetzen.