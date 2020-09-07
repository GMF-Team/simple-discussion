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
	 * If activated: email from name
	 *
	 * @var array
	 */
	private $commentsRecursive = array();

	/**
	 * If activated: subject of email
	 *
	 * @var string
	 */
	private $emailSubject = '';

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
	 */
	protected $persistenceManager;

	/**
	 * Inject Persistence Manager
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager $persistenceManager
	 */
	public function injectPersistenceManager(\TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager $persistenceManager) {
		$this->persistenceManager = $persistenceManager;
	}

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

		// Actually the param $comment does not contain the uid
		// We should call the PersistenceManager to update all fields
		$this->persistenceManager->persistAll();

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
	 * @return array
	 */
	private function unflattenArray($flatArray)
	{
		// If only one item, no need to sort
		if (count($flatArray) === 1) {
			return $flatArray;
		}

		// Iterate every comment
		foreach ($flatArray as $comment) {
			if($comment['reference'] === 0) {
				$this->commentsRecursive[] = $comment;
			} else {
				$this->addCommentToParent($comment, null);
			}
		}

		return $this->commentsRecursive;
	}

	/**
	 * Reorganize comments recursive
	 *
	 * @param array $entry
	 * @param array $children
	 * @return void
	 */
	private function addCommentToParent($entry, &$children = null) {
		if($children === null) {
			$children = &$this->commentsRecursive;
		}

		foreach($children as &$comment) {
			if($comment['id'] === $entry['reference']) {
				$comment['children'][] = $entry;
				return;
			} else {
				$this->addCommentToParent($entry, $comment['children']);
			}
		}
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
