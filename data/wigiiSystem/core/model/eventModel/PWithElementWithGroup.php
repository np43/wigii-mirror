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
 * Created on 20 nov. 09
 * by LWR
 */

class PWithElementWithGroup extends BaseEventModel {
	
	protected $element;
	public function setElement($var){ $this->element = $var; }
	public function getElement(){ return $this->element; }
	
	protected $group;
	public function setGroup($var){ $this->group = $var; }
	public function getGroup(){ return $this->group; }
	
	public static function createInstance($p, $element, $group){
		$r = new self();
		$r->setP($p);
		$r->setElement($element);
		$r->setGroup($group);
		return $r;
	}
}



