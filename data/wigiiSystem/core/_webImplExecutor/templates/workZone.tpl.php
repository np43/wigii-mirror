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
 * Created on 21 July 2011 by LWR
 * Modified by Medair in 2016 for maintenance purposes (see SVN log for details)
 */

//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."start workzone.tpl.php"] = microtime(true);
$this->executionSink()->publishStartOperation("TEMPLATE workZone.tpl.php");

//by default we load the workzone.tpl.php
//we can define here specific templates for given module by adding cases
//we could select templates based on a module config.

switch($exec->getCrtModule()->getModuleName()){
	case Module::HOME_MODULE:		
		if($exec->getIsUpdating()) $this->includeTemplateHome($p, $exec);
		?><div id='elementDialog' class='elementDialog' style='display:none; top:0px; left:0px;'></div><?
		break;
	default:
		$displayOnlyStructure = ($exec->getCrtAction() == 'display') && ($exec->getCrtParameters(0) == 'workZoneStructure');
		if($exec->getIsUpdating()) $this->includeTemplateSearchBar($p, $exec);

		?><div id="groupPanel" class="groupPanel BSB"><?
			if($exec->getIsUpdating() && !$displayOnlyStructure) $this->includeTemplateGroupPanel($p, $exec);
			//asynch call groupPanel --> this allow caching and accelerate rendering
			//$exec->addJsCode($exec->getCurrentUpdateJsCode($p, 'groupPanel', 'display/groupPanel'));
		?></div><?
		// 20.12.2016 Medair (LMA): moved elementDialog div to allow workzone docking
		?><div id="dockingContainer" style=""><?
		?><div id="moduleView" class="<?if($this->isWorkzoneViewDocked()){ echo "docked"; }?>"><?
			if($exec->getIsUpdating() && !$displayOnlyStructure) $this->includeTemplateModuleView($p, $exec);
			//asynch call module view --> this allow caching and accelerate rendering
			//$exec->addJsCode($exec->getCurrentUpdateJsCode($p, 'moduleView', 'display/moduleView'));
		?></div><?		
		?><div class="collapse SB grayFont"><span>&laquo;</span></div><?
		?><div id='elementDialog' class='elementDialog <?if($this->isWorkzoneViewDocked()){ echo "docked"; }?>' style='display:none;'></div><?
		?></div><?
		/*
		else {		
		?><div id="moduleView"><?
			if($exec->getIsUpdating() && !$displayOnlyStructure) $this->includeTemplateModuleView($p, $exec);
			//asynch call module view --> this allow caching and accelerate rendering
			//$exec->addJsCode($exec->getCurrentUpdateJsCode($p, 'moduleView', 'display/moduleView'));
		?></div><?		
		*/
}

//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."end workzone.tpl.php"] = microtime(true);
$this->executionSink()->publishEndOperation("TEMPLATE workZone.tpl.php");