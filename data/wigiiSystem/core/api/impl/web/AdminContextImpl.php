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

/*
 * Created on 15 janv. 10
 * by LWR
 */

class AdminContextImpl extends Model implements AdminContext {

	private $_debugLogger;
	private $_executionSink;
	private $workingModule;
	private $subScreenName;
	private $userListFilter;
	private $user2ListFilter;
	private $groupListFilter;
	private $rootPrincipal;
	private $groupFilterPost;
	private $groupFilterIsSet;
	private $userFilterPost;
	private $userFilterIsSet;
	private $user2FilterPost;
	private $user2FilterIsSet;
	private $desiredHPage;
	private $desiredVPage;

	public function __construct() {
		$this->debugLogger()->write("creating instance");
	}
	public function __sleep(){
		return array("workingModule", "subScreenName", "userListFilter", "user2ListFilter", "groupListFilter", "userFilterPost", "user2FilterPost", "groupFilterPost", "userFilterIsSet", "user2FilterIsSet");
	}

	public static function createInstance($rootPrincipal){
		$r = new self();
		$r->setRootPrincipal($rootPrincipal);
		return $r;
	}

	private function debugLogger() {
		if(!isset($this->_debugLogger)) {
			$this->_debugLogger = DebugLogger::getInstance("AdminContextImpl");
		}
		return $this->_debugLogger;
	}
	private function executionSink() {
		if(!isset($this->_executionSink)) {
			$this->_executionSink = ExecutionSink::getInstance("AdminContextImpl");
		}
		return $this->_executionSink;
	}

	public function setWorkingModule($module){
		if(isset($module) && $module->isAdminModule()) throw new ServiceException("working module should never be Admin module", ServiceException::INVALID_ARGUMENT);
		$this->workingModule = $module; }
	public function getWorkingModule(){ return $this->workingModule; }
	public function isWorkingModule($module){
		if(isset($this->workingModule)){
			return $this->workingModule->getModuleName() == $module->getModuleName();
		}
		return !isset($module);
	}

	public function setSubScreen($subScreenName){ $this->subScreenName = $subScreenName; }
	public function getSubScreen(){ return $this->subScreenName; }

	/**
	 * return an array of strings with the names of the subScreens
	 * this looks to the P rights to define which screens need to be showed
	 */
	public function getSubScreens($p){
		/**
		 * WARNING, the order is important.
		 * Because of grouping matrix in Access Menu
		 * and must end by adminModuleEditor
		 * This is in adminSearchBar.tpl.php
		 */
		$subScreens = array();
		$subScreens[] = "adminGroup";
		if($p->isUserCreator()) $subScreens[] = "adminUser"; //there is no added value having the list of users without doing anything
		if($p->isUserCreator()) $subScreens[] = "adminRole"; //there is no added value having the list of users without doing anything
		$subScreens[] = "adminGroupUser";
		$subScreens[] = "adminUserRole";
		if($p->isAdminCreator()) $subScreens[] = "adminUserUser";
		if($p->isAdminCreator()) $subScreens[] = "adminUserAdmin";
		if($p->isModuleEditor()) $subScreens[] = "adminModuleEditor";

		return $subScreens;
	}
	
	public function setDesiredHPage($nb){ $this->desiredHPage = $nb; }
	public function getDesiredHPage(){ return $this->desiredHPage; }
	
	public function setDesiredVPage($nb){ $this->desiredVPage = $nb; }
	public function getDesiredVPage(){ return $this->desiredVPage; }
	
	public function setUserListFilter($listFilter){ $this->userListFilter = $listFilter; }
	public function getUserListFilter(){
		//autowired
		if(!isset($this->userListFilter)){
			$this->userListFilter = $this->createListFilterInstance();
			$fskl = FieldSortingKeyListArrayImpl::createInstance();
			$fskl->addFieldSortingKey("isRole", null, true);
			$fskl->addFieldSortingKey("wigiiNamespace");
			$fskl->addFieldSortingKey("username");
			$this->userListFilter->setFieldSortingKeyList($fskl);
//			$fsle = LogExp::createEqualExp(FieldSelector::createInstance("isCalculatedRole"), false);
//			$this->userListFilter->setFieldSelectorLogExp($fsle);
		}
		return $this->userListFilter;
	}
	public function setUser2ListFilter($listFilter){ $this->user2ListFilter = $listFilter; }
	public function getUser2ListFilter(){
		//autowired
		if(!isset($this->user2ListFilter)){
			$this->user2ListFilter = $this->createListFilterInstance();
			$fskl = FieldSortingKeyListArrayImpl::createInstance();
			$fskl->addFieldSortingKey("isRole", null, true);
			$fskl->addFieldSortingKey("wigiiNamespace");
			$fskl->addFieldSortingKey("username");
			$this->user2ListFilter->setFieldSortingKeyList($fskl);
//			$fsle = LogExp::createEqualExp(FieldSelector::createInstance("isCalculatedRole"), false);
//			$this->userListFilter->setFieldSelectorLogExp($fsle);
		}
		return $this->user2ListFilter;
	}
	protected function createListFilterInstance(){
		return ListFilter::createInstance();
	}

	public function setGroupListFilter($listFilter){ $this->groupListFilter = $listFilter; }
	public function getGroupListFilter(){
		//autowired
		if(!isset($this->groupListFilter)){
			$this->groupListFilter = $this->createGroupListFilterInstance();
		}
		return $this->groupListFilter;
	}
	protected function createGroupListFilterInstance(){
		return ListFilter::createInstance();
	}

	public function setRootPrincipal($rootPrincipal){ $this->rootPrincipal = $rootPrincipal; }
	protected function getRootPrincipal(){ return $this->rootPrincipal; }

	public function getGroupFilterPost($name=null){
		if($name == null) return $this->groupFilterPost;
		else return $this->groupFilterPost[$name];
	}
	public function isGroupFilterSet(){
		return $this->groupFilterIsSet;
	}
	protected function setIsGroupFilterSet($isSet){
		$this->groupFilterIsSet = $isSet;
	}
	
	protected function getGroupFilterText(){
		return $this->getGroupFilterPost(self::GroupFilterText);
	}
	
	public function resetGroupFilter(){
		$this->groupFilterPost = null;
		$this->setDesiredHPage(null);
		$this->setDesiredVPage(null);
		$this->setIsGroupFilterSet(false);
		$listFilter = $this->getGroupListFilter();
		$listFilter->setFieldSelectorLogExp(null);
	}
	public function setGroupFilterFromPost(){
		$this->groupFilterPost = $this->getPost();
		
		//do the checks:
		//prevent injections
		foreach($this->groupFilterPost as $name=>$val){
			if($val === "none") $this->groupFilterPost[$name] = null;
			else {
				if(ini_get("magic_quotes_gpc")){
					$this->groupFilterPost[$name] = stripslashes($val);
				} else {
					$this->groupFilterPost[$name] = ($val);
				}
			}
		}
		
		/**
		 * search the id if one keyword #number
		 */
		$tempTextSearch = $this->getGroupFilterText();
		preg_match_all('/(\#[\d]{1,})/', $tempTextSearch, $matches);
		$filterOnId = array();
		foreach ($matches[0] as $key => $value) {
			  $filterOnId[] = str_replace("#", "", $value);
			  //delete in textSearch
			  $tempTextSearch = str_replace($value, "", $tempTextSearch);
		}
		$fslId = FieldSelectorListArrayWebImpl::createInstance();
		$fslId->addFieldSelector("id");
		
		//create logExp on text search
		$fsl = FieldSelectorListArrayWebImpl::createInstance();
		$fsl->addFieldSelector("groupname");
		$fsl->addFieldSelector("wigiiNamespace");
		$fsl->addFieldSelector("description");
		
		$expId = TechnicalServiceProvider::getSearchBarOrLogExpParser()->createLogExpOnCriteria($fslId, implode(" ", $filterOnId));
		$textLogExp = TechnicalServiceProvider::getSearchBarOrLogExpParser()->createLogExpOnCriteria($fsl, $tempTextSearch);
		if($textLogExp!=null && $expId != null){
			$tempExp = LogExp::createOrExp();
			$tempExp->addOperand($textLogExp);
			$tempExp->addOperand($expId);
			$textLogExp = $tempExp;
		} else if($expId != null){
			$textLogExp = $expId;
		}
		
		//create log exp on user type
		$this->setDesiredHPage($this->groupFilterPost[AdminContext::HorizontalPagingText]);
		$this->setDesiredVPage($this->groupFilterPost[AdminContext::VerticalPagingText]);
		
		$logExp = $textLogExp;

		$this->setIsGroupFilterSet($logExp != null);

		$listFilter = $this->getGroupListFilter();
		$listFilter->setFieldSelectorLogExp($logExp);
	}
	public function getUserFilterPost($name=null){
		if($name == null) return $this->userFilterPost;
		else return $this->userFilterPost[$name];
	}
	public function isUserFilterSet(){
		return $this->userFilterIsSet;
	}
	protected function setIsUserFilterSet($isSet){
		$this->userFilterIsSet = $isSet;
	}

	protected function getUserFilterText(){
		return $this->getUserFilterPost(self::UserFilterText);
	}
	protected function getUserFilterType(){
		return $this->getUserFilterPost(self::UserFilterType);
	}
	public function getUser2FilterPost($name=null){
		if($name == null) return $this->user2FilterPost;
		else return $this->user2FilterPost[$name];
	}
	public function isUser2FilterSet(){
		return $this->user2FilterIsSet;
	}
	protected function setIsUser2FilterSet($isSet){
		$this->user2FilterIsSet = $isSet;
	}

	protected function getUser2FilterText(){
		return $this->getUser2FilterPost(self::User2FilterText);
	}
	protected function getUser2FilterType(){
		return $this->getUser2FilterPost(self::User2FilterType);
	}
	protected function getPost(){ return $_POST; }

	public function resetUserFilter(){
		$this->userFilterPost = null;
		$this->setDesiredHPage(null);
		$this->setDesiredVPage(null);
		$this->setIsUserFilterSet(false);
		$listFilter = $this->getUserListFilter();
		$listFilter->setFieldSelectorLogExp(null);
	}
	public function resetUser2Filter(){
		$this->user2FilterPost = null;
		$this->setDesiredHPage(null);
		$this->setDesiredVPage(null);
		$this->setIsUser2FilterSet(false);
		$listFilter2 = $this->getUser2ListFilter();
		$listFilter2->setFieldSelectorLogExp(null);
	}
	
	public function setUserFilterFromPost(){
		$this->doSetUserFilterFromPost(null, AdminContext::UserFilterText, AdminContext::UserFilterType);
	}
	
	public function setUser2FilterFromPost(){
		$this->doSetUserFilterFromPost(2, AdminContext::User2FilterText, AdminContext::User2FilterType);
	}
	protected function doSetUserFilterFromPost($nb=null, $userFilterText, $userFilterType){
		$post = $this->getPost();
		//do the checks:
		//prevent injections
		foreach($post as $name=>$val){
			if($val === "none") $post[$name] = null;
			else {
				if(ini_get("magic_quotes_gpc")){
					$post[$name] = stripslashes($val);
				} else {
					$post[$name] = ($val);
				}
			}

			if($name == $userFilterType){
				if(false===array_search($post[$name], array(null, "user", "role", "calculatedRole"))) throw new AdminContextException('invalid '.$userFilterType.' '.$val, AdminContextException::INVALID_ARGUMENT);
			}
		}
		
		if($nb==2){
			$this->user2FilterPost = $post;
		} else {
			$this->userFilterPost = $post;
		}
		/**
		 * search the id if one keyword #number
		 */
		if($nb==2) $tempTextSearch = $this->getUser2FilterText();
		else $tempTextSearch = $this->getUserFilterText();
		
		preg_match_all('/(\#[\d]{1,})/', $tempTextSearch, $matches);
		$filterOnId = array();
		foreach ($matches[0] as $key => $value) {
			  $filterOnId[] = str_replace("#", "", $value);
			  //delete in textSearch
			  $tempTextSearch = str_replace($value, "", $tempTextSearch);
		}
		$fslId = FieldSelectorListArrayWebImpl::createInstance();
		$fslId->addFieldSelector("id");
		
		//create logExp on text search
		$fsl = FieldSelectorListArrayWebImpl::createInstance();
		$fsl->addFieldSelector("username");
		$fsl->addFieldSelector("wigiiNamespace");
		$fsl->addFieldSelector("description");
		
		$expId = TechnicalServiceProvider::getSearchBarOrLogExpParser()->createLogExpOnCriteria($fslId, implode(" ", $filterOnId));
		$textLogExp = TechnicalServiceProvider::getSearchBarOrLogExpParser()->createLogExpOnCriteria($fsl, $tempTextSearch);
		if($textLogExp!=null && $expId != null){
			$tempExp = LogExp::createOrExp();
			$tempExp->addOperand($textLogExp);
			$tempExp->addOperand($expId);
			$textLogExp = $tempExp;
		} else if($expId != null){
			$textLogExp = $expId;
		}
		
		//create log exp on user type
		$this->setDesiredHPage($post[AdminContext::HorizontalPagingText]);
		$this->setDesiredVPage($post[AdminContext::VerticalPagingText]);
		
		//set desired page number
		if($nb==2){
			if($this->getUser2FilterType() !== null){
				if($this->getUser2FilterType()==="role"){
					$typeLogExp = LogExp::createAndExp();
					$typeLogExp->addOperand(LogExp::createEqualExp(FieldSelector::createInstance("isRole"), true));
					$typeLogExp->addOperand(LogExp::createEqualExp(FieldSelector::createInstance("isCalculatedRole"), false));
				} else if($this->getUser2FilterType()==="calculatedRole"){
					$typeLogExp = LogExp::createAndExp();
					$typeLogExp->addOperand(LogExp::createEqualExp(FieldSelector::createInstance("isRole"), true));
					$typeLogExp->addOperand(LogExp::createEqualExp(FieldSelector::createInstance("isCalculatedRole"), true));
				} else {
					$typeLogExp = LogExp::createEqualExp(FieldSelector::createInstance("isRole"), false);
				}
			} else {
//				$typeLogExp = null;
				$typeLogExp = LogExp::createEqualExp(FieldSelector::createInstance("isCalculatedRole"), false);
			}
		} else {
			if($this->getUserFilterType() !== null){
				if($this->getUserFilterType()==="role"){
					$typeLogExp = LogExp::createAndExp();
					$typeLogExp->addOperand(LogExp::createEqualExp(FieldSelector::createInstance("isRole"), true));
					$typeLogExp->addOperand(LogExp::createEqualExp(FieldSelector::createInstance("isCalculatedRole"), false));
				} else if($this->getUserFilterType()==="calculatedRole"){
					$typeLogExp = LogExp::createAndExp();
					$typeLogExp->addOperand(LogExp::createEqualExp(FieldSelector::createInstance("isRole"), true));
					$typeLogExp->addOperand(LogExp::createEqualExp(FieldSelector::createInstance("isCalculatedRole"), true));
				} else {
					$typeLogExp = LogExp::createEqualExp(FieldSelector::createInstance("isRole"), false);
				}
			} else {
				$typeLogExp = LogExp::createEqualExp(FieldSelector::createInstance("isCalculatedRole"), false);
				//$typeLogExp = null;
			}
		}
		
		//merge text and type log exp
		if(isset($textLogExp) && isset($typeLogExp)){
			$logExp = LogExp::createAndExp();
			$logExp->addOperand($textLogExp);
			$logExp->addOperand($typeLogExp);
		} else if(isset($textLogExp)){
			$logExp = $textLogExp;
		} else if(isset($typeLogExp)){
			$logExp = $typeLogExp;
		}
		
		if($nb==2){
			$this->setIsUser2FilterSet($logExp != null);
			$listFilter = $this->getUser2ListFilter();
		} else {
			$this->setIsUserFilterSet($logExp != null);
			$listFilter = $this->getUserListFilter();
		}

		$listFilter->setFieldSelectorLogExp($logExp);
	}

	public function serialize($p){
		$returnValue = array();
		$returnValue["workingModule"] = ($this->getWorkingModule() ? $this->getWorkingModule()->getModuleName() : null);
		$returnValue["subScreen"] = $this->getSubScreen();
		return $returnValue;
	}
	public function importFromSerialize($p, $array){
		$mAS = ServiceProvider::getModuleAdminService();
		$this->setWorkingModule($mAS->getModule($p, $array["workingModule"]));
		$this->setSubScreen($array["subScreen"]);
	}
}


