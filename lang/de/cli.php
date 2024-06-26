<?php

use function PHPSTORM_META\map;

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
        ],
        'param' => [
            'user_id' => 'ID des Benutzers',
            'account_id' => 'ID des Bankkontos'
        ],
        'confirm_save' => 'Sie alle Daten korrekt?',
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
            'input_iban' => 'Bitte geben Sie eine IBAN ein',
            'input_bic' => 'Bitte geben Sie eine BIC ein',
            'validate_error' => [
                'duplicate_iban' => 'Die IBAN existiert bereits'
            ],
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
    ]
];
