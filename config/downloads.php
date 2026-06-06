<?php

return [
    'products' => [
        'hushcut' => [
            'name' => 'HushCut',
            'version' => '1.1.14',
            'url' => 'https://drive.google.com/uc?export=download&id=1fv1FU78a3i6kyQti6GENDWykJKhBySHw',
            'variants' => [
                'mac' => [
                    'premiere' => 'https://drive.google.com/uc?export=download&id=1fv1FU78a3i6kyQti6GENDWykJKhBySHw',
                    'resolve' => 'https://drive.google.com/uc?export=download&id=1l91XZM9SBrxiooS7ps3oEfqx3kvLnAXx',
                ],
                'windows' => [
                    'premiere' => 'https://drive.google.com/uc?export=download&id=1JyzvzY_eTcl3YNwBJuHEWcEuVHRroqHX',
                ],
            ],
        ],
        'babelcut' => [
            'name' => 'BabelCut',
            'version' => '1.1.2',
            'url' => 'https://drive.google.com/uc?export=download&id=1V5Vv6OcKLxdoxFbdrBswkHhfIRiw8QTm',
            'variants' => [
                'mac' => [
                    'premiere' => 'https://drive.google.com/uc?export=download&id=1V5Vv6OcKLxdoxFbdrBswkHhfIRiw8QTm',
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
