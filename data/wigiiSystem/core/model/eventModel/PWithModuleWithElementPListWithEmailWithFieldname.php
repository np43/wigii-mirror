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

/*
 * Created on 18 May 2011
 * by LWR
 */

class PWithModuleWithElementPListWithEmailWithFieldname extends BaseEventModel {
	
	protected $module;
	public function setModule($var){ $this->module = $var; }
	public function getModule(){ return $this->module; }
	
	protected $elementPList;
	public function setElementPList($var){ $this->elementPList = $var; }
	public function getElementPList(){ return $this->elementPList; }
	
	protected $email;
	public function setEmail($var){ $this->email= $var; }
	public function getEmail(){ return $this->email; }
	
	protected $fieldname;
	public function setFieldname($var){ $this->fieldname = $var; }
	public function getFieldname(){ return $this->fieldname; }
	
	public static function createInstance($p, $module=MANDATORY_ARG, $elementPList=MANDATORY_ARG, $email=MANDATORY_ARG, $fieldname=MANDATORY_ARG){
		$r = new self();
		$r->setP($p);
		$r->setModule($module);
		$r->setElementPList($elementPList);
		$r->setEmail($email);
		$r->setFieldname($fieldname);
		return $r;
	}
}


