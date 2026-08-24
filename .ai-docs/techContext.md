# Technical Context

## 1. Core Stack
- **Sprache/Runtime:** PHP 8.x
- **Architektur:** [z.B. Modularer Aufbau / Vanilla / Micro-Framework]
- **Datenbank:** MariaDB (über Docker)
  - **Schema-Datei:** `.ai-docs/databaseSchema.sql` (bzw. `.md`) – Dient als Single Source of Truth für Tabellenstrukturen.

## 2. Umgebung & Entwicklung
- **Lokales System:** WSL Ubuntu in VS Code
- **Containerisierung:** Docker Compose (Nginx, PHP-FPM, MariaDB)
- **Coding Standards:** PSR-12, striktes Typpüng (`declare(strict_types=1);`)
- **Testing:** [z.B. PHPUnit, falls im Einsatz]