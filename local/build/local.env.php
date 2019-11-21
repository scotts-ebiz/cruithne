<?php
return [
    'backend' => [
        'frontName' => 'admin_1j9rrk'
    ],
    'db' => [
        'connection' => [
            'indexer' => [
                'host' => 'local-mysql-service',
                'dbname' => 'magento',
                'username' => 'root',
                'password' => 'magento',
                'model' => 'mysql4',
                'engine' => 'innodb',
                'initStatements' => 'SET NAMES utf8;',
                'active' => '1',
                'persistent' => NULL
            ],
            'default' => [
                'host' => 'local-mysql-service',
                'dbname' => 'magento',
                'username' => 'root',
                'password' => 'magento',
                'model' => 'mysql4',
                'engine' => 'innodb',
                'initStatements' => 'SET NAMES utf8;',
                'active' => '1'
            ]
        ],
        'table_prefix' => ''
    ],
    'crypt' => [
        'key' => 'fb56ac6343bc5e4d56782b374d6ffbb0'
    ],
    'resource' => [
        'default_setup' => [
            'connection' => 'default'
        ]
    ],
    'x-frame-options' => 'SAMEORIGIN',
    'MAGE_MODE' => 'production',
    'session' => [
       'save' => 'files'
    ],
    'install' => [
        'date' => 'Wed, 28 Nov 2018 20:22:14 +0000'
    ]
];
