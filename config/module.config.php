<?php
return [
    'view_manager' => [
        'template_path_stack' => [
            OMEKA_PATH . '/modules/Timestamp/view',
        ],
    ],
    'data_types' => [
        'invokables' => [
            'timestamp' => Timestamp\DataType\Timestamp::class,
        ],
    ],
];
