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
            'confirm_save' => 'Sie alle Daten korrekt?',
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
        'list' => [
            'table' => [
                'columns' => [
                    'id' => 'ID',
                    'iban' => 'IBAN',
                    'bic' => 'BIC'
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
    ]
];
