<?php
namespace Gmf\SimpleDiscussion\Domain\Repository;


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
 * The repository for Comments
 */
class CommentRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{

    /**
     * Returns all objects of this repository.
     *
     * @return QueryResultInterface|array
     */
    public function findAll()
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setIgnoreEnableFields(array('hidden'));
        return $query->execute();
    }

	/**
	 * Debug Query
	 *
	 * @param query $query
	 * @return void
	 */
	public static function debugQuery($query)
	{
		$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$queryParser = $objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbQueryParser::class);
		\TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($queryParser->convertQueryToDoctrineQueryBuilder($query)->getSQL());
		\TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($queryParser->convertQueryToDoctrineQueryBuilder($query)->getParameters());
	}

}
