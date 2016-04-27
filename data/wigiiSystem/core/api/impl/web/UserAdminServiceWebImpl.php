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

/*
 * Created on 25 janv. 10
 * by LWR
 */

class UserAdminServiceWebImpl extends UserAdminServiceImpl {

	private $_debugLogger;
	private $_executionSink;
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("UserAdminServiceWebImpl");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("UserAdminServiceWebImpl");
		}
		return $this->_executionSink;
	}

	/**
	 * get listFilters to fetch user information for NavigationBar
	 */
	public function getListFilterForNavigationBar(){
		$listFilter = ListFilter::createInstance();
		$fskl = FieldSortingKeyListArrayImpl::createInstance();
		$fskl->addFieldSortingKey("wigiiNamespace");
		$fskl->addFieldSortingKey("username");
		$listFilter->setFieldSortingKeyList($fskl);
		return $listFilter;
	}
	/**
	 * get listFilters to fetch correct informations in Admin module
	 */
	public function getListFilterForUserList(){
		$listFilter = ListFilter::createInstance();
		$fskl = FieldSortingKeyListArrayImpl::createInstance();
		$fskl->addFieldSortingKey("wigiiNamespace");
		$fskl->addFieldSortingKey("username");
		$listFilter->setFieldSortingKeyList($fskl);
		$fsle = LogExp::createAndExp();
		$fsle->addOperand(LogExp::createEqualExp(FieldSelector::createInstance("isRole"), false));
		$listFilter->setFieldSelectorLogExp($fsle);

		return $listFilter;
	}
	/**
	 * get listFilters to fetch correct informations in Admin module
	 */
	public function getListFilterForRoleList(){
		$listFilter = ListFilter::createInstance();
		$fskl = FieldSortingKeyListArrayImpl::createInstance();
		$fskl->addFieldSortingKey("wigiiNamespace");
		$fskl->addFieldSortingKey("username");
		$listFilter->setFieldSortingKeyList($fskl);
		$fsle = LogExp::createAndExp();
		$fsle->addOperand(LogExp::createEqualExp(FieldSelector::createInstance("isRole"), true));
		$fsle->addOperand(LogExp::createEqualExp(FieldSelector::createInstance("isCalculatedRole"), false));
		$listFilter->setFieldSelectorLogExp($fsle);
		return $listFilter;
	}

	/**
	 * used in calculateAllMergedRoles
	 */
	public function getListFilterForRoleToCalculateCalculatedRoleList(){
		$listFilter = ListFilter::createInstance();
		$fskl = FieldSortingKeyListArrayImpl::createInstance();
		$fskl->addFieldSortingKey("wigiiNamespace");
		$fskl->addFieldSortingKey("username");
		$listFilter->setFieldSortingKeyList($fskl);
		//superadmins are removed in the UserListForCalculateAllMergeRoleImpl
		return $listFilter;
	}

	public function getListFilterForCalculatedRoleList(){

		$listFilter = ListFilter::createInstance();
		$fskl = FieldSortingKeyListArrayImpl::createInstance();
		$fskl->addFieldSortingKey("wigiiNamespace");
		$fskl->addFieldSortingKey("username");
		$listFilter->setFieldSortingKeyList($fskl);
		$fsle = LogExp::createEqualExp(FieldSelector::createInstance("isRole"), true);
		$listFilter->setFieldSelectorLogExp($fsle);
		return $listFilter;
	}

	public function getListFilterForCloseAdminButton($wigiiNamespace){

		$listFilter = ListFilter::createInstance();
		$fskl = FieldSortingKeyListArrayImpl::createInstance();
		$listFilter->setFieldSortingKeyList($fskl);
		$leAnd = LogExp::createAndExp();
		$le1 = LogExp::createEqualExp(FieldSelector::createInstance("isCalculatedRole"), true);
		$le2 = LogExp::createEqualExp(FieldSelector::createInstance("wigiiNamespace"), $wigiiNamespace->getWigiiNamespaceName());
		$leAnd->addOperand($le1);
		$leAnd->addOperand($le2);
		$listFilter->setFieldSelectorLogExp($leAnd);

		return $listFilter;
	}
	public function getListFilterForAdminMenu(){

		$listFilter = ListFilter::createInstance();
		$fskl = FieldSortingKeyListArrayImpl::createInstance();
		$fskl->addFieldSortingKey("wigiiNamespace");
		$fskl->addFieldSortingKey("username");
		$listFilter->setFieldSortingKeyList($fskl);
		$leAnd = LogExp::createAndExp();
		$leOr = LogExp::createOrExp();
		$le1 = LogExp::createEqualExp(FieldSelector::createInstance("isCalculatedRole"), false);
		$le2 = LogExp::createNotEqualExp(FieldSelector::createInstance("rootGroupCreator"), null);
		$le3 = LogExp::createEqualExp(FieldSelector::createInstance("userCreator"), true);
		$leOr->addOperand($le2);
		$leOr->addOperand($le3);
		$leAnd->addOperand($le1);
		$leAnd->addOperand($leOr);
		$listFilter->setFieldSelectorLogExp($leAnd);

		return $listFilter;
	}

}



