<?php
/**
 *  This file is part of Wigii (R) software.
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
 * tests ElementServiceImpl
 * Created by CWE on 17 août 09
 */
class Debug_ElementServiceImpl_getAllElementsInGroup extends WigiiApiTest
implements GroupPTree, ElementPList, WigiiBag, FieldList
{
	protected $firstGroup;
	protected $wigiiBag;
	protected $fieldList;

	private function d()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("Debug_ElementServiceImpl_getAllElementsInGroup");
		}
		return $this->_debugLogger;
	}

	public function __construct($testId=null, $testName=null)
	{
		parent::__construct((!isset($testId)?'Debug_ElementServiceImpl_getAllElementsInGroup':$testId),(!isset($testName)?'debug ElementService getAll elements in group':$testName));
		$this->wigiiBag = array();
	}
	public function run()
	{
		$groupAS = ServiceProvider::getGroupAdminService();
		$mas = ServiceProvider::getModuleAdminService();
		$dat = $this->data();
		$p = $dat->principal($dat->admin());

		$groupAS->getAllGroups($p, $mas->getModule($p, 'Contacts'), $this);
		//$this->firstGroup = $groupAS->getGroup($p, 280)->getGroup();
		$this->assertIsSet('first group is selected', $this->firstGroup);
		$this->d()->write("gets all elements in group ".$this->firstGroup->getId().":".$this->firstGroup->getGroupName());

		$eltS = ServiceProvider::getElementService();
		$eltS->getAllElementsInGroup($p, $this->firstGroup, $this);
	}


	// GroupPTree

	public function addGroupP($groupP, $parentGroup)
	{
		if(!isset($this->firstGroup)) $this->firstGroup = $groupP->getGroup();
	}
	public function getListIterator() {$this->fail('unsupported operation');}
	public function isEmpty() {$this->fail('unsupported operation');}
	public function count() {$this->fail('unsupported operation');}

	// ElementPList

	public function addElementP($elementP)
	{
		$e = $elementP->getElement();
		$rights = $elementP->getRights();
		if(isset($rights))
		{
			$rights = ', canWriteElement='.$rights->canWriteElement().', canShareElement='.$rights->canShareElement();
		}
		else $rights = '';
		$this->d()->write("adding elementP: ".$e->getId().$rights);
		$this->assertEqual("element field list is correctly set", ($this === $e->getFieldList()), true);
		$this->assertEqual("element wigii bag is correctly set", ($this === $e->getWigiiBag()), true);
	}
	public function createFieldList()
	{
		$this->fieldList = FieldListArrayImpl::createInstance();
		return $this;
	}
	public function createWigiiBag()
	{
		return $this;
	}

	// WigiiBag

	public function getValue($elementId, $dataTypeName, $fieldName, $subFieldName=null)
	{
		if(is_null($subFieldName)) $subFieldName = "value";
		return $this->wigiiBag["($elementId($dataTypeName($fieldName($subFieldName))))"];
	}
	public function setValue($value, $elementId, $dataTypeName, $fieldName, $subFieldName=null)
	{
		$this->d()->write("adding element value (id:$elementId, dtName:$dataTypeName, field:$fieldName, subField:$subFieldName, value:$value)");
		$this->wigiiBag["($elementId($dataTypeName($fieldName($subFieldName))))"] = $value;
	}
	public function applyOperator($operator, $value, $dataTypeName, $fieldName, $subFieldName=null, $field=null)
	{$this->fail('unsupported operation');}

	// FieldList

	public function addField($field)
	{
		$dtName = $field->getDataType();
		if(isset($dtName)) $dtName = $dtName->getDataTypeName();
		else $dtName = "freeText";
		$this->d()->write("adding field ".$field->getFieldName().": ".$dtName);
		$this->fieldList->addField($field);
	}
	public function getField($fieldName)
	{
		return $this->fieldList->getField($fieldName);
	}
	public function doesFieldExist($fieldName)
	{
		return $this->fieldList->doesFieldExist($fieldName);
	}
}
TestRunner::test(new Debug_ElementServiceImpl_getAllElementsInGroup());

class Debug_ElementServiceImpl_getAllElementsInGroupPaged extends Debug_ElementServiceImpl_getAllElementsInGroup
{
	private function d()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("Debug_ElementServiceImpl_getAllElementsInGroupPaged");
		}
		return $this->_debugLogger;
	}

	public function __construct()
	{
		parent::__construct('Debug_ElementServiceImpl_getAllElementsInGroupPaged','debug ElementService getAll elements in group PAGED');
	}
	public function run()
	{
		$groupAS = ServiceProvider::getGroupAdminService();
		$mas = ServiceProvider::getModuleAdminService();
		$dat = $this->data();
		$p = $dat->principal($dat->admin());

		$groupAS->getAllGroups($p, $mas->getModule($p, 'Contacts'), $this);
		//$this->firstGroup = $groupAS->getGroup($p, 216)->getGroup();
		$this->assertIsSet('first group is selected', $this->firstGroup);
		$this->d()->write("gets all elements in group ".$this->firstGroup->getId().":".$this->firstGroup->getGroupName());

		$eltS = ServiceProvider::getElementService();
		$listFilter = ListFilter::createInstance();
		$fieldSelectorList = FieldSelectorListArrayImpl::createInstance();
		$this->assertIsSet('fieldSelectorList is createad', $fieldSelectorList);
		$fieldSelectorList->addFieldSelector('first_name');
		$fieldSelectorList->addFieldSelector('site', 'target');
		$fieldSelectorList->addFieldSelector('site');
		$fieldSelectorList->addFieldSelector('address', 'city');
		$fieldSelectorList->addFieldSelector('address', 'country');
		$fieldSelectorList->addFieldSelector('calculatedField');

		$listFilter->setFieldSelectorList($fieldSelectorList);
		$this->assertIsSet('list filter has a fieldSelectorList', $listFilter->getFieldSelectorList());
		$this->assertIsSet('fieldSelectorList has an iterator', $listFilter->getFieldSelectorList()->getListIterator());

		$pageSize = 5;
		$listFilter->setPageSize($pageSize);
		$this->assertEqual("page size is set to $pageSize", $listFilter->getPageSize(), $pageSize);

		$page = 1; $eltCount = 0;
		$listFilter->setDesiredPageNumber($page);
		$this->assertEqual("page is set to $page", $page, $listFilter->getDesiredPageNumber());
		$eltCount += $eltS->getAllElementsInGroup($p, $this->firstGroup, $this, true, $listFilter);
		$this->d()->write("p.$page retrieved $eltCount elements on a total of ".$listFilter->getTotalNumberOfObjects());
		if($eltCount > 0)
		{
			$page++;
			while(($eltCount < $listFilter->getTotalNumberOfObjects()) && ($page < 100))
			{
				$listFilter->setDesiredPageNumber($page);
				$eltCount += $eltS->getAllElementsInGroup($p, $this->firstGroup, $this, true, $listFilter);
				$this->d()->write("p.$page retrieved $eltCount elements on a total of ".$listFilter->getTotalNumberOfObjects());
				$page++;
			}
		}
	}
}
TestRunner::test(new Debug_ElementServiceImpl_getAllElementsInGroupPaged());

class Debug_ElementServiceImpl_FillElement extends Debug_ElementServiceImpl_getAllElementsInGroup
{
	public $firstElementP;

	private function d()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("Debug_ElementServiceImpl_FillElement");
		}
		return $this->_debugLogger;
	}

	public function __construct()
	{
		parent::__construct('Debug_ElementServiceImpl_FillElement','debug ElementService fillElement');
	}
	public function run()
	{
		$groupAS = ServiceProvider::getGroupAdminService();
		$mas = ServiceProvider::getModuleAdminService();
		$dat = $this->data();
		$p = $dat->principal($dat->admin());

		$groupAS->getAllGroups($p, $mas->getModule($p, 'Contacts'), $this);
		//$this->firstGroup = $groupAS->getGroup($p, 216)->getGroup();
		$this->assertIsSet('first group is selected', $this->firstGroup);
		$this->d()->write("gets first element in group ".$this->firstGroup->getId().":".$this->firstGroup->getGroupName());

		$eltS = ServiceProvider::getElementService();
		$listFilter = ListFilter::createInstance();
		$fieldSelectorList = FieldSelectorListArrayImpl::createInstance();
		$this->assertIsSet('fieldSelectorList is createad', $fieldSelectorList);
		$fieldSelectorList->addFieldSelector('address', 'city');
		$fieldSelectorList->addFieldSelector('first_name');
		$fieldSelectorList->addFieldSelector('site', 'target');
		$fieldSelectorList->addFieldSelector('address', 'country');
		$fieldSelectorList->addFieldSelector('myText1');

		$listFilter->setFieldSelectorList($fieldSelectorList);
		$this->assertIsSet('list filter has a fieldSelectorList', $listFilter->getFieldSelectorList());
		$this->assertIsSet('fieldSelectorList has an iterator', $listFilter->getFieldSelectorList()->getListIterator());

		$pageSize = 1;
		$listFilter->setPageSize($pageSize);
		$this->assertEqual("page size is set to $pageSize", $listFilter->getPageSize(), $pageSize);

		$page = 1; $eltCount = 0;
		$listFilter->setDesiredPageNumber($page);
		$this->assertEqual("page is set to $page", $page, $listFilter->getDesiredPageNumber());
		$eltCount += $eltS->getAllElementsInGroup($p, $this->firstGroup, $this, true, $listFilter);
		$this->d()->write("p.$page retrieved $eltCount elements on a total of ".$listFilter->getTotalNumberOfObjects());
		if($eltCount > 0)
		{
			$this->assertIsSet("first ElementP retrieved", $this->firstElementP);
			$element = $this->firstElementP->getElement();
			$elementId = $element->getId();
			//$elementId = 100406;
			$module = $element->getModule();
			$element = Element::createInstance($module, $this->createFieldList(), $this);
			$element->setId($elementId);
			$this->assertEqual("module is correctly set to ".$module->getModuleName(), ($module === $element->getModule()), true);
			$this->assertEqual("fieldList is correctly set to this", ($this === $element->getFieldList()), true);
			$this->assertEqual("wigiiBag is correctly set to this", ($this === $element->getWigiiBag()), true);
			$this->assertEqual("elementId is correctly set to $elementId", $elementId, $element->getId());

			$fieldSelectorList = FieldSelectorListArrayImpl::createInstance();
			$this->assertIsSet('fieldSelectorList is createad', $fieldSelectorList);
			$fieldSelectorList->addFieldSelector('first_name');
			//$fieldSelectorList->addFieldSelector('address', 'country');
			$fieldSelectorList->addFieldSelector('last_name');
			$fieldSelectorList->addFieldSelector('text');
			$fieldSelectorList->addFieldSelector('myText1');


			$this->d()->write("calls elementService->fillElement");
			//$elementP = $eltS->fillElement($p, $element, $fieldSelectorList);
			$elementP = $eltS->fillElement($p, $element, null);
			$this->assertIsSet("element successfully retrieved", $elementP);
			$this->assertEqual("element object is not recreated", ($elementP->getElement() === $element), true);
			$rights = $elementP->getRights();
			if(isset($rights))
			{
				$this->d()->write('element right: canWriteElement='.$rights->canWriteElement().', canShareElement='.$rights->canShareElement());
			}
			else $this->d()->write('no rights on element');
		}
	}

	public function addElementP($elementP)
	{
		if(!isset($this->firstElementP))
		{
			$this->firstElementP = $elementP;
		}
	}
}
TestRunner::test(new Debug_ElementServiceImpl_FillElement());

class Debug_ElementServiceImpl_getAllGroupsContainingElement extends WigiiApiTest
implements GroupPList
{
	private function d()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("Debug_ElementServiceImpl_getAllGroupsContainingElement");
		}
		return $this->_debugLogger;
	}

	public function __construct($testId=null, $testName=null)
	{
		parent::__construct((!isset($testId)?'Debug_ElementServiceImpl_getAllGroupsContainingElement':$testId),(!isset($testName)?'debug ElementService getAllGroupsContainingElement':$testName));
	}
	public function run()
	{
		$dat = $this->data();
		$tgetElement = new Debug_ElementServiceImpl_FillElement();
		$tgetElement->setTestDataSet($dat);
		$tgetElement->setTestRunner($this->getTestRunner());
		$tgetElement->run();

		$elementP = $tgetElement->firstElementP;
		$this->assertIsSet("first elementP retrieved", $elementP);

		$p = $dat->principal($dat->admin());
		$eltS = ServiceProvider::getElementService();

		// list filter
		$listFilter = ListFilter::createInstance();
		$lExp = "groupname NOTLIKE %test%";
		$parser = TechnicalServiceProvider::getFieldSelectorLogExpParser();
		$lExp = $parser->createLogExpFromString($lExp);
		$listFilter->setFieldSelectorLogExp($lExp);

		$fskl = FieldSortingKeyListArrayImpl::createInstance();
		$fskl->addFieldSortingKey("groupname", null, false);
		$listFilter->setFieldSortingKeyList($fskl);

		$eltS->getAllGroupsContainingElement($p, $elementP->getElement(), $this, $listFilter);
	}
	public function addGroupP($groupP)
	{
		$rights = $groupP->getRights();
		if(isset($rights))
		{
			$rights = ', canModify='.$rights->canModify().', canWriteElement='.$rights->canWriteElement().', canShareElement='.$rights->canShareElement();
		}
		else $rights = ', no rights';
		$group = $groupP->getGroup();
		$this->d()->write($group->getId().":".$group->getGroupName().$rights);
	}
	public function getListIterator() {$this->fail('unsupported operation');}
	public function isEmpty() {$this->fail('unsupported operation');}
	public function count() {$this->fail('unsupported operation');}
}
TestRunner::test(new Debug_ElementServiceImpl_getAllGroupsContainingElement());

class Debug_ElementServiceImpl_getFieldFromGroupsContainingElement extends WigiiApiTest
{
	private function d()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("Debug_ElementServiceImpl_getFieldFromGroupsContainingElement");
		}
		return $this->_debugLogger;
	}

	public function __construct($testId=null, $testName=null)
	{
		parent::__construct((!isset($testId)?'Debug_ElementServiceImpl_getFieldFromGroupsContainingElement':$testId),(!isset($testName)?'debug ElementService getFieldFromGroupsContainingElement':$testName));
	}
	public function run()
	{
		$dat = $this->data();
		$tgetElement = new Debug_ElementServiceImpl_FillElement();
		$tgetElement->setTestDataSet($dat);
		$tgetElement->setTestRunner($this->getTestRunner());
		$tgetElement->run();

		$elementP = $tgetElement->firstElementP;
		$this->assertIsSet("first elementP retrieved", $elementP);
		$element = $elementP->getElement();

		$p = $this->getRootPrincipal();
		$eltS = ServiceProvider::getElementService();
		$fs = FieldSelector::createInstance('emailNotification');
		$value2stringMapper = TechnicalServiceProvider::getValueListStringMapper(';');
		$eltS->getFieldFromGroupsContainingElement($p, $fs, $element, 0, $value2stringMapper);
		$this->d()->write($value2stringMapper->getValueListString());
		TechnicalServiceProvider::recycleValueListStringMapper($value2stringMapper);
	}
}
TestRunner::test(new Debug_ElementServiceImpl_getFieldFromGroupsContainingElement());

class Debug_ElementServiceImpl_getAllElementsInGroups extends Debug_ElementServiceImpl_getAllElementsInGroup
{
	protected $rootGroupsIds;

	private function d()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("Debug_ElementServiceImpl_getAllElementsInGroups");
		}
		return $this->_debugLogger;
	}

	public function __construct($testId=null, $testName=null)
	{
		parent::__construct((!isset($testId)?'Debug_ElementServiceImpl_getAllElementsInGroups':$testId),(!isset($testName)?'debug ElementService getAll elements in groupS':$testName));
	}
	public function run()
	{
		$groupAS = ServiceProvider::getGroupAdminService();
		$mas = ServiceProvider::getModuleAdminService();
		$dat = $this->data();
		$p = $dat->principal($dat->admin());

		$groupAS->getAllGroups($p, $mas->getModule($p, 'Contacts'), $this);
		$this->d()->write("got ".count($this->rootGroupsIds)." root groups");

		$groupList = GroupListArrayImpl::createInstance();
		$groupAS->getGroupsWithoutDetail($p, $this->rootGroupsIds, $groupList);

		$eltS = ServiceProvider::getElementService();
		$listFilter = ListFilter::createInstance();
		$fieldSelectorList = FieldSelectorListArrayImpl::createInstance();
		$this->assertIsSet('fieldSelectorList is created', $fieldSelectorList);
		$fieldSelectorList->addFieldSelector('first_name');
		$fieldSelectorList->addFieldSelector('site', 'target');
		$fieldSelectorList->addFieldSelector('site');
		$fieldSelectorList->addFieldSelector('address', 'city');
		$fieldSelectorList->addFieldSelector('address', 'country');

		$listFilter->setFieldSelectorList($fieldSelectorList);
		$this->assertIsSet('list filter has a fieldSelectorList', $listFilter->getFieldSelectorList());
		$this->assertIsSet('fieldSelectorList has an iterator', $listFilter->getFieldSelectorList()->getListIterator());

		$pageSize = 5;
		$listFilter->setPageSize($pageSize);
		$this->assertEqual("page size is set to $pageSize", $listFilter->getPageSize(), $pageSize);

		$page = 1; $eltCount = 0;
		$listFilter->setDesiredPageNumber($page);
		$this->assertEqual("page is set to $page", $page, $listFilter->getDesiredPageNumber());
		$eltCount += $eltS->getAllElementsInGroups($p, $groupList, $this, true, $listFilter);
		$this->d()->write("p.$page retrieved $eltCount elements on a total of ".$listFilter->getTotalNumberOfObjects());
		if($eltCount > 0)
		{
			$page++;
			while(($eltCount < $listFilter->getTotalNumberOfObjects()) && ($page < 100))
			{
				$listFilter->setDesiredPageNumber($page);
				$eltCount += $eltS->getAllElementsInGroups($p, $groupList, $this, true, $listFilter);
				$this->d()->write("p.$page retrieved $eltCount elements on a total of ".$listFilter->getTotalNumberOfObjects());
				$page++;
			}
		}
	}

	public function addGroupP($groupP, $parentGroup)
	{
		if(!isset($parentGroup))
		{
			$group = $groupP->getGroup();
			$groupId = $group->getId();
			$this->d()->write("record root group ".$group->getGroupName()."($groupId)");
			$this->rootGroupsIds[$groupId] = $groupId;
		}
	}
}
TestRunner::test(new Debug_ElementServiceImpl_getAllElementsInGroups());

class Debug_ElementServiceImpl_insertElement extends WigiiApiTest
{
	private function d()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("Debug_ElementServiceImpl_insertElement");
		}
		return $this->_debugLogger;
	}

	public function __construct()
	{
		parent::__construct('Debug_ElementServiceImpl_insertElement','debug ElementService insert element');
	}
	public function run()
	{
		$mas = ServiceProvider::getModuleAdminService();
		$dat = $this->data();
		$p = $dat->principal($dat->admin());

		$module = $mas->getModule($p, 'Contacts');

		$groupAS = ServiceProvider::getGroupAdminService();
		$group = $groupAS->getGroupWithoutDetail($p, 222);
		$this->assertIsSet('getGroup(222) exists in DB', $group);
		$this->assertEqual('getGroup(222) is test56', $group->getGroupName(), 'test56');

		$eFieldList = FieldListArrayImpl::createInstance(false);
		$eWigiiBag = WigiiBagBaseImpl::createInstance();
		$element = Element::createInstance($module, $eFieldList, $eWigiiBag);

		// fills field list
		ServiceProvider::getConfigService()->getFields($p, $module, null, $element->getFieldList());

		$element->setFieldValue("first_name 1", "first_name");
		$this->assertEqual("first_name is correctly set", $element->getFieldValue("first_name"), "first_name 1");
		$element->setFieldValue("last_name 1", "last_name");
		$this->assertEqual("last_name is correctly set", $element->getFieldValue("last_name"), "last_name 1");
		$element->setFieldValue("Google", "link", "name");
		$this->assertEqual("link name is correctly set", $element->getFieldValue("link", "name"), "Google");
		$element->setFieldValue("www.google.ch", "link", "url");
		$this->assertEqual("link url is correctly set", $element->getFieldValue("link", "url"), "www.google.ch");
		$element->setFieldValue("city 1", "address", "city");
		$this->assertEqual("address city is correctly set", $element->getFieldValue("address", "city"), "city 1");
		$element->setFieldValue("street 1", "address", "street");
		$this->assertEqual("address street is correctly set", $element->getFieldValue("address", "street"), "street 1");


		$eltS = ServiceProvider::getElementService();
		$idBeforeInsert = $element->getId();
		$eltS->insertElement($p, $element, $group->getId());
		$this->assertNotNull("element id has been set", $element->getId());
		$this->assertEqual("fieldlist instance is set and untouched", $eFieldList === $element->getFieldList(), true);
		$this->assertEqual("wigiibag instance is set and untouched", $eFieldList === $element->getFieldList(), true);
		// wigii bag is not touched
		$this->assertEqual("first_name is not touched", $element->getFieldValueBeforeInsert("first_name"), "first_name 1");
		$this->assertEqual("last_name is not touched", $element->getFieldValueBeforeInsert("last_name"), "last_name 1");
		$this->assertEqual("link name is not touched", $element->getFieldValueBeforeInsert("link", "name"), "Google");
		$this->assertEqual("link url is not touched", $element->getFieldValueBeforeInsert("link", "url"), "www.google.ch");
		$this->assertEqual("address city is not touched", $element->getFieldValueBeforeInsert("address", "city"), "city 1");
		$this->assertEqual("address street is not touched", $element->getFieldValueBeforeInsert("address", "street"), "street 1");

		$element->getWigiiBag()->reset();
		$element->getFieldList()->reset();
		$elementP = $eltS->fillElement($p, $element);
		$this->assertIsSet("element has been inserted in db", $elementP);
		$element = $elementP->getElement();
		$this->d()->write("element has been created the ".$element->getSys_creationDate());
		$this->d()->write("element first name: ".$element->getFieldValue("first_name"));
		$this->d()->write("element last name: ".$element->getFieldValue("last_name"));
		$this->d()->write("element link: ".$element->getFieldValue("link", "name")." ".$element->getFieldValue("link", "url"));
		$this->d()->write("element address: ".$element->getFieldValue("address", "street")." ".$element->getFieldValue("address", "city"));

		$eltS->deleteElement($p, $element->getId());
		$element->getWigiiBag()->reset();
		$element->getFieldList()->reset();
		$elementP = $eltS->fillElement($p, $element);
		$this->assertNotSet("element has been deleted from db", $elementP);
	}
}
TestRunner::test(new Debug_ElementServiceImpl_insertElement());

class Debug_ElementServiceImpl_updateElement extends WigiiApiTest
{
	private function d()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("Debug_ElementServiceImpl_updateElement");
		}
		return $this->_debugLogger;
	}

	public function __construct()
	{
		parent::__construct('Debug_ElementServiceImpl_updateElement','debug ElementService update element');
	}
	public function run()
	{
		$mas = ServiceProvider::getModuleAdminService();
		$dat = $this->data();
		$p = $dat->principal($dat->admin());

		$module = $mas->getModule($p, 'Contacts');

		$groupAS = ServiceProvider::getGroupAdminService();
		$group = $groupAS->getGroupWithoutDetail($p, 222);
		$this->assertIsSet('getGroup(222) exists in DB', $group);
		$this->assertEqual('getGroup(222) is test56', $group->getGroupName(), 'test56');

		$element = Element::createInstance($module, FieldListArrayImpl::createInstance(false), WigiiBagBaseImpl::createInstance());

		// fills field list
		ServiceProvider::getConfigService()->getFields($p, $module, null, $element->getFieldList());

		$element->setFieldValue("first_name 2", "first_name");
		$this->assertEqual("first_name is correctly set", $element->getFieldValue("first_name"), "first_name 2");
		$element->setFieldValue("last_name 2", "last_name");
		$this->assertEqual("last_name is correctly set", $element->getFieldValue("last_name"), "last_name 2");
		$element->setFieldValue("Google 2", "link", "name");
		$this->assertEqual("link name is correctly set", $element->getFieldValue("link", "name"), "Google 2");
		$element->setFieldValue("www.google.de", "link", "url");
		$this->assertEqual("link url is correctly set", $element->getFieldValue("link", "url"), "www.google.de");
		$element->setFieldValue("city 2", "address", "city");
		$this->assertEqual("address city is correctly set", $element->getFieldValue("address", "city"), "city 2");
		$element->setFieldValue("street 2", "address", "street");
		$this->assertEqual("address street is correctly set", $element->getFieldValue("address", "street"), "street 2");

		$eltS = ServiceProvider::getElementService();
		$eltS->insertElement($p, $element, $group->getId());
		$this->assertNotNull("element id has been set", $element->getId());

		// updates element
		$element->setFieldValue("first_name 21", "first_name");
		$this->assertEqual("first_name is correctly updated", $element->getFieldValue("first_name"), "first_name 21");
		$element->setFieldValue("last_name 21", "last_name");
		$this->assertEqual("last_name is correctly set", $element->getFieldValue("last_name"), "last_name 21");
		$element->setFieldValue("city 21", "address", "city");
		$this->assertEqual("address city is correctly updated", $element->getFieldValue("address", "city"), "city 21");
		$element->setFieldValue("country 21", "address", "country");
		$this->assertEqual("address street is correctly updated", $element->getFieldValue("address", "country"), "country 21");
		$element->setFieldValue("to test the insertion of a new text during update", "text");
		$this->assertEqual("address street is correctly updated", $element->getFieldValue("text"), "to test the insertion of a new text during update");

		$fieldSelectorList = FieldSelectorListArrayImpl::createInstance();
		$this->assertIsSet('fieldSelectorList is created', $fieldSelectorList);
		$fieldSelectorList->addFieldSelector('first_name');
		$fieldSelectorList->addFieldSelector('last_name');
		$fieldSelectorList->addFieldSelector('address', 'city');
		$fieldSelectorList->addFieldSelector('address', 'country');
		$fieldSelectorList->addFieldSelector('text');

		$eltS->updateElement($p, $element, $fieldSelectorList);
		// wigii bag is not touched
		$this->assertEqual("first_name not touched", $element->getFieldValue("first_name"), "first_name 21");
		$this->assertEqual("last_name not touched", $element->getFieldValue("last_name"), "last_name 21");
		$this->assertEqual("address city not touched", $element->getFieldValue("address", "city"), "city 21");
		$this->assertEqual("address street not touched", $element->getFieldValue("address", "country"), "country 21");
		$this->assertEqual("address street not touched", $element->getFieldValue("text"), "to test the insertion of a new text during update");

		// reads back updated element
		$element->getWigiiBag()->reset();
		$element->getFieldList()->reset();
		$elementP = $eltS->fillElement($p, $element);
		$this->assertIsSet("element has been read from db", $elementP);
		$element = $elementP->getElement();
		$this->d()->write("element has been updated the ".$element->getSys_date());
		$this->d()->write("element first name: ".$element->getFieldValue("first_name"));
		$this->d()->write("element last name: ".$element->getFieldValue("last_name"));
		$this->d()->write("element link: ".$element->getFieldValue("link", "name")." ".$element->getFieldValue("link", "url"));
		$this->d()->write("element address: ".$element->getFieldValue("address", "street")." "
											.$element->getFieldValue("address", "city")." "
											.$element->getFieldValue("address", "country"));
		$this->d()->write("element text: ".$element->getFieldValue("text"));

		$eltS->deleteElement($p, $element->getId());
		$element->getWigiiBag()->reset();
		$element->getFieldList()->reset();
		$elementP = $eltS->fillElement($p, $element);
		$this->assertNotSet("element has been deleted from db", $elementP);
	}
}
TestRunner::test(new Debug_ElementServiceImpl_updateElement());

class Debug_ElementServiceImpl_shareElement extends WigiiApiTest implements GroupPList
{
	private $sharedGroupId;
	private $sharedGroupFound;

	private function d()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("Debug_ElementServiceImpl_shareElement");
		}
		return $this->_debugLogger;
	}

	public function __construct()
	{
		parent::__construct('Debug_ElementServiceImpl_shareElement','debug ElementService share element');
	}
	public function run()
	{
		$mas = ServiceProvider::getModuleAdminService();
		$dat = $this->data();
		$p = $dat->principal($dat->admin());

		$module = $mas->getModule($p, 'Contacts');

		$groupAS = ServiceProvider::getGroupAdminService();
		$group = $groupAS->getGroupWithoutDetail($p, 222);
		$this->assertIsSet('getGroup(222) exists in DB', $group);
		$this->assertEqual('getGroup(222) is test56', $group->getGroupName(), 'test56');

		$element = Element::createInstance($module, FieldListArrayImpl::createInstance(false), WigiiBagBaseImpl::createInstance());

		// fills field list
		ServiceProvider::getConfigService()->getFields($p, $module, null, $element->getFieldList());

		$element->setFieldValue("Test sharing", "first_name");
		$this->assertEqual("first_name is correctly set", $element->getFieldValue("first_name"), "Test sharing");
		$element->setFieldValue("Test sharing", "last_name");
		$this->assertEqual("last_name is correctly set", $element->getFieldValue("last_name"), "Test sharing");

		$eltS = ServiceProvider::getElementService();
		$eltS->insertElement($p, $element, $group->getId());
		$this->assertNotNull("element id has been set", $element->getId());

		// shares element with group 281
		$this->sharedGroupId = 281; $this->sharedGroupFound = false;
		$eltS->shareElement($p, $element->getId(), $this->sharedGroupId);

		// checks that group 281 contains element
		$eltS->getAllGroupsContainingElement($p, $element, $this);
		$this->assertEqual("element is shared with group ".$this->sharedGroupId, $this->sharedGroupFound, true);

		// unshares element with group 281
		$this->sharedGroupId = 281; $this->sharedGroupFound = false;
		try
		{
			$eltS->unshareElement($p, $element->getId(), array($this->sharedGroupId, 222));
			$this->fail("unshareElement deleted last share instead of throwing DATA_INTEGRITY_ERROR");
		}
		catch(ElementServiceException $ese)
		{
			if($ese->getCode() === ElementServiceException::DATA_INTEGRITY_ERROR)
			{
				$this->d()->write("prevented deleting last share by throwing a DATA_INTEGRITY_ERROR exception");
			}
			else throw $ese;
		}


		// checks that group 281 does not contain element
		$eltS->getAllGroupsContainingElement($p, $element, $this);
		$this->assertEqual("element is not shared with group ".$this->sharedGroupId, $this->sharedGroupFound, false);

		// shares again element with group 281
		$this->sharedGroupId = 281; $this->sharedGroupFound = false;
		$eltS->shareElement($p, $element->getId(), $this->sharedGroupId);

		// checks that group 281 contains element
		$eltS->getAllGroupsContainingElement($p, $element, $this);
		$this->assertEqual("element is shared with group ".$this->sharedGroupId, $this->sharedGroupFound, true);

		// cleanup
		$eltS->deleteElement($p, $element->getId());
		$element->getWigiiBag()->reset();
		$element->getFieldList()->reset();
		$elementP = $eltS->fillElement($p, $element);
		$this->assertNotSet("element has been deleted from db", $elementP);
	}

	public function addGroupP($groupP)
	{
		$rights = $groupP->getRights();
		if(isset($rights))
		{
			$rights = ', canModify='.$rights->canModify().', canWriteElement='.$rights->canWriteElement().', canShareElement='.$rights->canShareElement();
		}
		else $rights = ', no rights';
		$group = $groupP->getGroup();
		$groupId = $group->getId();
		$this->d()->write("$groupId:".$group->getGroupName().$rights);
		$this->sharedGroupFound = ($this->sharedGroupFound || ($this->sharedGroupId == $groupId));
	}
	public function getListIterator() {$this->fail('unsupported operation');}
	public function isEmpty() {$this->fail('unsupported operation');}
	public function count() {$this->fail('unsupported operation');}
}
TestRunner::test(new Debug_ElementServiceImpl_shareElement());

class Debug_ElementServiceImpl_fillWigiiBagWithAllElementsInGroup extends Debug_ElementServiceImpl_getAllElementsInGroup
{
	private function d()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("Debug_ElementServiceImpl_fillWigiiBagWithAllElementsInGroup");
		}
		return $this->_debugLogger;
	}

	public function __construct()
	{
		parent::__construct('Debug_ElementServiceImpl_fillWigiiBagWithAllElementsInGroup','debug ElementService fill wigiibag with elements in group');
	}

	public function run()
	{
		$groupAS = ServiceProvider::getGroupAdminService();
		$mas = ServiceProvider::getModuleAdminService();
		$dat = $this->data();
		$p = $dat->principal($dat->admin());

		$groupAS->getAllGroups($p, $mas->getModule($p, 'Contacts'), $this);
		//$this->firstGroup = $groupAS->getGroup($p, 280)->getGroup();
		$this->assertIsSet('first group is selected', $this->firstGroup);
		$this->d()->write("gets all elements in group ".$this->firstGroup->getId().":".$this->firstGroup->getGroupName());

		$eltS = ServiceProvider::getElementService();
		$eltS->fillWigiiBagWithAllElementsInGroup($p, $this->firstGroup, $this);
	}
}
TestRunner::test(new Debug_ElementServiceImpl_fillWigiiBagWithAllElementsInGroup());

class Debug_ElementServiceImpl_fillWigiiBagWithAllElementsInGroups extends Debug_ElementServiceImpl_getAllElementsInGroups
{
	private function d()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("Debug_ElementServiceImpl_fillWigiiBagWithAllElementsInGroups");
		}
		return $this->_debugLogger;
	}

	public function __construct()
	{
		parent::__construct('Debug_ElementServiceImpl_fillWigiiBagWithAllElementsInGroups','debug ElementService fill wigiibag with elements in groupS');
	}

	public function run()
	{
		$groupAS = ServiceProvider::getGroupAdminService();
		$mas = ServiceProvider::getModuleAdminService();
		$dat = $this->data();
		$p = $dat->principal($dat->admin());

		$groupAS->getAllGroups($p, $mas->getModule($p, 'Contacts'), $this);
		$this->d()->write("got ".count($this->rootGroupsIds)." root groups");

		$groupList = GroupListArrayImpl::createInstance();
		$groupAS->getGroupsWithoutDetail($p, $this->rootGroupsIds, $groupList);

		$eltS = ServiceProvider::getElementService();
		$listFilter = ListFilter::createInstance();
		$fieldSelectorList = FieldSelectorListArrayImpl::createInstance();
		$this->assertIsSet('fieldSelectorList is created', $fieldSelectorList);
		$fieldSelectorList->addFieldSelector('first_name');
		$fieldSelectorList->addFieldSelector('site', 'target');
		$fieldSelectorList->addFieldSelector('site');
		$fieldSelectorList->addFieldSelector('address', 'city');
		$fieldSelectorList->addFieldSelector('address', 'country');

		$listFilter->setFieldSelectorList($fieldSelectorList);
		$this->assertIsSet('list filter has a fieldSelectorList', $listFilter->getFieldSelectorList());
		$this->assertIsSet('fieldSelectorList has an iterator', $listFilter->getFieldSelectorList()->getListIterator());

		$pageSize = 5;
		$listFilter->setPageSize($pageSize);
		$this->assertEqual("page size is set to $pageSize", $listFilter->getPageSize(), $pageSize);

		$page = 1; $eltCount = 0;
		$listFilter->setDesiredPageNumber($page);
		$this->assertEqual("page is set to $page", $page, $listFilter->getDesiredPageNumber());
		$eltCount += $eltS->fillWigiiBagWithAllElementsInGroups($p, $groupList, $this, true, $listFilter);
		$this->d()->write("p.$page retrieved $eltCount elements on a total of ".$listFilter->getTotalNumberOfObjects());
		if($eltCount > 0)
		{
			$page++;
			while(($eltCount < $listFilter->getTotalNumberOfObjects()) && ($page < 100))
			{
				$listFilter->setDesiredPageNumber($page);
				$eltCount += $eltS->fillWigiiBagWithAllElementsInGroups($p, $groupList, $this, true, $listFilter);
				$this->d()->write("p.$page retrieved $eltCount elements on a total of ".$listFilter->getTotalNumberOfObjects());
				$page++;
			}
		}
	}
}
TestRunner::test(new Debug_ElementServiceImpl_fillWigiiBagWithAllElementsInGroups());

class Debug_ElementServiceImpl_updateElementAttribute extends WigiiApiTest
{
	private function d()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("Debug_ElementServiceImpl_updateElementAttribute");
		}
		return $this->_debugLogger;
	}

	public function __construct()
	{
		parent::__construct('Debug_ElementServiceImpl_updateElementAttribute','debug ElementService updates element attribute');
	}

	public function run()
	{
		$mas = ServiceProvider::getModuleAdminService();
		$dat = $this->data();
		$p = $dat->principal($dat->admin());

		$module = $mas->getModule($p, 'Contacts');

		$groupAS = ServiceProvider::getGroupAdminService();
		$group = $groupAS->getGroupWithoutDetail($p, 222);
		$this->assertIsSet('getGroup(222) exists in DB', $group);
		$this->assertEqual('getGroup(222) is test56', $group->getGroupName(), 'test56');

		$element = Element::createInstance($module, FieldListArrayImpl::createInstance(), WigiiBagBaseImpl::createInstance());

		// fills field list
		ServiceProvider::getConfigService()->getFields($p, $module, null, $element->getFieldList());

		$element->setFieldValue("first_name 2", "first_name");
		$this->assertEqual("first_name is correctly set", $element->getFieldValue("first_name"), "first_name 2");
		$element->setFieldValue("last_name 2", "last_name");
		$this->assertEqual("last_name is correctly set", $element->getFieldValue("last_name"), "last_name 2");
		$element->setFieldValue("Google 2", "link", "name");
		$this->assertEqual("link name is correctly set", $element->getFieldValue("link", "name"), "Google 2");
		$element->setFieldValue("www.google.de", "link", "url");
		$this->assertEqual("link url is correctly set", $element->getFieldValue("link", "url"), "www.google.de");
		$element->setFieldValue("city 2", "address", "city");
		$this->assertEqual("address city is correctly set", $element->getFieldValue("address", "city"), "city 2");
		$element->setFieldValue("street 2", "address", "street");
		$this->assertEqual("address street is correctly set", $element->getFieldValue("address", "street"), "street 2");

		$eltS = ServiceProvider::getElementService();
		$eltS->insertElement($p, $element, $group->getId());
		$this->assertNotNull("element id has been set", $element->getId());

		// updates element
		$element->setVersion(12);
		$this->assertEqual("element version is correctly updated", $element->getVersion(), 12);

		$fieldSelectorList = FieldSelectorListArrayImpl::createInstance();
		$this->assertIsSet('fieldSelectorList is created', $fieldSelectorList);
		$fieldSelectorList->addFieldSelectorInstance(FieldSelector::createElementAttributeSelector('version'));

		$eltS->updateElement($p, $element, $fieldSelectorList);

		// reads back updated element
		$element->getWigiiBag()->reset();
		$element->getFieldList()->reset();
		$elementP = $eltS->fillElement($p, $element);
		$this->assertIsSet("element has been read from db", $elementP);
		$element = $elementP->getElement();
		$this->d()->write("element has been updated the ".$element->getSys_date());
		$this->d()->write("element version: ".$element->getVersion());

		$eltS->deleteElement($p, $element->getId());
		$element->getWigiiBag()->reset();
		$element->getFieldList()->reset();
		$elementP = $eltS->fillElement($p, $element);
		$this->assertNotSet("element has been deleted from db", $elementP);
	}
}
TestRunner::test(new Debug_ElementServiceImpl_updateElementAttribute());

class Debug_ElementServiceImpl_getSelectedElementsInGroups extends Debug_ElementServiceImpl_getAllElementsInGroup
{
	private function d()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("Debug_ElementServiceImpl_getSelectedElementsInGroups");
		}
		return $this->_debugLogger;
	}

	public function __construct($testId=null, $testName=null)
	{
		parent::__construct((!isset($testId)?'Debug_ElementServiceImpl_getSelectedElementsInGroups':$testId),(!isset($testName)?'debug ElementService get selected elements in groups':$testName));
	}
	public function run()
	{
		$groupAS = ServiceProvider::getGroupAdminService();
		$mas = ServiceProvider::getModuleAdminService();
		$dat = $this->data();
		$p = $dat->principal($dat->admin());

		// in group log exp
		//$inGroupLogExp = 'INGR(module = Contacts AND (id_group_parent = 0 OR id_group_parent = NULL))';
		//$inGroupLogExp = 'INGR(id IN (281))  AND  NOTINGR(id = 394)';
		$inGroupLogExp = 'INGR(id IN (394))  AND  NOTINGR(id = 394)';
		//$inGroupLogExp = 'ING(module = Contacts AND groupname = test4) || INGR(module = Contacts AND groupname LIKE test%)';
		//$inGroupLogExp = '!!!!INGR(module = Contacts AND groupname = test4) && !!NOTINGR(module = Contacts AND groupname = test)';
		//$inGroupLogExp = 'INGR(module = Contacts) && NOTINGR(module = Contacts AND groupname = test)';
		$parser = TechnicalServiceProvider::getFieldSelectorLogExpParser();
		$inGroupLogExp = $parser->createLogExpFromString($inGroupLogExp);
		//$this->d()->write($inGroupLogExp->displayDebug());
		$this->d()->write($parser->logExpToString($inGroupLogExp));

		// get elements
		$eltS = ServiceProvider::getElementService();
		$listFilter = ListFilter::createInstance();
		$fieldSelectorList = FieldSelectorListArrayImpl::createInstance();
		$this->assertIsSet('fieldSelectorList is created', $fieldSelectorList);
		$fieldSelectorList->addFieldSelector('first_name');
		$fieldSelectorList->addFieldSelector('site', 'target');
		$fieldSelectorList->addFieldSelector('site');
		$fieldSelectorList->addFieldSelector('address', 'city');
		$fieldSelectorList->addFieldSelector('address', 'country');

		$listFilter->setFieldSelectorList($fieldSelectorList);
		$this->assertIsSet('list filter has a fieldSelectorList', $listFilter->getFieldSelectorList());
		$this->assertIsSet('fieldSelectorList has an iterator', $listFilter->getFieldSelectorList()->getListIterator());

		$pageSize = 5;
		$listFilter->setPageSize($pageSize);
		$this->assertEqual("page size is set to $pageSize", $listFilter->getPageSize(), $pageSize);

		$page = 1; $eltCount = 0;
		$listFilter->setDesiredPageNumber($page);
		$this->assertEqual("page is set to $page", $page, $listFilter->getDesiredPageNumber());
		$eltCount += $eltS->getSelectedElementsInGroups($p, $inGroupLogExp, $this, $listFilter);
		$this->d()->write("p.$page retrieved $eltCount elements on a total of ".$listFilter->getTotalNumberOfObjects());
		if($eltCount > 0)
		{
			$page++;
			while(($eltCount < $listFilter->getTotalNumberOfObjects()) && ($page < 100))
			{
				$listFilter->setDesiredPageNumber($page);
				$eltCount += $eltS->getSelectedElementsInGroups($p, $inGroupLogExp, $this, $listFilter);
				$this->d()->write("p.$page retrieved $eltCount elements on a total of ".$listFilter->getTotalNumberOfObjects());
				$page++;
			}
		}
	}
}
TestRunner::test(new Debug_ElementServiceImpl_getSelectedElementsInGroups());

class Debug_ElementServiceImpl_fillWigiiBagWithSelectedElementsInGroups extends Debug_ElementServiceImpl_getSelectedElementsInGroups
{
	private function d()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("Debug_ElementServiceImpl_fillWigiiBagWithSelectedElementsInGroups");
		}
		return $this->_debugLogger;
	}

	public function __construct($testId=null, $testName=null)
	{
		parent::__construct((!isset($testId)?'Debug_ElementServiceImpl_fillWigiiBagWithSelectedElementsInGroups':$testId),(!isset($testName)?'debug ElementService fill wigii bag with selected elements in groups':$testName));
	}
	public function run()
	{
		$groupAS = ServiceProvider::getGroupAdminService();
		$mas = ServiceProvider::getModuleAdminService();
		$dat = $this->data();
		$p = $dat->principal($dat->admin());

		// in group log exp
		$inGroupLogExp = 'INGR(module = Contacts AND (id_group_parent = 0 OR id_group_parent = NULL))';
		//$inGroupLogExp = 'ING(module = Contacts AND groupname = test4) || INGR(module = Contacts AND groupname LIKE test%)';
		//$inGroupLogExp = 'INGR(module = Contacts AND groupname = test4) && NOTINGR(module = Contacts AND groupname = test)';
		//$inGroupLogExp = 'INGR(module = Contacts) && NOTINGR(module = Contacts AND groupname = test)';
		$parser = TechnicalServiceProvider::getFieldSelectorLogExpParser();
		$inGroupLogExp = $parser->createLogExpFromString($inGroupLogExp);
		$this->d()->write($parser->logExpToString($inGroupLogExp));

		// get elements
		$eltS = ServiceProvider::getElementService();
		$listFilter = ListFilter::createInstance();
		$fieldSelectorList = FieldSelectorListArrayImpl::createInstance();
		$this->assertIsSet('fieldSelectorList is created', $fieldSelectorList);
		$fieldSelectorList->addFieldSelector('first_name');
		$fieldSelectorList->addFieldSelector('site', 'target');
		$fieldSelectorList->addFieldSelector('site');
		$fieldSelectorList->addFieldSelector('address', 'city');
		$fieldSelectorList->addFieldSelector('address', 'country');

		$listFilter->setFieldSelectorList($fieldSelectorList);
		$this->assertIsSet('list filter has a fieldSelectorList', $listFilter->getFieldSelectorList());
		$this->assertIsSet('fieldSelectorList has an iterator', $listFilter->getFieldSelectorList()->getListIterator());

		$pageSize = 5;
		$listFilter->setPageSize($pageSize);
		$this->assertEqual("page size is set to $pageSize", $listFilter->getPageSize(), $pageSize);

		$page = 1; $eltCount = 0;
		$listFilter->setDesiredPageNumber($page);
		$this->assertEqual("page is set to $page", $page, $listFilter->getDesiredPageNumber());
		$eltCount += $eltS->fillWigiiBagWithSelectedElementsInGroups($p, $inGroupLogExp, $this, $listFilter);
		$this->d()->write("p.$page retrieved $eltCount elements on a total of ".$listFilter->getTotalNumberOfObjects());
		if($eltCount > 0)
		{
			$page++;
			while(($eltCount < $listFilter->getTotalNumberOfObjects()) && ($page < 100))
			{
				$listFilter->setDesiredPageNumber($page);
				$eltCount += $eltS->fillWigiiBagWithSelectedElementsInGroups($p, $inGroupLogExp, $this, $listFilter);
				$this->d()->write("p.$page retrieved $eltCount elements on a total of ".$listFilter->getTotalNumberOfObjects());
				$page++;
			}
		}
	}
}
TestRunner::test(new Debug_ElementServiceImpl_fillWigiiBagWithSelectedElementsInGroups());