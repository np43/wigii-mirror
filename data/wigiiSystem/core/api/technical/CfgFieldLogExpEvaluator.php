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
 * A class which evaluates a Field Selector LogExp against a cfgField StdClass instance.
 * See function 'cfgField' in FuncExpBuilder class. 
 * Returns true, if the log exp returns true.
 * Created by Medair (CWE) on 06.04.2018
 */
class CfgFieldLogExpEvaluator extends FieldSelectorLogExpAbstractEvaluator
{
	private $_debugLogger;
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("CfgFieldLogExpEvaluator");
		}
		return $this->_debugLogger;
	}
	
	protected $record;

	// Object lifecycle

	public function reset($cfgField=null)
	{
		$this->freeMemory();
		$this->cfgField = $cfgField;
	}
	public function freeMemory() {
	    unset($this->cfgField);
	}

	// Service implementation

	/**
	 * @param StdClass $cfgField a cfgField StdClass instance
	 * @param LogExp $fsLogExp a field selector log exp.
	 * FieldSelector 'name' matches to field name, 
	 * FieldSelector 'label.l01','label.l02', ... matches to field label, 'label.value' matches to general label (without language selector)
	 * FieldSelector 'xyz' matches to field xml attribute 'xyz'. 
	 * For example fs('type') returns field data type, or fs('hidden') returns if field is hidden, etc. 
	 * @return true if cfgField matches fsLogExp.
	 */
	public function evaluate($cfgField, $fsLogExp)
	{
		if(isset($fsLogExp))
		{
		    $this->reset($cfgField);
			$returnValue = $fsLogExp->acceptLogExpVisitor($this);
			$this->freeMemory();
			return $returnValue;
		}
	}

	protected function getValue($fs){
		$fieldName = $fs->getFieldName();
		switch($fieldName) {
		    case 'name': $returnValue = $this->cfgField->name; break;
		    case 'label':
		        $subfieldName = $fs->getSubFieldName();
		        if($subfieldName==null || $subfieldName=='value') $returnValue = $this->cfgField->label;
		        elseif($this->cfgField->label) $returnValue = $this->cfgField->label[$subfieldName];
		        else $returnValue = null;
		        break;
		    default:
		        if($this->cfgField->attributes) $returnValue = $this->cfgField->attributes[$fieldName];
		        else $returnValue = null;
		        break;
		}
		//$this->debugLogger()->write($fieldName."=".$returnValue);
		return $returnValue;
	}	
}