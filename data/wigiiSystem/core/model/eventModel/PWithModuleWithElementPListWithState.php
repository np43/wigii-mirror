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
 * Created on 19 march 10
 * by LWR
 */

class PWithModuleWithElementPListWithState extends PWithModuleWithElementPList {
	
	protected $state;
	public function setState($var){ $this->state = $var; }
	public function getState(){ return $this->state; }
	
	public static function createInstance($p, $module, $elementPList, $state){
		$r = new self();
		$r->setP($p);
		$r->setModule($module);
		$r->setElementPList($elementPList);
		$r->setState($state);
		return $r;
	}
}



