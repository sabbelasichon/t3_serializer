<?php

use Ssch\T3Serializer\Controller\TestController;

defined('TYPO3_MODE') || die('Access denied.');

if (!isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['t3_serializer']) || !is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['t3_serializer'])) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['t3_serializer'] = [
        'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
        'backend' => \TYPO3\CMS\Core\Cache\Backend\SimpleFileBackend::class,
        'options' => [
            'defaultLifetime' => 0,
        ],
        'groups' => ['system'],
    ];
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'T3Serializer',
    'Serializer',
    [
        TestController::class => 'index',
    ],
    [
        TestController::class => 'index',
    ]
);
