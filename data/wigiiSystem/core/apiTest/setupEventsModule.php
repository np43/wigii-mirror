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
 * Setups the Events Module for admin user.
 * Created by CWE on 17 fév. 2010
 */
class SetupEventsModule extends WigiiApiTest
{
	private $_debugLogger;
	private function d()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("SetupEventsModule");
		}
		return $this->_debugLogger;
	}
	public function __construct()
	{
		parent::__construct('SetupEventsModule','Setups Events module for admin user');
	}


	public function run()
	{
		$rootPrincipal = $this->getRootPrincipal();
		$dat = $this->data();
		$dat->principal($dat->admin());
		$userAS = ServiceProvider::getUserAdminService();
		// checks UserAdminService is instance of UserAdminServiceImpl
		$this->assertEqual("UserAdminService instanceof UserAdminServiceImpl", $userAS instanceof UserAdminServiceImpl, true);

		// updates module access
		$mySqlF = TechnicalServiceProvider::getMySqlFacade();
		$dbCS = ServiceProvider::getDbAdminService()->getDbConnectionSettings($rootPrincipal);
		$mySqlF->updateField($rootPrincipal, FieldSelector::createInstance("moduleAccess"), "Users",
			LogExp::createEqualExp(FieldSelector::createInstance("username"), $dat->admin()),
			"Events", SUPDOP_ADD, $userAS->getSqlWhereClauseBuilderForSelectUsers(),
			FieldSelector::createInstance("id"), $dbCS,
			$readSep='/[\\s,;\\/:\\\\]+/', $writeSep=';');

		// checks access to module TestCase for admin user
		ServiceProvider::getAuthenticationService()->logout();
		$this->assertNotNull("admin has access to Events module", $dat->principal($dat->admin())->getModuleAccess("Events"));
	}
}
TestRunner::test(new SetupEventsModule());

class Debug_EventsModule_getAllElementsInGroup extends WigiiApiTest
implements GroupPTree, ElementPList, WigiiBag, FieldList
{
	protected $firstGroup;
	protected $wigiiBag;

	private function d()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("Debug_EventsModule_getAllElementsInGroup");
		}
		return $this->_debugLogger;
	}

	public function __construct($testId=null, $testName=null)
	{
		parent::__construct((!isset($testId)?'Debug_EventsModule_getAllElementsInGroup':$testId),(!isset($testName)?'debug ElementService getAll elements in group':$testName));
		$this->wigiiBag = array();
	}
	public function run()
	{
		$groupAS = ServiceProvider::getGroupAdminService();
		$mas = ServiceProvider::getModuleAdminService();
		$dat = $this->data();
		$p = $dat->principal($dat->admin());

		$groupAS->getAllGroups($p, $mas->getModule($p, 'Events'), $this);
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

	// FieldList

	public function addField($field)
	{
		$dtName = $field->getDataType();
		if(isset($dtName)) $dtName = $dtName->getDataTypeName();
		else $dtName = "freeText";
		$this->d()->write("adding field ".$field->getFieldName().": ".$dtName);
	}
	public function getField($fieldName)
	{
		$this->fail("not implemented");
	}
	public function doesFieldExist($fieldName)
	{
		$this->fail("not implemented");
	}
}
TestRunner::test(new Debug_EventsModule_getAllElementsInGroup());