# Technical Context

> Beschreibt die technischen Rahmenbedingungen des Projekts.
>
> Hier gehören insbesondere Technologien, Laufzeitumgebung, Datenhaltung,
> externe Dienste sowie Entwicklungs- und Qualitätswerkzeuge hinein.
>
> Nicht hier dokumentieren:
> - fachliche Ziele und Funktionen → `projectBrief.md`
> - Architekturentscheidungen → `architecture.md`
> - aktuelle Aufgaben und Entwicklungsstand → `activeContext.md`
> - langfristige Meilensteine → `progress.md`
>
> Nur bestätigte Informationen aufnehmen. Unbekannte Angaben als
> `[NOCH NICHT FESTGELEGT]` oder `[NOCH ZU KLÄREN]` kennzeichnen.

## Technology Stack

* Programmiersprache: PHP
* Framework: Laravel
* Aktuell eingesetzte Version: Laravel 11
* PHP-Anforderung des Projekts: `^8.2`
* Die Anwendung ist als CLI-Anwendung ausgelegt.

## Database

* Als Datenbank wird MariaDB verwendet.
* Die MariaDB-Datenbank läuft aktuell in der lokalen Entwicklungsumgebung über Docker.
* Das Datenbankschema wird zusätzlich in `.ai-docs/databaseSchema.sql` dokumentiert.
* Das Projekt verwendet keine Laravel-Migrations zur Pflege des Datenbankschemas.
* Wenn für eine Änderung SQL-Anpassungen erforderlich sind, werden die SQL-Statements bereitgestellt und manuell ausgeführt.

## Execution Environment

* Die Anwendung wird für die persönliche Nutzung lokal auf dem eigenen Rechner ausgeführt.
* Die vorgesehene Laufzeitumgebung ist WSL.
* Die lokale MariaDB wird über Docker bereitgestellt.

## External Services

### Email

* Der E-Mail-Versand erfolgt über einen SMTP-Server.
* Für die persönliche Nutzung wird die E-Mail-Infrastruktur von `xovatec.de` beim Webhoster All-Inkl verwendet.
* Zugangsdaten und sonstige Secrets werden nicht in der Memory Bank dokumentiert.

## Development and Quality Assurance

* Das Projekt wird über GitHub verwaltet.
* Ein GitHub Workflow prüft den Code automatisiert und führt Tests aus.
* PHPUnit, PHPStan, Larastan und Laravel Pint sind aktuell als Entwicklungsabhängigkeiten vorhanden.
* Diese Entwicklungswerkzeuge stammen in erster Linie aus der ursprünglichen Laravel-Installation und stellen derzeit keine bewusst festgelegte langfristige Werkzeugauswahl dar.
* Eine zukünftige Änderung dieser Werkzeuge ist `[NOCH NICHT FESTGELEGT]`.
* Neue Unit-Tests sind für Änderungen grundsätzlich nicht erforderlich.
* Bereits vorhandene Tests sollen bei Änderungen berücksichtigt und bei Bedarf angepasst werden, wenn sie durch die Änderung betroffen sind.

## Version and Update Strategy

* Vor der persönlichen Nutzung soll das Projekt einmalig auf aktuelle stabile Versionen von PHP, Laravel und den verwendeten Paketen aktualisiert werden.
* Die konkreten Zielversionen sind `[NOCH NICHT FESTGELEGT]`.
* Nach dieser Aktualisierung sind keine regelmäßigen Aktualisierungen vorgesehen.
* Aktualisierungen sollen bei Bedarf erfolgen, insbesondere bei der Entwicklung neuer Funktionen oder bei bekannt gewordenen Sicherheitslücken.