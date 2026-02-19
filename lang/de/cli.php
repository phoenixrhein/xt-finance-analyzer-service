<?php

return [
    'base' => [
        'button' => [
            'create' => 'Anlegen',
            'abort' => 'Abbrechen',
            'yes' => 'Ja',
            'no' => 'Nein',
            'ok' => 'Ok'
        ],
        'error' => [
            'message' => 'Es ist ein technischer Fehler aufgetreten [Msg-Id: :msgId]',
            'not_found_user' => 'Der Benutzer mit der ID \':userId\' wurde nicht gefunden',
            'not_found_account' => 'Das Bankkonto mit der ID \':accountId\' wurde nicht gefunden',
            'not_found' => 'Der Eintrag mit der ID \':id\' wurde nicht gefunden',
            'not_rows_found' => 'Es wurden keine Einträge gefunden'
        ],
        'param' => [
            'user_id' => 'ID des Benutzers',
            'account_id' => 'ID des Bankkontos',
            'rule_id' => 'ID der Regel',
            'force_delete' => 'Löschen erzwingen',
            'consider_ignore_ibans' => 'Die Ignore-IBANs berücksichtigen'
        ],
        'confirm_save' => 'Sind alle Daten korrekt?',
        'created' => 'Eintrag erfolgreich angelegt [Id: :id]',
        'edited' => 'Der Eintrag mit der ID \':id\' wurde aktualisiert',
        'deleted' => 'Der Eintrag mit der ID \':id\' wurde gelöscht',
        'iban' => 'IBAN',
        'upsert_hint_add' => 'Es wurde keine ID übergeben. Der Eintrag wird daher neu angelegt',
        'halt' => 'Drücken Sie die Eingabetaste, um fortzufahren....',
        'payee' => 'Zahlungsempfänger',
        'value' => 'Wert',
        'count' => 'Anzahl',
        'amount' => 'Betrag',
        'rules' => 'Regeln',
        'cashflow_in' => 'Einnahmen',
        'cashflow_out' => 'Ausgaben',
    ],
    'service' => [
        'cli_error_highlighter' => [
            'no_error_found' => 'Kein Fehler gefunden',
            'error_report_title' => 'Fehlerhafte Expression',
            'details' => 'Details'
        ]
    ],
    'fin_query' => [
        'parser' => [
            'error' => [
                'invalid_field' => 'Ungültiges Feld. Gültige Felder sind: :fields',
                'invalid_operator' => 'Ungültiger Operator. Gültige Operatoren sind: :operators',
                'invalid_value' => 'Ungültiger Wert: :error_message',
                'invalid_syntax' => 'Ungültige Syntax: :trimmedCondition',
                'invalid_option' => 'Ungültige Option. Gültige Optionen sind: :options',
                'close_bracket_missing' => 'Schließende Klammer fehlt',
                'invalid_bracket' => 'Nicht alle Klammern sind korrekt',
                'invalid_single_quote' => 'Ungültige Anzahl von Hochkommas',
                'invalid_logical_operator' => 'Ungültiger logischer Operator. Gültige Operatoren sind: :operators',
            ]
        ]
            ],
    'view' => [
        'input' => [
            'iban' => 'Bitte geben Sie eine IBAN ein',
        ],
        'validate_error' => [
            'duplicate_iban' => 'Die IBAN existiert bereits'
        ],
        'find_and_select_transaction' => [
            'description' => 'Suche und Auswahl einer Buchung',
            'month_input' => 'Bitte geben Sie dazu einen Monat ein [MMJJJJ]',
            'search' => 'Sucheingabe [mind. 3 Zeichen]',
            'search_by_text' => 'Mit diesem Text suchen',
            'transaction_id_input' => 'Bitte geben Sie die Transactions-Id an',
            'transaction_id_input_hint' => 'Sofern Sie die Suche erneut starten möchten, lassen Sie dieses Feld leer',
        ],
        'condition_creator' => [
            'option_and_link' => 'Weitere Bedingung mit UND-Verknüpfung',
            'option_or_link' => 'Weitere Bedingung mit ODER-Verknüpfung',
            'option_no_more_condition' => 'Keine weitere Bedingung',
            'option_modify_condition' => 'Letzte Bedingung erneut anpassen',
            'select_field' => 'Feld für die Bedingung auswählen',
            'select_operator' => 'Vergleichs-Operator auswählen',
            'value_input' => 'Wert eingeben',
            'value_select' => 'Wert auswählen',
            'confirm_condition' => 'Ist die Bedingung korrekt?',
            'further_condition' => 'Weitere Bedingung hinzufügen?',
            'option_more_data' => 'Weitere Treffer anzeigen',
            'warning_overlap_matches' => 'Es gibt Überschneidungen mit mindestens einer weiteren Regel',
            'operator' => [
                'equal' => 'Gleich',
                'not_equal' => 'Ungleich',
                'greater_than' => 'Größer als',
                'less_than' => 'Kleiner als',
                'contains' => 'Enthält',
                'starts_with' => 'Beginnt mit',
                'ends_with' => 'Endet mit',
                'not_contains' => 'Enthält nicht',
                'not_starts_with' => 'Beginnt nicht mit',
                'not_ends_with' => 'Endet nicht mit'
            ],
        ],
        'fin_query_creator' => [
            'input' => 'FinQuery eingeben',
            'confirm_condition' => 'Ist die FinQuery-Bedingung korrekt?',
        ],
        'display_interim_results' => [
            'more_matches_found' => 'weitere(r) Treffer gefunden',
            'no_matches_found' => 'Keine Treffer gefunden'
        ]
    ],
    'param' => [
        'date_range' => [
            'description' => 'Zeitraum: JJJJ-JJJJ, JJJJMM-JJJJMM, JJJJMMDD-JJJJMMDD, JJJJ, JJJJMM, JJJJMMDD',
            'error' => [
                'time_period_invalid' => 'Zeitraum: Das Format des Zeitraums ist ungültig [JJJJ-JJJJ, JJJJMM-JJJJMM, JJJJMMDD-JJJJMMDD, JJJJ, JJJJMM, JJJJMMDD]',
                'not_start_before_end' => 'Zeitraum: Das Enddatum darf nicht vor dem Startdatum liegen'
            ],
            'duration' => 'Zeitraum'
        ]
    ],
    'transaction_split' => [
        'base' => [
            'param' => [
                'transaction_split_id' => 'ID des Buchungsaufteilung'
            ]
        ],
        'upsert' => [
            'description' => 'Buchungsaufteilung hinzufügen/bearbeiten',
            'note' => 'Bitte geben Sie einen Kommentar ein',
            'new_amount' => 'Bitte geben Sie den Betrag an',
            'validate_error' => [
                'no_more_split_allowed' => 'Der Restbetrag kann nicht mehr aufgeteilt werden',
                'total_amount_exceeded' => 'Der Restbetrag von :rest wurde überschritten'
            ],
            'available_remaining_amount' => 'Verfübarer Restbetrag: :rest'
        ],
        'list' => [
            'description' => 'Buchungsaufteilungen anzeigen',
            'table_header' => [
                'id' => 'ID',
                'note' => 'Grund',
                'split_amount' => 'Aufgeteilter Betrag',
                'total_amount' => 'Gesamtbetrag'
            ]
        ],
        'delete' => [
            'description' => 'Buchungsaufteilung-Eintrag löschen',
            'confirm' => 'Wollen Sie den Eintrag mit der ID \':id\' [Kommentar: :comment] wirklich löschen?'
        ],
        'cash_detector' => [
            'description' => 'Bargeldauszahlung bei Kartenzahlung ermitteln',
            'amount' => 'Bargeldauszahlungsbetrag',
            'amount_note' => 'Das Feld leer lassen, wenn keine Bargeldauszahlung übernommen werden soll',
            'confirm' => 'Der Wert weicht vom ermittelten Wert ab. Dennoch speichern?',
            'note' => 'Automatische Ermittlung der Bargeldauszahlung'
        ]
    ],
    'transaction_adjustment' => [
        'base' => [
            'param' => [
                'transaction_adjustment_id' => 'ID des Buchungsverschiebung'
            ]
        ],
        'upsert' => [
            'description' => 'Buchungsverschiebung hinzufügen/bearbeiten',
            'new_date' => 'Bitte geben Sie das Buchungsdatum ein [dd.mm.jjjj]',
            'note' => 'Bitte geben Sie einen Kommentar ein',
            'validate_error' => [
                'duplicate' => 'Für diese Tranaktion besteht bereits ein Eintrag [Id: :id]'
            ],
        ],
        'list' => [
            'description' => 'Buchungsverschiebungen anzeigen',
            'table_header' => [
                'id' => 'ID',
                'note' => 'Kommentar',
                'new_date' => 'Neues Buchungsdatum'
            ]
        ],
        'delete' => [
            'description' => 'Bargeldeinzahlungs-Eintrag löschen',
            'confirm' => 'Wollen Sie den Eintrag mit der ID \':id\' [Kommentar: :comment] wirklich löschen?'
        ],
    ],
    'cash_deposit' => [
        'base' => [
            'param' => [
                'cash_deposit_id' => 'ID des Bargeldeinzahlungseintrag'
            ]
        ],
        'upsert' => [
            'description' => 'Bargeldeinzahlung hinzufügen/bearbeiten',
            'amount' => 'Bitte geben Sie den Bareinzahlungsbetrag ein',
            'currency' => 'Bitte geben Sie die Währung ein',
            'deposit_date' => 'Bitte geben Sie die Einzahlungsdatum ein [dd.mm.jjjj]',
            'note' => 'Bitte geben Sie einen Kommentar ein',
        ],
        'delete' => [
            'description' => 'Bargeldeinzahlungseintrag-Eintrag löschen',
            'confirm' => 'Wollen Sie den Eintrag mit der ID \':id\' [Kommentar: :comment] wirklich löschen?'
        ],
        'list' => [
            'description' => 'Bargeldeinzahlungsliste anzeigen',
            'table_header' => [
                'id' => 'ID',
                'iban' => 'IBAN',
                'cash_deposit_date' => 'Annahmedatum',
                'amount' => 'Betrag',
                'currency' => 'Währung',
                'note' => 'Kommentar'
            ]
        ],
    ],
    'user' => [
        'add' => [
            'description' => 'Neuen Benutzer anlegen',
            'input_email' => 'Bitte geben Sie eine E-Mail-Adresse ein',
            'validate_error' => [
                'duplicate_email' => 'Die E-Mail-Adresse existiert bereits'
            ],
            'confirm' => 'Möchten Sie den Benutzer anlegen?',
            'created' => 'Benutzer erfolgreich angelegt. [Email: :mail / Id: :id]'
        ],
        'list' => [
            'description' => 'Zeigt eine Liste aller Benutzer oder die Details zu einem Benutzer',
            'details_title' => 'Benutzerdetails',
            'linked_account_title' => 'Verknüpfte Bankkonten',
            'table' => [
                'columns' => [
                    'id' => 'ID',
                    'mail' => 'E-Mail',
                    'count_accounts' => 'Anzahl der Konten'
                ]
            ],
            'param' => [
                'user_id' => 'ID des Benutzers um Details anzuzeigen',
            ],
        ],
        'delete' => [
            'description' => 'Benutzer löschen',
            'confirm_question' => 'Wollen Sie wirklich den User \':mail\' löschen?',
            'question_delete_accounts' => 'Es wurden Bankkonten gefunden, mit denen kein anderer Benutzer verknüpft ist. Sollen diese auch gelöscht werden?',
            'deleted' => 'Der Benutzer mit der ID \':userId\' (:mail) wurde gelöscht'
        ],
        'edit' => [
            'description' => 'Benutzer bearbeiten',
            'input_email' => 'Bitte geben Sie eine E-Mail-Adresse ein',
            'updated' => 'Benutzer erfolgreich aktualisiert. [Email: :mail / Id: :id]'
        ],
        'addAccount' => [
            'description' => 'Benutzer mit Konto verknüpfen',
            'error' => [
                'duplicate' => 'Die Verknüpfung existiert bereits'
            ],
            'added' => 'Zugewiesener Benutzer mit der ID \':userId\' zum Bankkonto mit der ID \':accountId\''
        ],
        'detachAccount' => [
            'description' => 'Benutzer von Konto lösen',
            'error' => [
                'not_found' => 'Die Verknüpfung existiert nicht'
            ],
            'detached' => 'Verknüpfung zwischen dem Benutzer mit der ID \':userId\' und dem Bankkonto mit der ID \':accountId\' wurde entfernt'
        ]
    ],
    'account' => [
        'base' => [
            'param' => [
                'account_id' => 'ID des Bankkontos'
            ],
            'error'=> [
                'not_found_account_id' => 'Das Bankkonto mit der ID \':accountId\' wurde nicht gefunden'
            ]
        ],
        'delete' => [
            'description' => 'Bankkonto löschen',
            'confirm_question' => 'Möchten Sie das Bankkonto mit der IBAN \':iban\' wirklich löschen?',
            'question_delete_users' => 'Es wurden Benutzer gefunden, mit denen kein weiteres Bankkonto verknüpft ist. Sollen diese auch gelöscht werden?',
            'deleted' => 'Das Bankkonto mit der ID \':accountId\' (:iban) wurde gelöscht'
        ],
        'list' => [
            'description' => 'Bankkonton anzeigen',
            'details_title' => 'Bankkontodetails',
            'linked_user_title' => 'Verknüpfte Benutzer',
            'table' => [
                'columns' => [
                    'id' => 'ID',
                    'iban' => 'IBAN',
                    'bic' => 'BIC',
                    'count_users' => 'Anzahl der Benutzer'
                ]
            ]
        ],
        'add' => [
            'description' => 'Bankkonto anlegen',
            'input_bic' => 'Bitte geben Sie eine BIC ein',
            'confirm' => 'Möchten Sie das Bankkonto anlegen?',
            'created' => 'Bankkonto erfolgreich angelegt. [IBAN: :iban / Id: :id]'
        ],
        'edit' => [
            'description' => 'Bankkonto bearbeiten',
            'edited' => 'Das Bankkonto mit der ID \':accountId\' wurde aktualisiert'
        ]
    ],
    'category' => [
        'base' => [
            'error'=> [
                'not_found_cashflow' => 'Der Zahlungsstrom zu der Bank-Account-ID \':bankAccountId\' wurde nicht gefunden',
                'already_exist' => 'Eine Kategorie mit dem Namen existiert bereits auf der Ebene'
            ],
            'category_path' => 'Kategoriepfad',
            'input_name' => 'Bitte geben Sie einen Namen ein',
            'input_parent_id' => 'Bitte geben Sie die ID der übergeordneten Kategorie ein',
            'select_category' => 'Bitte wählen Sie eine Kategorie aus',
        ],
        'manage' => [
            'description' => 'Kategorien verwalten',
            'select_action' => 'Bitte wählen Sie eine Option',
            'action' => [
                'add' => 'Kategorie anlegen',
                'edit' => 'Kategorie bearbeiten',
                'delete' => 'Kategorie löschen',
                'finish' => 'Beenden'
            ],
        ],
        'add' => [
            'created' => 'Kategorie erfolgreich angelegt [Name: :name / Id: :id]'
        ],
        'edit' => [
            'edited' => 'Die Kategorie mit der ID \':categoryId\' wurde aktualisiert'
        ],
        'list' => [
            'description' => 'Liste der Kategorien des Zahlungsstroms',
        ],
        'delete' => [
            'confirm_question' => 'Möchten Sie die Kategorie \':name\' wirklich löschen?',
            'error' => [
                'has_childs' => 'Die Kategorie hat mindestens eine Unterkategorie',
                'category_is_cashflow' => 'Die Kategorie ist der Zahlungsstrom',
                'has_rules' => 'Die Kategorie ist mindestens einer Regel zugewiesen',
            ],
            'deleted' => 'Die Kategorie mit der ID \':categoryId\' (:name) wurde gelöscht'
        ]
    ],
    'ignore_list' => [
        'base' => [
            'param' => [
                'ignore_id' => 'ID des Ignore-Eintrags'
            ]
        ],
        'list' => [
            'description' => 'Ignorierliste anzeigen',
            'table_header' => [
                'id' => 'ID',
                'type' => 'Typ',
                'value' => 'Wert',
                'comment' => 'Kommentar'
            ]
        ],
        'upsert' => [
            'description' => 'Ignorierliste bearbeiten',
            'edit_bank_account_id' => 'Bitte geben Sie die ID des Bankkontos ein',
            'comment' => 'Kommentar'
        ],
        'delete' => [
            'description' => 'Ignorierliste-Eintrag löschen',
            'confirm' => 'Wollen Sie den Eintrag mit der ID \':id\' [Kommentar: :comment] wirklich löschen?'
        ]
    ],
    'transaction' => [
        'base' => [
            'param' => [
                'transaction_id' => 'ID der Buchung'
            ],
            'error'=> [
                'not_found_transaction_id' => 'Die Buchung mit der ID \':transactionId\' wurde nicht gefunden'
            ],
            'table' => [
                'header' => [
                    'id' => 'Id',
                    'created_at' => 'Importiert am',
                    'bank_account_iban' => 'Auftragskonto',
                    'transaction_date' => 'Buchungstag',
                    'exchange_date' => 'Valutadatum',
                    'transaction_type' => 'Buchungstext',
                    'reason_for_payment' => 'Verwendungszweck',
                    'creditor_id' => 'Glaeubiger ID',
                    'mandate_ reference' => 'Mandatsreferenz',
                    'customer_reference' => 'Kundenreferenz (End-to-End)',
                    'collector_reference' => 'Sammlerreferenz',
                    'debit_original_amount' => 'Lastschrift Ursprungsbetrag',
                    'reimbursement_of_expenses_return_debit' => 'Auslagenersatz Ruecklastschrift',
                    'beneficiary_payee' => 'Beguenstigter/Zahlungspflichtiger',
                    'creditor_iban' => 'Kontonummer/IBAN',
                    'creditor_bic' => 'BIC (SWIFT-Code)',
                    'amount' => 'Betrag',
                    'currency' => 'Währung',
                    'note' => 'Anmerkung'
                ]
            ]
        ],
        'comment' => [
            'description' => 'Kommentar bearbeiten',
            'input_note' => 'Bitte geben Sie einen Kommentar an',
            'edited' => 'Der Kommentar wurde geändert'
        ],
        'import' => [
            'param' => [
                'file' => 'Zu importierende Datei',
                'lastMonths' => 'Anzahl der letzten zu importierenden Monate. 0 bedeutet uneingeschränkt alles',
                'ignoreAlreadyExists' => 'Ignoriert bereits vorhandene Einträge. Ansonsten wird der Import abgebrochen'
            ],
            'description' => 'Buchungen importieren',
            'input' => [
                'lastmonths' => [
                    'text' => 'Anzahl der letzten zu importierenden Monate',
                    'hint' => '0 bedeutet uneingeschränkt alles. Z.B. 1 bedeutet nur den letzten Monat'
                ],
                'ignoreAlreadyExists' => 'Sollen Duplikate ignoriert werden?',
                'ignoreAlreadyExists_hint' => 'Ansonsten bricht das Skript beim ersten Duplikat ab'
            ],
            'validate' => [
                'file_not_found' => 'Die Datei wurde nicht gefunden: :file',
                'is_no_file' => 'Pfad ist keine Datei: :file',
                'file_not_readable' => 'Keine Leserechte für die Datei: :file',
            ],
            'error' => [
                'different_accounts' => 'Unterschiedliche Konten in der Importdatei. Erwartet: :expected / Erhalten: :get'
            ],
            'refresh_index' => 'Aktualisiere den Regel-Buchungs-Index...'
        ],
        'list' => [
            'description' => 'Buchungen anzeigen',
            'query_type' => [
                'title' => 'Abfrage-Typ für den Zeitraum',
                'options' => [
                    'all' => 'Alle anzeigen',
                    'manual' => 'Auswahl manuell einschränken',
                    'fin_query' => 'Auswahl mit FinQuery einschränken'
                ]
            ]
        ]
    ],
    'rule' => [
        'assign' => [
            'description' => 'Regel zuweisen',
            'select_more_data_or_add_rule' => [
                'text' => 'Weitere anzeigen oder ein Regel anlegen',
                'options' => [
                    'add_rule' => 'Regel anlegen',
                    'more_data' => 'Weitere anzeigen'
                ]
            ],
            'count_unmatched_transactions' => 'Anzahl der nicht kategorisierten Transaktionen: :count',
            'total_found' => 'Gesamtanzahl: :count',
            'found_already_matched' => 'Es werden :count bereits kategorisierte Transaktionen gefunden. Diese sind mit gelber Schrift markiert',
            'cat_mgmt_continue_button_text' => 'Kategorie auswählen',
            'result' => [
                'info' => 'Die Regel mit der ID \':id\' wurde erfolgreich angelegt',
                'error' => 'Beim Speichern der Regel ist ein Fehler aufgetreten',
            ]
        ],
        'list' => [
            'description' => 'Regeln anzeigen',
            'table_header' => [
                'id' => 'ID',
                'name' => 'Name',
                'expression' => 'Expression',
                'category' => 'Kategorie'
            ]
        ],
        'delete' => [
            'description' => 'Regel löschen',
            'confirm' => 'Wollen Sie die Regel mit der ID \':id\' wirklich löschen?',
            'deleted' => 'Die Regel mit der ID \':ruleId\' wurde gelöscht',
            'error' => [
                'not_found' => 'Die Regel mit der ID \':ruleId\' wurde nicht gefunden'
            ],
            'success' => 'Die Regel mit der ID \':ruleId\' wurde gelöscht'
        ],
        'refresh_index' => [
            'description' => 'Regel-Index aktualisieren',
            'not_considering_ignore_ibans' => 'Die Aktualisierung des Indexes berücksichtigt nicht die Ignore-IBANs',
            'summary' => 'Es wurden :total Regeln verarbeitet. :updated Regeln aktualisiert, :zero Regeln ohne Treffer.',
            'starts' => 'Beginne mit der Aktualisierung des Regel-Index...'
        ],
        'validator' => [
            'description' => 'Regel validieren',
            'not_considering_ignore_ibans' => 'Die Validierung der Regeln berücksichtigt nicht die Ignore-IBANs',
            'validate_assignments' => 'Validierung der Buchungen für das Bankkonto: :iban (:id)... ',
            'validate_assignments_finished' => 'Beendet',
            'validate_assignments_overlaps' => 'Buchung mit der ID :id würde mehreren Regeln zugeordnet: :ruleIds',
        ]
    ],
    'report' => [
        'description' => 'Finanzbericht generieren',
        'type' => [
            'monthly' => 'Monatlich',
            'yearly' => 'Jährlich'
        ],
        'select_type' => 'Bitte wählen Sie den Berichtstyp aus',
        'input_target' => [
            'month' => [
                'label' => 'Bitte geben Sie den Monat ein [JJJJMM]',
                'hint' => 'Format: JJJJMM, z.B. 202403 für März 2024'
            ],
            'year' => [
                'label' => 'Bitte geben Sie das Jahr ein [JJJJ]',
                'hint' => 'Format: JJJJ, z.B. 2024'
            ],
            'error' => [
                'incomplete_data' => 'Der ausgewählte Zeitraum ist unvollständig. Bitte stellen Sie sicher, dass alle Buchungen bis zum Ende des Zeitraums vorliegen.',
                'unknown_report_type' => 'Unbekannter Berichtstyp'
            ]
        ],
        'select_time_span' => [
            'label' => 'Wie viele vorausgegangene :span sollen berücksichtigt werden?',
            'only_current_month' => 'Nur aktueller Monat',
            'only_current_year' => 'Nur aktuelles Jahr',
            'time_span_options' => [
                '0' => 'Keine (:only_current)',
                '1' => '1 :span',
                '2' => '2 :span',
                '3' => '3 :span'
            ],
            'span' => [
                'months' => 'Monate',
                'years' => 'Jahre',
                'month' => 'Monat',
                'year' => 'Jahr'
            ]
        ]
    ]
];
