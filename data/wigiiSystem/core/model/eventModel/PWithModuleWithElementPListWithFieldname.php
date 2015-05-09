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
 * Created on 18 May 2011
 * by LWR
 */

class PWithModuleWithElementPListWithFieldname extends BaseEventModel {
	
	protected $module;
	public function setModule($var){ $this->module = $var; }
	public function getModule(){ return $this->module; }
	
	protected $elementPList;
	public function setElementPList($var){ $this->elementPList = $var; }
	public function getElementPList(){ return $this->elementPList; }
	
	protected $fieldname;
	public function setFieldname($var){ $this->fieldname = $var; }
	public function getFieldname(){ return $this->fieldname; }
	
	public static function createInstance($p, $module, $elementPList, $fieldname){
		$r = new self();
		$r->setP($p);
		$r->setModule($module);
		$r->setElementPList($elementPList);
		$r->setFieldname($fieldname);
		return $r;
	}
}


