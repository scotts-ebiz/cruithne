<?php
return [
    'backend' => [
        'frontName' => 'admin_1j9rrk'
    ],
    'db' => [
        'connection' => [
            'indexer' => [
                'host' => '10.55.32.6',
                'dbname' => 'magento',
                'username' => 'magento',
                'password' => '4IBlhtLk9!0LS!Aup5GW',
                'model' => 'mysql4',
                'engine' => 'innodb',
                'initStatements' => 'SET NAMES utf8;',
                'active' => '1',
                'persistent' => NULL
            ],
            'default' => [
                'host' => '10.55.32.6',
                'dbname' => 'magento',
                'username' => 'magento',
                'password' => '4IBlhtLk9!0LS!Aup5GW',
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
        'save' => 'redis',
        'redis' => [
            'host' => '10.0.1.3',
            'database' => '2',
            'password' => '',
            'timeout' => '2.5',
            'persistent_identifier' => '',
            'compression_threshold' => '2048',
            'compression_library' => 'gzip',
            'max_concurrency' => '6',
            'break_after_frontend' => '5',
            'break_after_adminhtml' => '30',
            'first_lifetime' => '600',
            'bot_first_lifetime' => '60',
            'bot_lifetime' => '7200',
            'disable_locking' => '0',
            'min_lifetime' => '60',
            'max_lifetime' => '2592000',
            'log_level' => '3'
        ]
    ],
    'cache_types' => [
        'config' => 1,
        'layout' => 1,
        'block_html' => 1,
        'collections' => 1,
        'reflection' => 1,
        'db_ddl' => 1,
        'compiled_config' => 1,
        'eav' => 1,
        'customer_notification' => 1,
        'config_integration' => 1,
        'config_integration_api' => 1,
        'target_rule' => 1,
        'full_page' => 1,
        'config_webservice' => 1,
        'translate' => 1,
        'vertex' => 1
    ]
];
