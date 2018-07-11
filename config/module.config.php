<?php
return [
    'view_manager' => [
        'template_path_stack' => [
            OMEKA_PATH . '/modules/Timestamp/view',
        ],
    ],
    'entity_manager' => [
        'functions' => [
            'string' => [
                'cast_signed' => Timestamp\Query\CastSigned::class,
            ],
        ]
    ],
    'data_types' => [
        'invokables' => [
            'timestamp' => Timestamp\DataType\Timestamp::class,
        ],
    ],
];
