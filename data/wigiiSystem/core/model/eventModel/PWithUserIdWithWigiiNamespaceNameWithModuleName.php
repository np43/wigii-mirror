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
 * Created on 20 nov. 09
 * by LWR
 */

class PWithUserIdWithWigiiNamespaceNameWithModuleName extends BaseEventModel {
	
	protected $userId;
	public function setUserId($var){ $this->userId = $var; }
	public function getUserId(){ return $this->userId; }
	protected $wigiiNamespace;
	public function setWigiiNamespaceName($var){ $this->wigiiNamespace = $var; }
	public function getWigiiNamespaceName(){ return $this->wigiiNamespace; }
	protected $module;
	public function setModuleName($var){ $this->module = $var; }
	public function getModuleName(){ return $this->module; }
	
	public static function createInstance($p, $userId=MANDATORY_ARG, $wigiiNamespaceName=MANDATORY_ARG, $moduleName=MANDATORY_ARG){
		$r = new self();
		$r->setP($p);
		$r->setUserId($userId);
		$r->setWigiiNamespaceName($wigiiNamespaceName);
		$r->setModuleName($moduleName);
		return $r;
	}
}


