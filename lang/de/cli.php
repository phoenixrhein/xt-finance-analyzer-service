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
            'message' => 'Es ist ein technischer Fehler aufgetreten: :error',
            'not_found_user' => 'Der Benutzer mit der ID \':userId\' wurde nicht gefunden',
            'not_found_account' => 'Das Bankkonto mit der ID \':accountId\' wurde nicht gefunden',
            'not_found' => 'Der Eintrag mit der ID \':id\' wurde nicht gefunden',
            'not_rows_found' => 'Es wurden keine Einträge gefunden'
        ],
        'param' => [
            'user_id' => 'ID des Benutzers',
            'account_id' => 'ID des Bankkontos'
        ],
        'confirm_save' => 'Sie alle Daten korrekt?',
        'created' => 'Eintrag erfolgreich angelegt [Id: :id]',
        'edited' => 'Der Eintrag mit der ID \':id\' wurde aktualisiert',
        'deleted' => 'Der Eintrag mit der ID \':id\' wurde gelöscht',
        'iban' => 'IBAN',
        'upsert_hint_add' => 'Es wurde keine ID übergeben. Der Eintrag wird daher neu angelegt',
        'halt' => 'Drücken Sie die Eingabetaste, um fortzufahren....'
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
            'param' => [
                'category_id' => 'ID des Kategorie',
                'cashflow_id' => 'ID des Zahlungsstroms'
            ],
            'error'=> [
                'not_found_category_id' => 'Die Kategorie mit der ID \':categoryId\' wurde nicht gefunden',
                'not_found_cashflow_id' => 'Der Zahlungsstrom mit der ID \':cashflowId\' wurde nicht gefunden'
            ],
            'category_path' => 'Kategoriepfad',
            'input_name' => 'Bitte geben Sie einen Namen ein',
            'input_parent_id' => 'Bitte geben Sie die ID der übergeordneten Kategorie ein',
        ],
        'add' => [
            'description' => 'Neue Kategorie anlegen',
            'confirm' => 'Möchten Sie die Kategorie anlegen?',
            'created' => 'Kategorie erfolgreich angelegt [Name: :name / Id: :id]'
        ],
        'edit' => [
            'description' => 'Kategorie bearbeiten',
            'edited' => 'Die Kategorie mit der ID \':categoryId\' wurde aktualisiert'
        ],
        'list' => [
            'description' => 'Liste der Kategorien des Zahlungsstroms',
        ],
        'delete' => [
            'description' => 'Kategorie löschen',
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
            ]
        ]
];
