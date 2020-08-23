<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function()
    {

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            'SimpleDiscussion',
            'Plugin',
            'GMF Simple discussion'
        );

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('simple_discussion', 'Configuration/TypoScript', 'GMF Simple discussion');

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_simplediscussion_domain_model_comment', 'EXT:simple_discussion/Resources/Private/Language/locallang_csh_tx_simplediscussion_domain_model_comment.xlf');
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_simplediscussion_domain_model_comment');

    }
);
