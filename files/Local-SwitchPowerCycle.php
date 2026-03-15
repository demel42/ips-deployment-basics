<?php

declare(strict_types=1);

$setting = [
    'ProfileName'  => 'Local.PowerCycle',
    'ProfileType'  => VARIABLETYPE_INTEGER,
    'Icon'         => 'arrows-spin',
    'Prefix'       => '',
    'Suffix'       => '',
    'MinValue'     => 0.0,
    'MaxValue'     => 0.0,
    'StepSize'     => 0.0,
    'Digits'       => 0,
    'Associations' => [
        0 => [
            'Value' => 0.0,
            'Name'  => '-',
            'Icon'  => '',
            'Color' => -1,
        ],
        1 => [
            'Value' => 1.0,
            'Name'  => 'Neustart',
            'Icon'  => '',
            'Color' => -1,
        ],
    ],
    'IsReadOnly' => false,
];

echo json_encode($setting);