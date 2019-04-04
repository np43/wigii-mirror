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
 * A configuration include selector
 * Created by CWE on 29.03.2019
 */
class ConfigIncludeSelector extends Model
{
	private $configFilePath;
	private $configNodePath;
	private $xmlAttributes;
	
	public static function createInstance($configFilePath,$configNodePath=null,$xmlAttribute=null)
	{
		$returnValue = new self();
		$returnValue->setFilePath($configFilePath);
		$returnValue->setNodePath($configNodePath);
		$returnValue->setXmlAttr($xmlAttribute);
		return $returnValue;
	}
	
	/**
	 * @return String the configuration file logical path, including file name
	 */
	public function getFilePath() {
	    return $this->configFilePath;
	}
	/**
	 * @param String $filePath sets the configuration file logical path, including file name
	 */
	public function setFilePath($filePath) {
	    $this->configFilePath = $filePath;
	}
	
	/**
	 * @return String the xml path from root to node 
	 */
	public function getNodePath() {
	    return $this->configNodePath;
	}
    /**
     * @param String $nodePath sets the xml path from root to node
     */	
	public function setNodePath($nodePath) {
	    $this->configNodePath = $nodePath;
	}
	
	/**
	 * @return Array the array of selected attributes
	 */
	public function getXmlAttr() {
	    return $this->xmlAttributes;
	}
	/**
	 * @param Array|String $xmlAttributes sets array of selected xml attributes
	 */
	public function setXmlAttr($xmlAttributes) {
	    $this->xmlAttributes = null;
	    $this->addXmlAttr($xmlAttributes);
	}
	/**
	 * @param Array|String $xmlAttribute adds one or several xml attributes
	 */
	public function addXmlAttr($xmlAttribute) {
	    if(isset($xmlAttribute)) {
	        if(!isset($this->xmlAttributes)) $this->xmlAttributes = array();
	        if(is_array($xmlAttribute)) {
	            foreach($xmlAttribute as $attr) {
	                $this->xmlAttributes[$attr] = $attr;
	            }
	        }
	        else $this->xmlAttributes[$xmlAttribute] = $xmlAttribute;
	    }
	}	
}