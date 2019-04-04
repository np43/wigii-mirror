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
 * A configuration controller which interprets the 'sameAsField' xml attribute and 
 * replaces the content of the drop-down with the content of the referenced field.
 * Created by CWE on 19 June 2015
 */
class SameAsFieldConfigController implements ConfigController
{
	private $_debugLogger;
	private $lockedForUse = true;
	
	// Object lifecycle
	
	public function reset() {
		$this->freeMemory();
		$this->lockedForUse = true;
	}
	
	public function freeMemory() {
		$this->lockedForUse = false;
	}

	public function isLockedForUse() {
		return $this->lockedForUse;
	}
	
	// Dependency injection
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("SameAsFieldConfigController");
		}
		return $this->_debugLogger;
	}
	
	// Configuration
	

	
	// ConfigController implementation
	
	public function processConfigurationNode($principal, $xmlConfig, $getWritableNode, $lp) {		
		// gets all nodes having sameAsField attribute
		$fieldNodes = $xmlConfig->xpath('//*[@sameAsField]');
		$returnValue = false;
		if(!empty($fieldNodes)) {
			// runs again the xpath on a writable node
			$writableXml = $getWritableNode->invoke();
			$fieldNodes = $writableXml->xpath('//*[@sameAsField]');			
			$this->debugLogger()->logBeginOperation('processConfigurationNode');
			
			$fieldCache = array();
			$s = ''; $first = true;
			foreach($fieldNodes as $field) {
				if($first) $first = false;
				else $s .= ', ';
				$sameAsField = (string)$field['sameAsField'];								
				
				// retrieves sourceField from cache
				$sourceField = $fieldCache[$sameAsField];
				// if not in cache, then looks for sourceField in same field list of current field (this is not to take the field from another activity)
				if(is_null($sourceField)) {
					$sourceField = $field->xpath('../'.$sameAsField);
					if(!$sourceField) throw new ConfigServiceException("Non-existing sameAsField='".$sameAsField."' attached to field ".$field->getName());
					$sourceField = $sourceField[0];
					$fieldCache[$sameAsField] = $sourceField;
				}
				$this->copyAttributes($sourceField, $field);
				//$s .= $field->getName().'='.$sourceField->getName();				
			}
			//$this->debugLogger()->write('SameAsField fields: '.$s);
			//$this->debugLogger()->write($writableXml->asXml());
			$returnValue = true;
			$this->debugLogger()->logEndOperation('processConfigurationNode');
		}		
		return $returnValue;
	}	
	
	/**
	 * Copy drop-down entries from source to destination
	 * @param SimpleXMLElement $source
	 * @param SimpleXMLElement $destination
	 * @return SimpleXMLElement a pointer to the updated destination node.
	 */
	private function copyAttributes($source, $destination) {
		$destination = dom_import_simplexml($destination);
		foreach($source->children() as $attribute) {
			if($attribute->getName() == 'label') continue;
			$attribute = dom_import_simplexml($attribute);
			$attribute = $attribute->cloneNode(true);
			$destination->appendChild($attribute);
		}
		return simplexml_import_dom($destination);
	}
}