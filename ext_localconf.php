<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function()
    {

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'SimpleDiscussion',
            'Plugin',
            [
                \Gmf\SimpleDiscussion\Controller\CommentController::class => 'list, create, edit, update'
            ],
            // non-cacheable actions
            [
                \Gmf\SimpleDiscussion\Controller\CommentController::class => 'create, update'
            ]
        );

        // wizards
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
            'mod {
                wizards.newContentElement.wizardItems.plugins {
                    elements {
                        plugin {
                            iconIdentifier = simple_discussion-icon
                            title = LLL:EXT:simple_discussion/Resources/Private/Language/locallang_db.xlf:tx_simple_discussion_plugin.name
                            description = LLL:EXT:simple_discussion/Resources/Private/Language/locallang_db.xlf:tx_simple_discussion_plugin.description
                            tt_content_defValues {
                                CType = list
                                list_type = simplediscussion_plugin
                            }
                        }
                    }
                    show = *
                }
           }'
        );
		$iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);

			$iconRegistry->registerIcon(
				'simple_discussion-icon',
				\TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
				['source' => 'EXT:simple_discussion/Resources/Public/Icons/Plugin.svg']
			);

    }
);
