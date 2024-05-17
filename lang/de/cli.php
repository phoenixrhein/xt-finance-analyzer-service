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
            'message' => 'Es ist ein technischer Fehler aufgetreten: :error'
        ]
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
                    'mail' => 'E-Mail'
                ]
            ]
        ],
        'delete' => [
            'description' => 'Benutzer löschen',
            'confirm_question' => 'Wollen Sie wirklich den User \':mail\' löschen?',
            'question_delete_accounts' => 'Es wurden Bankkonten gefunden, mit denen kein anderer Benutzer verknüpft ist. Sollen diese auch gelöscht werden?',
            'error' => [
                'not_found' => 'Der Benutzer mit der ID \':userId\' wurde nicht gefunden'
            ],
            'deleted' => 'Der Benutzer mit der ID \':userId\' (:mail) wurde gelöscht'
        ]
    ],
    'account' => [
        'list' => [
            'table' => [
                'columns' => [
                    'id' => 'ID',
                    'iban' => 'IBAN',
                    'bic' => 'BIC'
                ]
            ]
        ]
    ]
];
