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
 * A configuration selector
 * Created by CWE on 28 janvier 2014
 */
class ConfigSelector extends Model
{
	private $wigiiNamespaceName;
	private $moduleName;
	private $groupLogExp;

	public static function createInstanceForWigiiNamespaceConfig($wigiiNamespaceName, $moduleName) {
		$returnValue = new self();
		$returnValue->setWigiiNamespaceName($wigiiNamespaceName);
		$returnValue->setModuleName($moduleName);
		$returnValue->setGroupLogExp(null);
		return $returnValue;
	}
	public static function createInstanceForGroupConfig($groupLogExp) {
		$returnValue = new self();
		$returnValue->setGroupLogExp($groupLogExp);
		$returnValue->setWigiiNamespaceName(null);
		$returnValue->setModuleName(null);	
		return $returnValue;
	}
	public static function createInstanceForCurrentWigiiNamespace() {
		$exec = ServiceProvider::getExecutionService();
		$returnValue = ConfigSelector::createInstanceForWigiiNamespaceConfig($exec->getCrtWigiiNamespace()->getWigiiNamespaceName(), $exec->getCrtModule()->getModuleName());
		return $returnValue;
	}
	public function getWigiiNamespaceName() {
		return $this->wigiiNamespaceName;
	}
	public function setWigiiNamespaceName($wigiiNamespaceName) {
		$this->wigiiNamespaceName = $wigiiNamespaceName;
	}
	
	public function getModuleName() {
		return $this->moduleName;
	}
	public function setModuleName($moduleName) {
		$this->moduleName = $moduleName;
	}
	
	public function getGroupLogExp() {
		return $this->groupLogExp;
	}
	public function setGroupLogExp($groupLogExp) {
		$this->groupLogExp = $groupLogExp;
	}
	
	/**
	 * Sets the wigii namespace name or the group log exp according to the argument type.
	 * This method is used by the FuncExpBuilder in case of parametric expressions.
	 * @param String|LogExp $wigiiNamespaceNameOrGroupExp
	 */
	public function setWigiiNamespaceNameOrGroupExp($wigiiNamespaceNameOrGroupExp) {
		if($wigiiNamespaceNameOrGroupExp instanceof LogExp) {
			$this->setGroupLogExp($wigiiNamespaceNameOrGroupExp);
			$this->setWigiiNamespaceName(null);
			$this->setModuleName(null);			
		}
		else {
			$this->setWigiiNamespaceName($wigiiNamespaceNameOrGroupExp);
			$this->setGroupLogExp(null);
		}
	}
	
	/**
	 * Converts this ConfigSelector to its FuncExp equivalent
	 * @return FuncExp
	 */
	public function toFx() {
		if(isset($this->wigiiNamespaceName)) {
			$returnValue = fx('cs', $this->wigiiNamespaceName, $this->moduleName);
		}
		else {
			$lxFxBuilder = TechnicalServiceProvider::getFieldSelectorLogExpFuncExpBuilder();
			$returnValue = fx('cs', $lxFxBuilder->logExp2funcExp($this->groupLogExp));
			$lxFxBuilder->freeMemory();
		}
		return $returnValue;
	}
}