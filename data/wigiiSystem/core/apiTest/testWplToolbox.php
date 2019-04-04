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

class Debug_WplToolbox_recordXml extends WigiiApiTest
{
	private function d()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("Debug_WplToolbox_recordXml");
		}
		return $this->_debugLogger;
	}

	public function __construct()
	{
		parent::__construct('Debug_WplToolbox_recordXml','debug WplToolbox record2xml and xml2record');
	}
	public function run()
	{
		$mas = ServiceProvider::getModuleAdminService();
		$dat = $this->data();
		$p = $dat->principal('cwe', 'Nadmin@HQ', 'cwe');
		
		$module = $mas->getModule($p, 'HQ');

		$groupAS = ServiceProvider::getGroupAdminService();
		$groupP = $groupAS->getGroup($p, 603174, $groupAS->getFieldSelectorListForGroupWithoutDetail());				
		$this->assertIsSet('getGroup(603174) exists in DB', $groupP);
		$group = $groupP->getGroup();
		$pRights = $groupP->getRights();		
		$this->assertEqual('getGroup(603174) is Reports', $group->getGroupName(), 'Reports');
		$this->assertIsSet('cwe has at least read rights on group Reports', $pRights);
		$this->assertEqual('cwe has write rights on group Reports', $pRights->canWriteElement(), true);		

		$eltPList = ElementPListArrayImpl::createInstance();
		$eltS = ServiceProvider::getElementService();
		$eltS->getAllElementsInGroup($p, $group, $eltPList);
		$this->d()->write(implode(", ", $eltPList->getElementIds()));
		
		$element = Element::createInstance($module,FieldListArrayImpl::createInstance(),WigiiBagBaseImpl::createInstance());
		$element->setId(623957);
		$eltS->fillElement($p, $element);
		
		TechnicalServiceProvider::getWplToolbox()->record2xml($p, FieldListArrayImpl::createInstance(), WigiiBagBaseImpl::createInstance(), false, FieldSelectorListArrayImpl::createInstance(), 3);
		/* TechnicalServiceProvider::getWplToolbox()->xml2record($p, "<?xml version='1.0'?><record><fields><field name='a'><value>toto</value></field></fields></record>", FieldListArrayImpl::createInstance(), WigiiBagBaseImpl::createInstance()); */
	}
}
TestRunner::test(new Debug_WplToolbox_recordXml());