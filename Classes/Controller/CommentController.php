<?php

namespace Gmf\SimpleDiscussion\Controller;

use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use Symfony\Component\Mime\Address;

/***
 *
 * This file is part of the "GMF Simple discussion" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2020 Team GMF <teamonline@gmf-design.de>, GMF
 *
 ***/
/**
 * CommentController
 */
class CommentController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{

    /**
     * Secure time to wait before sending form
     *
     * @var integer
     */
    private $secureTimeInSeconds = 5;

    /**
     * Activate Notice email
     *
     * @var boolean
     */
    private $noticeEmail = false;

    /**
     * Automatically hide
     *
     * @var boolean
     */
    private $autoHide = false;

    /**
     * Show or hide Reply Button
     *
     * @var boolean
     */
    private $allowReply = false;

    /**
     * If activated: email to
     *
     * @var string
     */
    private $emailTo = '';

    /**
     * If activated: email to name
     *
     * @var string
     */
    private $emailToName = '';

    /**
     * If activated: email from
     *
     * @var string
     */
    private $emailFrom = '';

    /**
     * If activated: email from name
     *
     * @var string
     */
    private $emailFromName = '';

    /**
     * If activated: subject of email
     *
     * @var string
     */
    private $emailSubject = '';

    /**
     * commentRepository
     *
     * @var \Gmf\SimpleDiscussion\Domain\Repository\CommentRepository
     */
    protected $commentRepository = null;

    /**
     * @param \Gmf\SimpleDiscussion\Domain\Repository\CommentRepository $commentRepository
     */
    public function injectCommentRepository(\Gmf\SimpleDiscussion\Domain\Repository\CommentRepository $commentRepository)
    {
        $this->commentRepository = $commentRepository;
    }

    /**
     * Init, started before all actions
     *
     * @return void
     */
    public function initializeAction()
    {
        $this->noticeEmail = (bool) ($this->settings['noticeEmail'] ?? false);
        $this->autoHide = (bool) ($this->settings['autoHide'] ?? false);
        $this->allowReply = (bool) ($this->settings['allowReply'] ?? false);
        $this->emailTo = $this->settings['emailTo'];
        $this->emailToName = $this->settings['emailToName'];
        $this->emailFrom = $this->settings['emailFrom'];
        $this->emailFromName = $this->settings['emailFromName'];
        $this->emailSubject = $this->settings['emailSubject'];
    }

    /**
     * action list
     *
     * @return void
     */
    public function listAction()
    {
        $comments = $this->commentRepository->findAll();

        $unflatten = [];
        $collectHiddenUids = [];
        foreach ($comments as $key => $comment) {
            $uid = $comment->getUid();
            $hidden = $comment->getHidden();
            $reference = $comment->getReference();

            $unflatten[$uid] = array(
                'id' => $uid,
                'hidden' => $hidden,
                'reference' => $reference,
                'comment' => $comment,
                'children' => array()
            );

            // Collect hidden param
            if ($hidden) {
                $collectHiddenUids[] = $uid;
            }

            // Hide also reference
            if (in_array($reference, $collectHiddenUids)) {
                $collectHiddenUids[] = $uid;
            }
        }

        // Remove all hidden items
        foreach ($collectHiddenUids as $key) {
            unset($unflatten[$key]);
        }

        // assign
        $this->view->assign('control', time());
        $cObjectData = $GLOBALS['TSFE']->cObj->data;
        $this->view->assign('data', $cObjectData);
        $this->view->assign('allowReply', $this->allowReply);
        $this->view->assign('comments', $this->unflattenArray($unflatten));
    }


    /**
     * Send email
     *
     * @param \Gmf\SimpleDiscussion\Domain\Model\Comment $comment
     * @return void
     */
    function sendAdminEmail(\Gmf\SimpleDiscussion\Domain\Model\Comment $comment)
    {

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $standaloneView = $objectManager->get(StandaloneView::class);
        $templatePath = GeneralUtility::getFileAbsFileName('EXT:simple_discussion/Resources/Private/Templates/Email/Admin.html');
        $standaloneView->setFormat('html');
        $standaloneView->setTemplatePathAndFilename($templatePath);

        $uriBuilder = $objectManager->get(UriBuilder::class);
        $uri = $uriBuilder->reset()
        ->setTargetPageUid($GLOBALS['TSFE']->id)
        ->setCreateAbsoluteUri(TRUE)
        ->build();

        $standaloneView->assignMultiple([
            'comment' => $comment,
            'uri' => $uri
        ]);
        $html = $standaloneView->render();

        // Create message and send
        $mail = GeneralUtility::makeInstance(MailMessage::class);
        $mail
            ->from(new Address($this->emailFrom, $this->emailFromName))
            ->to(new Address($this->emailTo, $this->emailToName))
            ->subject($this->emailSubject)
            ->text(strip_tags($html))
            ->html($html)
            ->send();
    }

    /**
     * unflatten and make tree like
     *
     * @param array $flatArray
     * @return void
     */
    private function unflattenArray($flatArray)
    {
        $refs = array();
        $result = array();

        // process all elements until nohting could be resolved.
        // then add remaining elements to the root one by one.
        while (count($flatArray) > 0) {
            for ($i = count($flatArray) - 1; $i >= 0; $i--) {
                if ($flatArray[$i]['reference'] === 0) {
                    // root element: set in result and ref!
                    $result[$flatArray[$i]['id']] = $flatArray[$i];
                    $refs[$flatArray[$i]['id']] = &$result[$flatArray[$i]['id']];
                    unset($flatArray[$i]);
                    $flatArray = array_values($flatArray);
                } else if ($flatArray[$i]['reference'] !== 0) {
                    // no root element. Push to the referenced parent, and add to references as well.
                    if (array_key_exists($flatArray[$i]['reference'], $refs)) {
                        // parent found
                        $o = $flatArray[$i];
                        $refs[$flatArray[$i]['id']] = $o;
                        $refs[$flatArray[$i]['reference']]["children"][$refs[$flatArray[$i]['id']]['id']] = &$refs[$flatArray[$i]['id']];
                        unset($flatArray[$i]);
                        $flatArray = array_values($flatArray);
                    }
                }
            }
        }

        return $this->sortTreeArray($result);
    }

    private function sortTreeArray($array)
    {
        krsort($array);
        return $array;
    }

    /**
     * action create
     *
     * @param \Gmf\SimpleDiscussion\Domain\Model\Comment $newComment
     * @return void
     */
    public function createAction(\Gmf\SimpleDiscussion\Domain\Model\Comment $newComment)
    {
        if (trim($newComment->getWebsite()) !== '') {
            // Check Honeypot
            $this->addFlashMessage(LocalizationUtility::translate('tx_simplediscussion_domain_model_comment.error_honeypot', 'SimpleDiscussion'), '', \TYPO3\CMS\Core\Messaging\AbstractMessage::OK);
        } else if (($newComment->getControl() + $this->secureTimeInSeconds) > time()) {
            // Check time
            $this->addFlashMessage(LocalizationUtility::translate('tx_simplediscussion_domain_model_comment.error_delaytime', 'SimpleDiscussion'), '', \TYPO3\CMS\Core\Messaging\AbstractMessage::OK);
        } else if ($newComment->getControl() < (time() - (60 * 10))) {
            // Check time
            $this->addFlashMessage(LocalizationUtility::translate('tx_simplediscussion_domain_model_comment.error_timeout', 'SimpleDiscussion'), '', \TYPO3\CMS\Core\Messaging\AbstractMessage::OK);
        } else {
            $message = LocalizationUtility::translate('tx_simplediscussion_domain_model_comment.created', 'SimpleDiscussion');
            if ($this->autoHide) {
                $newComment->setHidden(1);
                $message = LocalizationUtility::translate('tx_simplediscussion_domain_model_comment.created_autohide', 'SimpleDiscussion');
            }
            $this->commentRepository->add($newComment);
            if ($this->noticeEmail) {
                $this->sendAdminEmail($newComment);
            }
            $this->addFlashMessage($message, '', \TYPO3\CMS\Core\Messaging\AbstractMessage::OK);
        }

        $this->redirect('list', 'Comment', 'SimpleDiscussion', array('instruction' => 'all'));
    }

    /**
     * action edit
     *
     * @param \Gmf\SimpleDiscussion\Domain\Model\Comment $comment
     * @TYPO3\CMS\Extbase\Annotation\IgnoreValidation("comment")
     * @return void
     */
    public function editAction(\Gmf\SimpleDiscussion\Domain\Model\Comment $comment)
    {
        if ($this->allowReply === false) {
            throw new \UnexpectedValueException(
                sprintf('Missconfiguration found: You cannot use reply template if option is disabled!'),
                1597868555
            );
        }
        $this->view->assign('control', time());
        $cObjectData = $GLOBALS['TSFE']->cObj->data;
        $this->view->assign('data', $cObjectData);
        $this->view->assign('comment', $comment);
    }

    /**
     * action create
     *
     * @param \Gmf\SimpleDiscussion\Domain\Model\Comment $comment
     * @return void
     */
    public function updateAction(\Gmf\SimpleDiscussion\Domain\Model\Comment $comment)
    {
        if (trim($comment->getWebsite()) !== '') {
            // Check Honeypot
            $this->addFlashMessage(LocalizationUtility::translate('tx_simplediscussion_domain_model_comment.error_honeypot', 'SimpleDiscussion'), '', \TYPO3\CMS\Core\Messaging\AbstractMessage::OK);
        } else if (($comment->getControl() + $this->secureTimeInSeconds) > time()) {
            // Check time
            $this->addFlashMessage(LocalizationUtility::translate('tx_simplediscussion_domain_model_comment.error_delaytime', 'SimpleDiscussion'), '', \TYPO3\CMS\Core\Messaging\AbstractMessage::OK);
        } else if ($comment->getControl() < (time() - (60 * 10))) {
            // Check time
            $this->addFlashMessage(LocalizationUtility::translate('tx_simplediscussion_domain_model_comment.error_timeout', 'SimpleDiscussion'), '', \TYPO3\CMS\Core\Messaging\AbstractMessage::OK);
        } else {
            $newReply = new \Gmf\SimpleDiscussion\Domain\Model\Comment;
            $newReply->setPid($comment->getPid());
            $newReply->setName($comment->getName());
            $newReply->setEmail($comment->getEmail());
            $newReply->setComment($comment->getComment());
            $newReply->setReference($comment->getUid());

            $message = LocalizationUtility::translate('tx_simplediscussion_domain_model_comment.updated', 'SimpleDiscussion');
            if ($this->autoHide) {
                $newReply->setHidden(1);
                $message = LocalizationUtility::translate('tx_simplediscussion_domain_model_comment.updated_autohide', 'SimpleDiscussion');
            }
            $this->commentRepository->add($newReply);
            if ($this->noticeEmail) {
                $this->sendAdminEmail($newReply);
            }
            $this->addFlashMessage($message, '', \TYPO3\CMS\Core\Messaging\AbstractMessage::OK);
        }

        $this->redirect('list', 'Comment', 'SimpleDiscussion', array('instruction' => 'all'));
    }
}
