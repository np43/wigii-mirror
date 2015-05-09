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
 * Fills a LinkSelectorList with db rows containing link informations.
 * Created by CWE on 6 février 2014
 */
class LinkSelectorListMapper implements RowList
{
	private $lockedForUse = true;
	private $linkSelectorList;
	private $linkSelectorCounter;

	// Object lifecycle
	
	public static function createInstance($linkSelectorList)
	{
		$returnValue = new self();		
		$returnValue->reset($linkSelectorList);
		return $returnValue;
	}

	public function reset($linkSelectorList)
	{
		$this->freeMemory();
		$this->lockedForUse = true;
		$this->linkSelectorList = $linkSelectorList;		
		$this->linkSelectorCounter = 0;
	}
	public function freeMemory() {
		$this->lockedForUse = false;
	}
	public function isLockedForUse() {
		return $this->lockedForUse;
	}
	
	// Row list implementation
	
	public function getListIterator()
	{
		return $this->linkSelectorList->getListIterator();
	}

	public function isEmpty()
	{
		return ($this->linkSelectorCounter == 0);
	}

	public function count()
	{
		return $this->linkSelectorCounter;
	}

	public function addRow($row)
	{
		if(isset($row))
		{
			$fieldName = null;
			if(isset($row['linkName'])) $fieldName = $row['linkName'];
			elseif(isset($row['fieldName'])) $fieldName = $row['fieldName'];
			
			$moduleName = null;
			if(isset($row['modulename'])) $moduleName = $row['modulename'];
			elseif(isset($row['module'])) $moduleName = $row['module'];
			
			if(isset($row['id_element_owner'])) {
				$linkSelector = LinkSelector::createInstance($row['id_element_owner'], $fieldName);
				if(isset($moduleName)) $linkSelector->setModuleName($moduleName);
				if(isset($row['state_blocked'])) $linkSelector->setOwnerElementBlocked($row['state_blocked']);
			}	
			elseif(isset($moduleName)) {
				$linkSelector = LinkSelector::createConfigInstance($fieldName, $moduleName);
			}
			else throw new ElementServiceException("A LinkSelector should have either a id_element_owner defined or a modulename defined", ElementServiceException::INVALID_ARGUMENT);
					 
			$this->linkSelectorList->addLinkSelector($linkSelector);
			$this->linkSelectorCounter++;
		}
	}
}