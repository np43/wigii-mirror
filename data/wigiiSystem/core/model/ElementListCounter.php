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
 * Count a list of element
 */
class ElementListCounter implements ElementList, ElementPList
{
	private $total;
	
	public static function createInstance(){
		$r = new self();
		$r->reset();
		return $r;
	}
	
	public function reset(){
		$this->total = 0;
	}
	
	public function isEmpty(){
		return $this->getTotal() == null;
	}
	public function count(){
		return $this->getTotal();
	}
	public function getTotal(){
		return $this->total;
	}
	
	public function addElement($element){
		$this->total ++;
	}
	public function addElementP($elementP){
		$this->addElement($elementP->getDbEntity());
	}
	
	public function getListIterator(){ throw new ServiceException('', ServiceException::UNSUPPORTED_OPERATION); }
	
	public function createFieldList(){
		return FieldListArrayImpl::createInstance();
	}
	public function createWigiiBag(){
		return WigiiBagBaseImpl::createInstance();
	}
	
}