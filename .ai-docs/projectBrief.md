# Project Brief

> Beschreibt das Projekt auf fachlicher und übergeordneter Ebene.
>
> Hier gehören die grundlegende Idee, der Zweck, das gewünschte Ergebnis,
> die wichtigsten Funktionen und die Zielgruppe hinein.
>
> Nicht hier dokumentieren:
> - technische Details → `techContext.md`
> - Architekturentscheidungen → `architecture.md`
> - aktuelle Aufgaben und Entwicklungsstand → `activeContext.md`
> - langfristige Meilensteine → `progress.md`
>
> Nur bestätigte Informationen aufnehmen. Unbekannte Angaben als
> `[NOCH NICHT FESTGELEGT]` oder `[NOCH ZU KLÄREN]` kennzeichnen.

## Core Vision

Der XT-Finance-Analyzer-Service soll einen übersichtlichen Überblick über die Einnahmen und Ausgaben eines Girokontos ermöglichen.

Einnahmen und Ausgaben sollen nach Monat und Jahr ausgewertet, Kategorien zugeordnet und mit vergangenen Zeiträumen verglichen werden können. Dadurch soll erkennbar werden, wofür Geld eingenommen oder ausgegeben wird und in welchen Bereichen sich die Einnahmen und Ausgaben gegenüber früheren Monaten oder Jahren verändert haben.

Die Ergebnisse sollen als monatliche und jährliche Reports bereitgestellt und zusätzlich als passwortgeschützte PDF-Datei per E-Mail versendet werden können.

## Problem / Purpose

Online-Banking bietet keine ausreichende Aufteilung der Einnahmen und Ausgaben nach Monat und Jahr und ermöglicht insbesondere keine individuelle Kategorisierung der Buchungen.

Der XT-Finance-Analyzer-Service soll deshalb ermöglichen, Kontobewegungen nach eigenen Regeln und einem hierarchischen Kategorienbaum auszuwerten. Dabei soll sichtbar sein, welche Einnahmen und Ausgaben welchen Kategorien und gegebenenfalls Ober- und Unterkategorien zugeordnet sind.

Durch Vergleiche mit vergangenen Monaten und Jahren soll außerdem erkennbar werden, in welchen Kategorien besonders viel oder wenig eingenommen bzw. ausgegeben wurde.

Die Anwendung soll außerdem besondere finanzielle Vorgänge wie Bargeldein- und -auszahlungen, gesplittete Buchungen und Umbuchungen abbilden können. Besondere Konten, beispielsweise Sparbücher, sollen dabei anders als normale Einnahmen und Ausgaben behandelt werden können.

## Key Features

* Girokonten anlegen und verwalten
* Benutzer anlegen und verwalten und einem oder mehreren Girokonten zuordnen
* Bankbuchungen aus CSV-Dateien im Format Camt52V8 importieren und versehentliche Doppelimporte vermeiden
* Einnahmen und Ausgaben manuell kategorisieren
* Einen hierarchischen Kategorienbaum mit Ober- und Unterkategorien verwalten
* Regeln zur Unterstützung der Zuordnung von Buchungen zu Kategorien verwalten
* Nicht durch Regeln zugeordnete Buchungen in einer Liste anzeigen und eine manuelle Nachbearbeitung ermöglichen
* Buchungen als „nicht zuweisbar“ markieren können, damit sie aus der Liste der noch zu bearbeitenden Buchungen verschwinden
* Für nicht zuweisbare Buchungen die Kategorien „Sonstige Einnahmen“ bzw. „Sonstige Ausgaben“ verwenden
* Häufig auftretende Zahlungspartner unter den nicht zugeordneten Buchungen als Top-X-Liste anzeigen
* Buchungen auf mehrere Kategorien aufteilen können
* Bargeldeinnahmen und Bargeldauszahlungen abbilden können
* Buchungen für die Auswertung auf ein anderes Buchungsdatum umbuchen können, ohne die ursprüngliche Bankbuchung zu verändern
* Besondere Konten, beispielsweise Sparbücher, verwalten und bei der Auswertung anders behandeln
* Monatliche und jährliche Reports erstellen
* Reports mit vergangenen Monaten und Jahren vergleichen
* Für Reports ausschließlich vergangene und vollständig importierte Zeiträume verwenden
* Reports als passwortgeschützte PDF-Dateien per E-Mail an die hinterlegten Benutzer versenden
* Weitere Buchungsformate zukünftig unterstützen können; das Importformat wird erkannt und die Importverarbeitung kann entsprechend erweitert werden

## Target Audience

Die Anwendung ist in erster Linie für die persönliche Finanzverwaltung des Entwicklers vorgesehen.

Da das Projekt öffentlich auf GitHub bereitgestellt wird, kann es grundsätzlich auch von anderen privaten Nutzern verwendet werden.