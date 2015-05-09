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

/*
 * MultipleElement is a technical class to make easy the import or update of multipleElement
 * Created on 31 mars 10 by LWR
 */

class MultipleElement extends Element {
	
	private $elementList;
	public function getElementList(){ return $this->elementList; }
	protected function setElementList($var){ $this->elementList = $var; }
	
	/**
	 * elementList cannot be null or empty
	 */
	public static function createInstance($elementList)
	{
		$e = new self();
		if($elementList == null || $elementList->isEmpty()) throw ElementServiceException("elementList cannot be empty", ElementServiceException::INVALID_ARGUMENT);
		$e->setElementList($elementList);
		$firstElement = reset($elementList->getListIterator())->getDbEntity();
		$e->setModule($firstElement->getModule());
		$e->setFieldList($firstElement->getFieldList());
		$e->setWigiiBag($firstElement->getWigiiBag());
		return $e;
	}
	
}



