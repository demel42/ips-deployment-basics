<?php

declare(strict_types=1);

// HmIP-DSBB
$setting = [
    'name' => 'Klingelsignalsensor',
    0      => [
        'INSTALL_TEST' => [
            'isHidden' => true,
        ],
        'LOW_BAT' => [
            'name'          => 'Batterie',
            'customProfile' => '~Battery',
            'doArchive'     => true,
        ],
        'OPERATING_VOLTAGE' => [
            'name'          => 'Versorgungsspannung',
            'customProfile' => '~Volt',
            'doArchive'     => true,
        ],
        'OPERATING_VOLTAGE_STATUS' => [
            'isHidden' => true,
        ],
        'RSSI_DEVICE' => [
            'doArchive' => true,
        ],
        'RSSI_PEER' => [
            'doArchive' => true,
        ],
        'UPDATE_PENDING' => [
            'isHidden' => true,
        ],
    ],
    1 => [
        'isMain'     => true,
        'PRESS_LONG' => [
            'isHidden' => true,
        ],
        'PRESS_SHORT' => [
            'isHidden' => true,
        ],
    ],
];

echo json_encode($setting);
