<?php

return [
    'products' => [
        'hushcut' => [
            'name' => 'HushCut',
            'version' => '1.1.15',
            'url' => 'https://drive.google.com/uc?export=download&id=1BmzujnAqQEvMdI_S_sfOKIFlE6upzmEC',
            'variants' => [
                'mac' => [
                    'premiere' => 'https://drive.google.com/uc?export=download&id=1BmzujnAqQEvMdI_S_sfOKIFlE6upzmEC',
                    'resolve' => 'https://drive.google.com/uc?export=download&id=1RlWulenPnco7aJ-lCz0_qHwWJ4bkdA44',
                ],
            ],
        ],
        'babelcut' => [
            'name' => 'BabelCut',
            'version' => '1.1.3',
            'url' => 'https://drive.google.com/uc?export=download&id=1Je8x19p-yHetsqVOu2kY8vVmuoCKvE1Y',
            'variants' => [
                'mac' => [
                    'premiere' => 'https://drive.google.com/uc?export=download&id=1Je8x19p-yHetsqVOu2kY8vVmuoCKvE1Y',
                ],
            ],
        ],
        'bundle' => [
            'name' => 'Studio Pass (HushCut + BabelCut)',
            'version' => '1.1.15 + 1.1.3',
            'url' => 'https://labs.lensmania.ae/dashboard',
            'includes' => ['hushcut', 'babelcut'],
        ],
    ],
];
