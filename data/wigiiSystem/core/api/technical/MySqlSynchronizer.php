<?php
/**
 *  This file is part of Wigii.
 *
 *  Wigii is free software: you can redistribute it and\/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *  
 *  Wigii is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  
 *  You should have received a copy of the GNU General Public License
 *  along with Wigii.  If not, see <http:\//www.gnu.org/licenses/>.
 *  
 *  @copyright  Copyright (c) 2012 Wigii 		 http://code.google.com/p/wigii/    http://www.wigii.ch
 *  @license    http://www.gnu.org/licenses/     GNU General Public License
 */

/**
 * Synchronizes the database with a list of objects
 * Created by CWE on 12 sept. 09
 */
abstract class MySqlSynchronizer implements RowList
{
	private $mysqlF;
	private $tosyncList;
	private $updateList;
	private $updateOrigList;
	private $deleteList;
	private $doDelete;

	protected function shouldDelete()
	{
		return $this->doDelete;
	}
	protected function setShouldDelete($doDelete)
	{
		$this->doDelete = $doDelete;
	}

	// dependency injection

	public function setMySqlFacade($mysqlFacade)
	{
		$this->mysqlF = $mysqlFacade;
	}
	protected function getMySqlFacade()
	{
		// autowired
		if(!isset($this->mysqlF))
		{
			$this->mysqlF = TechnicalServiceProvider::getMySqlFacade();
		}
		return $this->mysqlF;
	}

	// service implementation

	/**
	 * Synchronizes the database with a list of objects.
	 * Does insert, update and delete.
	 */
	public function synchronize($principal, $cnxSettings, $objectList)
	{
		$this->reset();
		$this->setShouldDelete(true);
		$this->doSync($principal, $cnxSettings, $objectList);
	}

	/**
	 * Upgrades the database with a list of objects.
	 * Does insert and update, but NO delete.
	 */
	public function upgrade($principal, $cnxSettings, $objectList)
	{
		$this->reset();
		$this->setShouldDelete(false);
		$this->doSync($principal, $cnxSettings, $objectList);
	}

	/**
	 * See RowList
	 */
	public function addRow($record)
	{
		$rId = $this->getRecordId($record);
		$o = $this->tosyncList[$rId];
		// if record should be synchronized
		if(isset($o))
		{
			// if object is different from existing record, then update is needed
			if(!$this->isObjectEqualToRecord($o, $record))
			{
				$this->updateList[$rId] = $o;
				$this->updateOrigList[$rId] = $record;
				unset($this->toSyncList[$rId]);
			}
			// else nothing to do.
		}
		// record should be deleted (if allowed)
		elseif($this->shouldDelete())
		{
			$this->deleteList[$rId] = $record;
		}
	}

	/**
	 * Synchonization algorithm
	 */
	protected function doSync($principal, $cnxSettings, $objectList)
	{
		if(is_null($objectList)) return;

		// 1. prepares select to fetch existing objects in database
		if(!is_array($objectList))
		{
			$objectList = $objectList->getListIterator();
		}
		foreach($objectList as $o)
		{
			$oId = $this->getObjectId($o);
			$this->tosyncList[$oId] = $o;
			$this->addForSelect($principal, $o, $oId);
		}

		// 2. queries the database and calls back addRow.
		$this->getMySqlFacade()->selectAll($principal, $this->buildSqlQuery($principal), $cnxSettings, $this);

		// 3. insert new objects
		foreach($this->tosyncList as $oId => $o)
		{
			$this->insertObject($principal, $o, $oId, $cnxSettings);
		}
		$this->endOfInsertion($principal, $cnxSettings);

		// 4. updates existing objects which have changed
		foreach($this->updateList as $oId => $o)
		{
			$this->updateObject($principal, $o, $oId, $this->updateOrigList[$oId], $cnxSettings);
		}
		$this->endOfUpdate($principal, $cnxSettings);

		// 5. deletes obsolete objects
		if($this->shouldDelete())
		{
			foreach($this->deleteList as $oId => $o)
			{
				$this->deleteRecord($principal, $o, $oId, $cnxSettings);
			}
			$this->endOfDeletion($principal, $cnxSettings);
		}

		// cleanup
		$this->reset();
	}

	/**
	 * Returns ID of object
	 */
	protected abstract function getObjectId($object);
	/**
	 * Adds object to select query
	 */
	protected abstract function addForSelect($principal, $object, $objectId);
	/**
	 * Builds sql select query and returns it as string
	 */
	protected abstract function buildSqlQuery($principal);


	/**
	 * Returns ID of record
	 */
	protected abstract function getRecordId($record);
	/**
	 * Compares object to selected record in database, returns true if equal
	 */
	protected abstract function isObjectEqualToRecord($object, $record);


	/**
	 * Inserts object in database or prepares it for later insertion
	 */
	protected abstract function insertObject($principal, $object, $objectId, $cnxSettings);
	/**
	 * Informs of end of insertion.
	 * Subclass can use this method to effectively do the insertion at this moment
	 */
	protected function endOfInsertion($principal, $cnxSettings) {/* does nothing. */}


	/**
	 * Updates object in database or prepares it for later update
	 */
	protected abstract function updateObject($principal, $object, $objectId, $origRecord, $cnxSettings);
	/**
	 * Informs of end of update.
	 * Subclass can use this method to effectively do the update at this moment
	 */
	protected function endOfUpdate($principal, $cnxSettings) {/* does nothing. */}


	/**
	 * Deletes object in database or prepares it for later deletion
	 */
	protected abstract function deleteRecord($principal, $record, $recordId, $cnxSettings);
	/**
	 * Informs of end of deletion.
	 * Subclass can use this method to effectively do the delete at this moment
	 */
	protected function endOfDeletion($principal, $cnxSettings) {/* does nothing. */}

	/**
	 * Resets the synchronizer's state, should be called before using it.
	 */
	protected function reset()
	{
		unset($this->tosyncList);
		unset($this->updateList);
		unset($this->updateOrigList);
		unset($this->deleteList);
		$this->setShouldDelete(false);
	}
}