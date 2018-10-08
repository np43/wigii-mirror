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
 * A Form to gather user input for executing a given Activity
 * Created by CWE on 2 sept. 09
 * modified by LWR on 15 sept, change from ActivityForm to ActivityRecord
 * An activity could be linked to a specific Module. if not, just leave the module to null
 */
class ActivityRecord extends Record
{
	private $activity;
	public function getActivity() { return $this->activity; }
	protected function setActivity($activity) { $this->activity = $activity; }
	
	private $module;
	public function getModule(){ return $this->module; }
	public function setModule($module){ $this->module = $module; }
	
	public static function createInstance($activity=MANDATORY_ARG, $module = null, $fieldList = null, $wigiiBag = null)
	{
		$ar = new ActivityRecord();
		$ar->setActivity($activity);
		if(isset($module)) $ar->setModule($module);
		if(isset($fieldList)) $ar->setFieldList($fieldList);
		if(isset($wigiiBag)) $ar->setWigiiBag($wigiiBag);
		return $ar;
	}
}