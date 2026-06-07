<?php

return [
    'products' => [
        'hushcut' => [
            'name' => 'HushCut',
            'version' => '1.1.15',
            'url' => 'https://drive.usercontent.google.com/download?id=1BmzujnAqQEvMdI_S_sfOKIFlE6upzmEC&export=download&confirm=t',
            'variants' => [
                'mac' => [
                    'premiere' => 'https://drive.usercontent.google.com/download?id=1BmzujnAqQEvMdI_S_sfOKIFlE6upzmEC&export=download&confirm=t',
                    'resolve' => 'https://drive.usercontent.google.com/download?id=1RlWulenPnco7aJ-lCz0_qHwWJ4bkdA44&export=download&confirm=t',
                ],
            ],
        ],
        'babelcut' => [
            'name' => 'BabelCut',
            'version' => '1.1.3',
            'url' => 'https://drive.usercontent.google.com/download?id=1Je8x19p-yHetsqVOu2kY8vVmuoCKvE1Y&export=download&confirm=t',
            'variants' => [
                'mac' => [
                    'premiere' => 'https://drive.usercontent.google.com/download?id=1Je8x19p-yHetsqVOu2kY8vVmuoCKvE1Y&export=download&confirm=t',
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
