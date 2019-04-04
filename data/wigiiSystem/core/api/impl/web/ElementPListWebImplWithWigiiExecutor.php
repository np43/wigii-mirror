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
 * Created on 6 oct. 09 by LWR
 * Modified by Medair in 2016 for maintenance purposes (see SVN log for details)
 */

abstract class ElementPListWebImplWithWigiiExecutor implements ElementPList {

	private $listContext;
	protected function getListContext(){ return $this->listContext; }
	protected function setListContext($listContext){ $this->listContext = $listContext; }

	private $wigiiExecutor;
	protected function setWigiiExecutor($var){$this->wigiiExecutor = $var; }
	protected function getWigiiExecutor(){return $this->wigiiExecutor; }
	
	public function isWorkzoneViewDocked(){
		return $this->getWigiiExecutor()->isWorkzoneViewDocked();
	}
	
	private $elementPolicyEvaluator;
	/**
	 * Injects the ElementPolicyEvaluator instance to be used by the ElementPList
	 * @param ElementPolicyEvaluator $elementPolicyEvaluator
	 */
	public function setElementPolicyEvaluator($elementPolicyEvaluator) {
		$this->elementPolicyEvaluator = $elementPolicyEvaluator;
	}
	/**
	 * Returns the injeced ElementPolicyEvaluator to be used. Can be null, if no policy should be calculated.
	 * @return ElementPolicyEvaluator or null if not injected.
	 */
	protected function getElementPolicyEvaluator() {
		return $this->elementPolicyEvaluator;
	}
	
	abstract static function createInstance($wigiiExecutor, $listContext);
	
	/**
	 * Returns an iterator on this list
	 * Compatible with the foreach control structure
	 */
	public function getListIterator(){
		throw new ServiceException('unsupported by this implementation', ServiceException::UNSUPPORTED_OPERATION);
	}
	/**
	 * Returns true if the list is empty
	 */
	public function isEmpty(){
		throw new ServiceException('unsupported by this implementation', ServiceException::UNSUPPORTED_OPERATION);
	}
	/**
	 * Returns the number of items in the list
	 */
	public function count(){
		throw new ServiceException('unsupported by this implementation', ServiceException::UNSUPPORTED_OPERATION);
	}
	/**
	 * Creates a new empty FieldList
	 */
	public function createFieldList(){
		return FieldListArrayImpl::createInstance();
	}
	/**
	 * Creates an empty wigii bag
	 */
	public function createWigiiBag(){
		return WigiiBagBaseImpl::createInstance();
	}
	
	abstract public function actOnBeforeAddElementP();
	abstract public function actOnFinishAddElementP($numberOfObjects);

}

