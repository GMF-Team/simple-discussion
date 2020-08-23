<?php
defined('TYPO3_MODE') or die();

$pluginSignature = 'simplediscussion_plugin';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    $pluginSignature,
    'FILE:EXT:simple_discussion/Configuration/FlexForms/Registration.xml'
);
