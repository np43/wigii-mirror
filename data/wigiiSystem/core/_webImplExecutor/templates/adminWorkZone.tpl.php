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
 * Created on 21 July 2011 by LWR
 * Modified by Medair in 2016 for maintenance purposes (see SVN log for details)
 */
// 20.12.2016 Medair (LMA): moved elementDialog div to allow workzone docking
?><div id='elementDialog' class='elementDialog' style="display: none"></div><?

if(!$exec->getIsUpdating() || $exec->getIdAnswer() == "workZone"){
	?><div id="adminSearchBar" class="SB" ><?
	$this->includeTemplateAdminSearchBar($p, $exec);
	?></div><?
}
if(!$exec->getIsUpdating() || $exec->getIdAnswer() == "workZone") ?><div id="adminWorkZone"><?

switch($this->getAdminContext($p)->getSubScreen()){
	case "adminGroup":
		if(!$exec->getIsUpdating() || $exec->getIdAnswer() == "workZone") ?><div id="adminGroup" ><?
		$this->includeTemplateAdminGroup($p, $exec);
		if(!$exec->getIsUpdating() || $exec->getIdAnswer() == "workZone") ?></div><?
		break;
	case "adminGroupUser":
		if(!$exec->getIsUpdating() || $exec->getIdAnswer() == "workZone") ?><div id="adminGroupUser"><?
		$this->includeTemplateAdminGroupUser($p, $exec);
		if(!$exec->getIsUpdating() || $exec->getIdAnswer() == "workZone") ?></div><?
		break;
	case "adminUser":
		if(!$exec->getIsUpdating() || $exec->getIdAnswer() == "workZone") ?><div id="adminUser"><?
		$this->includeTemplateAdminUser($p, $exec);
		if(!$exec->getIsUpdating() || $exec->getIdAnswer() == "workZone") ?></div><?
		break;
	case "adminRole":
		if(!$exec->getIsUpdating() || $exec->getIdAnswer() == "workZone") ?><div id="adminRole"><?
		$this->includeTemplateAdminRole($p, $exec);
		if(!$exec->getIsUpdating() || $exec->getIdAnswer() == "workZone") ?></div><?
		break;
	case "adminUserAdmin":
		if(!$exec->getIsUpdating() || $exec->getIdAnswer() == "workZone") ?><div id="adminUserAdmin"><?
		$this->includeTemplateAdminUserAdmin($p, $exec);
		if(!$exec->getIsUpdating() || $exec->getIdAnswer() == "workZone") ?></div><?
		break;
	case "adminUserRole":
		if(!$exec->getIsUpdating() || $exec->getIdAnswer() == "workZone") ?><div id="adminUserRole"><?
		$this->includeTemplateAdminUserRole($p, $exec);
		if(!$exec->getIsUpdating() || $exec->getIdAnswer() == "workZone") ?></div><?
		break;
	case "adminUserUser":
		if(!$exec->getIsUpdating() || $exec->getIdAnswer() == "workZone") ?><div id="adminUserUser"><?
		$this->includeTemplateAdminUserUser($p, $exec);
		if(!$exec->getIsUpdating() || $exec->getIdAnswer() == "workZone") ?></div><?
		break;
	case "adminModuleEditor":
		if(!$exec->getIsUpdating() || $exec->getIdAnswer() == "workZone") ?><div id="adminModuleEditor"><?
		$this->includeTemplateAdminModuleEditor($p, $exec);
		if(!$exec->getIsUpdating() || $exec->getIdAnswer() == "workZone") ?></div><?
		break;
}
if(!$exec->getIsUpdating()) ?></div><?

