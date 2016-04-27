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

class Debug_ElementServiceImpl_insertElementWithFieldSelector extends WigiiApiTest
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
		$p = $dat->principal('cwe', 'Nadmin@Global', 'cwe');
		
		$module = $mas->getModule($p, 'Contacts');

		$groupAS = ServiceProvider::getGroupAdminService();
		$groupP = $groupAS->getGroup($p, 600926, $groupAS->getFieldSelectorListForGroupWithoutDetail());				
		$this->assertIsSet('getGroup(600926) exists in DB', $groupP);
		$group = $groupP->getGroup();
		$pRights = $groupP->getRights();		
		$this->assertEqual('getGroup(600926) is Medair', $group->getGroupName(), 'Medair');
		$this->assertIsSet('cwe has at least read rights on group Medair', $pRights);
		$this->assertEqual('cwe has write rights on group Medair', $pRights->canWriteElement(), true);		
		
		$eFieldList = FieldListArrayImpl::createInstance(false);
		$eWigiiBag = WigiiBagBaseImpl::createInstance();
		$element = Element::createInstance($module, $eFieldList, $eWigiiBag);

		// fills field list
		ServiceProvider::getConfigService()->getGroupFields($p, $group, null, $element->getFieldList());
		
		
		$element->setFieldValue("first_name 1", "first_name");		
		$element->setFieldValue("last_name 1", "last_name");
		$element->setFieldValue("city 1", "address1", "city");
		$element->setFieldValue("street 1", "address1", "street");
		$element->setFieldValue("1012", "address1", "zip_code");
		$element->setFieldValue("Suisse", "address1", "country");
		$element->setFieldValue("city 1", "address2", "city");	
		$element->setFieldValue("1012", "address2", "zip_code");
		$element->setFieldValue("street 1", "address2", "street");
		$element->setFieldValue("Suisse", "address2", "country");	

		$fsl = FieldSelectorListArrayImpl::createInstance();
		$fsl->addFieldSelector("first_name");
		$fsl->addFieldSelector("last_name");
		$fsl->addFieldSelector("address1", "city");
		$fsl->addFieldSelector("address1", "street");
		//$fsl->addFieldSelector("address1", "zip_code");
		$fsl->addFieldSelector("address1", "country");
		$fsl->addFieldSelector("address2", "city");
		$fsl->addFieldSelector("address2", "street");
		$fsl->addFieldSelector("address2", "zip_code");
		$fsl->addFieldSelector("address2", "country");
		
		$eltS = ServiceProvider::getElementService();
		$idBeforeInsert = $element->getId();
		$eltS->insertElement($p, $element, $group->getId(), $fsl);
		$this->assertNotNull("element id has been set", $element->getId());
	}
}
TestRunner::test(new Debug_ElementServiceImpl_insertElementWithFieldSelector());