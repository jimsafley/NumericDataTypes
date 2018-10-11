<?php
return [
    'view_manager' => [
        'template_path_stack' => [
            OMEKA_PATH . '/modules/Timestamp/view',
        ],
    ],
    'entity_manager' => [
        'mapping_classes_paths' => [
            OMEKA_PATH . '/modules/Timestamp/src/Entity',
        ],
        'proxy_paths' => [
            OMEKA_PATH . '/modules/Timestamp/data/doctrine-proxies',
        ],
    ],
    'data_types' => [
        'invokables' => [
            'timestamp' => Timestamp\DataType\Timestamp::class,
        ],
    ],
];
