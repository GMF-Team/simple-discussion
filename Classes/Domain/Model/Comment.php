<?php

namespace Gmf\SimpleDiscussion\Domain\Model;


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
 * Comment
 */
class Comment extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{

    /**
     * name
     *
     * @var string
     */
    protected $name = '';

    /**
     * email
     *
     * @var string
     */
    protected $email = '';

    /**
     * control
     *
     * @var string
     */
    protected $control = '';

    /**
     * website
     *
     * @var string
     */
    protected $website = '';

    /**
     * comment
     *
     * @var string
     */
    protected $comment = '';

    /**
     * reference
     *
     * @var int
     */
    protected $reference = 0;

    /**
     * hidden
     *
     * @var int
     */
    protected $hidden = 0;

    /**
     * crdate
     *
     * @var \DateTime
     */
    protected $crdate = null;

    /**
     * Returns the name
     *
     * @return string $name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the name
     *
     * @param string $name
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Returns the email
     *
     * @return string $email
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Sets the email
     *
     * @param string $email
     * @return void
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * Returns the website
     *
     * @return string $website
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * Sets the website
     *
     * @param string $website
     * @return void
     */
    public function setWebsite($website)
    {
        $this->website = $website;
    }

    /**
     * Returns the control
     *
     * @return string $control
     */
    public function getControl()
    {
        return $this->control;
    }

    /**
     * Sets the control
     *
     * @param string $control
     * @return void
     */
    public function setControl($control)
    {
        $this->control = $control;
    }

    /**
     * Returns the comment
     *
     * @return string $comment
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Sets the comment
     *
     * @param string $comment
     * @return void
     */
    public function setComment($comment)
    {
        $this->comment = $comment;
    }

    /**
     * Returns the reference
     *
     * @return int $reference
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * Sets the reference
     *
     * @param int $reference
     * @return void
     */
    public function setReference($reference)
    {
        $this->reference = $reference;
    }


    /**
     * Returns the hidden
     *
     * @return int $hidden
     */
    public function getHidden()
    {
        return $this->hidden;
    }

    /**
     * Sets the hidden
     *
     * @param int $hidden
     * @return void
     */
    public function setHidden($hidden)
    {
        $this->hidden = $hidden;
    }

    /**
     * Returns the crdate
     *
     * @return int
     */
    public function getCrdate()
    {
        return $this->crdate;
    }
}
