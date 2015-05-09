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
 * LinkSelectorList array implementation
 * Created by CWE on 6 février 2014
 */
class LinkSelectorListArrayImpl extends ObjectListArrayImpl implements LinkSelectorList
{		
	public static function createInstance()
	{
		$returnValue = new self();
		$returnValue->reset();
		return $returnValue;
	}
	
	public function addLinkSelector($linkSelector) {
		if(!isset($linkSelector)) throw new ListException("linkSelector cannot be null", ListException::INVALID_ARGUMENT);
		$this->objArray[] = $linkSelector;		
	}
	
	public function getFirstLinkSelector() {
		if($this->isEmpty()) return null;
		else return reset($this->objArray);
	}
	
	public function getLastLinkSelector() {
		if($this->isEmpty()) return null;
		else return end($this->objArray);
	}
}