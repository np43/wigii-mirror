<?php
/**
 *  This file is part of Wigii.
 *  Wigii is developed to inspire humanity. To Humankind we offer Gracefulness, Righteousness and Goodness.
 *  
 *  Wigii is free software: you can redistribute it and/or modify it 
 *  under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, 
 *  or (at your option) any later version.
 *  
 *  Wigii is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; 
 *  without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
 *  See the GNU General Public License for more details.
 *
 *  A copy of the GNU General Public License is available in the Readme folder of the source code.  
 *  If not, see <http://www.gnu.org/licenses/>.
 *
 *  @copyright  Copyright (c) 2016  Wigii.org
 *  @author     <http://www.wigii.org/system>      Wigii.org 
 *  @link       <http://www.wigii-system.net>      <https://github.com/wigii/wigii>   Source Code
 *  @license    <http://www.gnu.org/licenses/>     GNU General Public License
 */

/**
 * A filter on a list of objects to be provided to a service before querying the database
 * Created by CWE on 19 août 09
 * modified by LWR on 4 May 2011, add in ListFilter model a groupList to used for defining the configuration
 */
class ListFilter extends Model
{
	private $fieldSelectorList;
	private $fieldSortingKeyList;
	private $pageNumber;
	private $pageSize;
	private $totalNumberOfObjects;
	private $fieldSelectorLogExp;
	private $configGroupList;	

	public static function createInstance()
	{
		$returnValue = new self();
		return $returnValue;
	}

	public function getFieldSelectorList()
	{
		return $this->fieldSelectorList;
	}
	public function setFieldSelectorList($fieldSelectorList)
	{
		$this->fieldSelectorList = $fieldSelectorList;
		return $this;
	}
	public function getFieldSortingKeyList()
	{
		return $this->fieldSortingKeyList;
	}
	public function setFieldSortingKeyList($fieldSortingKeyList)
	{
		$this->fieldSortingKeyList = $fieldSortingKeyList;
		return $this;
	}

	/**
	 * Returns true if the list should be paged
	 */
	public function isPaged()
	{
		return ($this->getDesiredPageNumber() > 0 && $this->getPageSize() > 0);
	}

	/**
	 * Sets the parameters to select the desired page of elements
	 * pageNumber: the page number to retrieve, starting with 1
	 */
	public function setDesiredPageNumber($pageNumber)
	{
		if($pageNumber <= 0) throw new ListException('pageNumber must be greater than 0', ListException::INVALID_ARGUMENT);
		$this->pageNumber = $pageNumber;
	}
	public function getDesiredPageNumber()
	{
		return $this->pageNumber;
	}
	/**
	 * Sets the maximum number of objects returned in the page
	 */
	public function setPageSize($pageSize)
	{
		if($pageSize <= 0) throw new ListException('pageSize must be greater than 0', ListException::INVALID_ARGUMENT);
		$this->pageSize = $pageSize;
	}
	public function getPageSize()
	{
		return $this->pageSize;
	}
	
	public function resetPagination(){
		$this->pageSize = null;
		$this->pageNumber = null;
	}

	/**
	 * The Service updates this value each time getAllObjects is called.
	 */
	public function setTotalNumberOfObjects($totalNumberOfObjects)
	{
		$this->totalNumberOfObjects = $totalNumberOfObjects;
		return $this;
	}
	/**
	 * Returns the actual total number of objects set by the Service
	 * return 0 if the Service.getAllObjects has not been called yet.
	 */
	public function getTotalNumberOfObjects()
	{
		return $this->totalNumberOfObjects;
	}

	/**
	 * Returns the FieldSelector LogExp (logical expression)
	 */
	public function getFieldSelectorLogExp()
	{
		return $this->fieldSelectorLogExp;
	}
	/**
	 * Sets the FieldSelector LogExp (logical expression)
	 */
	public function setFieldSelectorLogExp($fieldSelectorLogExp)
	{
		$this->fieldSelectorLogExp = $fieldSelectorLogExp;
		return $this;
	}
	
	/**
	 * Returns the groupList used to define configuration
	 */
	public function getConfigGroupList()
	{
		return $this->configGroupList;
	}
	/**
	 * Sets the groupList used to define the configuration
	 */
	public function setConfigGroupList($groupList)
	{
		$this->configGroupList = $groupList;
		return $this;
	}	
	
	/**
	 * Converts this ListFilter to its FuncExp equivalent
	 * @return FuncExp
	 */
	public function toFx() {
		$fxb = TechnicalServiceProvider::getFuncExpBuilder();
		$lxFxBuilder = TechnicalServiceProvider::getFieldSelectorLogExpFuncExpBuilder();
		$args = array();
		if(isset($this->fieldSelectorList)) $args[] = $fxb->fsl2fx($this->fieldSelectorList);
		if(isset($this->fieldSelectorLogExp)) $args[] = $lxFxBuilder->logExp2funcExp($this->fieldSelectorLogExp); 
		if(isset($this->fieldSortingKeyList)) $args[] = $fxb->fskl2fx($this->fieldSortingKeyList);
		if($this->isPaged()) {
			$args[] = $this->getDesiredPageNumber();
			$args[] = $this->getPageSize();
		}
		$returnValue = fx('lf', $args);
		$lxFxBuilder->freeMemory();
		return $returnValue;
	}
}