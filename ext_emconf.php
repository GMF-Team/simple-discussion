<?php

/***************************************************************
 * Extension Manager/Repository config file for ext: "simple_discussion"
 *
 * Manual updates:
 * Only the data in the array - anything else is removed by next write.
 * "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = [
    'title' => 'GMF Simple discussion',
    'description' => 'Simple discussion system ',
    'category' => 'plugin',
    'author' => 'Team GMF',
    'author_email' => 'teamonline@gmf-design.de',
    'state' => 'alpha',
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.0-10.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
