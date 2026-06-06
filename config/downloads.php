<?php

return [
    'products' => [
        'hushcut' => [
            'name' => 'HushCut',
            'version' => '1.1.14',
            'url' => 'https://drive.google.com/uc?export=download&id=15DeJQ9yKsdJDdtAIUTTxsFewAxgf8ClU',
            'variants' => [
                'mac' => [
                    'premiere' => 'https://drive.google.com/uc?export=download&id=15DeJQ9yKsdJDdtAIUTTxsFewAxgf8ClU',
                    'resolve' => 'https://drive.google.com/uc?export=download&id=1U9MSH6iam5EtQT8CvrMe9DwqPLeklGjy',
                ],
                'windows' => [
                    'premiere' => 'https://drive.google.com/uc?export=download&id=16i5W1ImSJVHNXA5IfqjEdjgEVpSoJj9W',
                ],
            ],
        ],
        'babelcut' => [
            'name' => 'BabelCut',
            'version' => '1.1.2',
            'url' => 'https://drive.google.com/uc?export=download&id=1dSJ-ysINEZBn3QztNkdwgQSRS77-Enbd',
            'variants' => [
                'mac' => [
                    'premiere' => 'https://drive.google.com/uc?export=download&id=1dSJ-ysINEZBn3QztNkdwgQSRS77-Enbd',
                ],
            ],
        ],
        'bundle' => [
            'name' => 'Studio Pass (HushCut + BabelCut)',
            'version' => '1.1.14 + 1.1.2',
            'url' => 'https://labs.lensmania.ae/dashboard.html',
            'includes' => ['hushcut', 'babelcut'],
        ],
    ],
];
