# Progress Log

> Dokumentiert nur langfristig relevante Meilensteine des Projekts.
>
> Hier gehören hinein:
> - wichtige abgeschlossene Entwicklungsphasen
> - wesentliche Projektfortschritte
> - bedeutende Änderungen am Projektzustand
>
> Nicht hier dokumentieren:
> - einzelne alltägliche Tasks
> - kurzfristige Arbeitsschritte
> - detaillierte Entwicklungschroniken
> - den aktuellen Arbeitsstand → `activeContext.md`
>
> Ein Meilenstein sollte nur aufgenommen werden, wenn er für das langfristige
> Verständnis oder die Entwicklung des Projekts relevant ist.
>
> Die History bleibt bewusst kurz. Nicht jede Codeänderung oder jedes
> abgeschlossene Feature benötigt einen eigenen Eintrag.

## Milestones

* [x] Grundlegende Anwendung für die Analyse von Girokonto-Buchungen entwickelt
* [x] Import von Bankbuchungen im Format CAMT.52 V8 umgesetzt
* [x] Regelbasierte Zuordnung von Buchungen zu Kategorien umgesetzt
* [x] Hierarchische Kategorien und Cashflows umgesetzt
* [x] Reports für Einnahmen und Ausgaben umgesetzt
* [x] Funktionen für Transaction Splits und Transaction Adjustments umgesetzt
* [x] ExclusionList und CashTransaction als Bestandteile der Finanzanalyse umgesetzt
* [ ] Version 1.0 fertigstellen
* [ ] Anwendung auf aktuelle stabile Versionen von PHP, Laravel und den verwendeten Paketen aktualisieren

## History

### [NOCH NICHT FESTGELEGT] – Entwicklung der Service-Schicht begonnen

Ein Teil der ursprünglich in Commands enthaltenen Businesslogik wurde schrittweise in Services ausgelagert.

Die Auslagerung ist noch nicht vollständig abgeschlossen. Für Version 1.0 ist keine nachträgliche vollständige Vereinheitlichung der bestehenden Architektur vorgesehen.

### [NOCH NICHT FESTGELEGT] – Brownfield-Stand für Version 1.0 festgelegt

Das Projekt befindet sich in einem weit fortgeschrittenen Entwicklungsstand. Für die Fertigstellung von Version 1.0 soll die bestehende Implementierung grundsätzlich weiterentwickelt und nicht umfassend refaktoriert werden.

Bei neuen Funktionen soll die Platzierung von Businesslogik in Commands, Services oder Models jeweils bewusst entschieden werden.