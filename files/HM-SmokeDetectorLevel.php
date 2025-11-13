<?php

declare(strict_types=1);

$setting = [
    'ProfileName'  => 'HM.SmokeDetectorLevel',
    'ProfileType'  => VARIABLETYPE_FLOAT,
    'Icon'         => 'Intensity',
    'Prefix'       => '',
    'Suffix'       => ' %',
    'MinValue'     => 0.0,
    'MaxValue'     => 1.0,
    'StepSize'     => 0.0,
    'Digits'       => 0,
    'Associations' => [
    ],
    'IsReadOnly' => false,
];

echo json_encode($setting);
