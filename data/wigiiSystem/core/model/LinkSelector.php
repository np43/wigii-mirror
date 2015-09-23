<?php
/**
 *  This file is part of Wigii.
 *
 *  Wigii is free software: you can redistribute it and\/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *  
 *  Wigii is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  
 *  You should have received a copy of the GNU General Public License
 *  along with Wigii.  If not, see <http:\//www.gnu.org/licenses/>.
 *  
 *  @copyright  Copyright (c) 2012 Wigii 		 http://code.google.com/p/wigii/    http://www.wigii.ch
 *  @license    http://www.gnu.org/licenses/     GNU General Public License
 */

/**
 * A link selector
 * Created by CWE on 27 janvier 2014
 */
class LinkSelector extends Model
{
	private $ownerElementId;
	private $ownerElementBlocked;
	private $moduleName;
	private $fieldName;	
	private $fieldLabel;
	private $rootConfigSelector;

	public static function createInstance($ownerElementId, $fieldName, $rootConfigSelector=null)
	{
		$returnValue = new self();
		$returnValue->setOwnerElementId($ownerElementId);
		$returnValue->setFieldName($fieldName);	
		$returnValue->setRootConfigSelector($rootConfigSelector);	
		return $returnValue;
	}
	
	public static function createConfigInstance($fieldName, $moduleName=null) {
		$returnValue = new self();
		$returnValue->setFieldName($fieldName);
		if(isset($moduleName)) $returnValue->setModuleName($moduleName);
		return $returnValue;
	}

	public function getOwnerElementId()
	{
		return $this->ownerElementId;
	}
	public function setOwnerElementId($id)
	{
		$this->ownerElementId = $id;
	}

	/**
	 * Returns true if the owner element is blocked.
	 * This information is filled by the ElementService and 
	 * available only if the method 'getSubElementPathFromRoot' has been called.
	 */
	public function isOwnerElementBlocked() {
		return $this->ownerElementBlocked;
	}
	public function setOwnerElementBlocked($bool) {
		$this->ownerElementBlocked = $this->formatBoolean($bool);
	}
	
	public function getModuleName()
	{
		return $this->moduleName;
	}
	public function setModuleName($moduleName)
	{
		$this->moduleName = $moduleName;
	}	
	
	public function getFieldName()
	{
		return $this->fieldName;
	}
	public function setFieldName($fieldName)
	{
		$this->fieldName = $fieldName;
	}	
	
	/**
	 * Sets a translated label for the field
	 */
	public function setFieldLabel($fieldLabel) {
		$this->fieldLabel = $fieldLabel;
	}
	/**
	 * Returns a translated label for the field if set.
	 */
	public function getFieldLabel() {
		return $this->fieldLabel;
	}

	/**
	 * @return ConfigSelector returns the Root element config selector
	 */
	public function getRootConfigSelector() {
		return $this->rootConfigSelector;
	}
	
	/**
	 * Sets the root element config selector
	 * @param ConfigSelector $configSelector
	 */
	public function setRootConfigSelector($configSelector) {
		$this->rootConfigSelector = $configSelector;
	}
	
	/**
	 * Converts this link selector to its FuncExp equivalent
	 * @return FuncExp
	 */
	public function toFx() {
		return fx('ls', 
			$this->ownerElementId, 
			$this->fieldName, 
			(isset($this->rootConfigSelector)?$this->rootConfigSelector->toFx():null)
		);
	}
}