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
 * Created on 29 june 2010
 * by LWR
 */
class CheckInAndModifyElementFormExecutor extends EditElementFormExecutor {
	
	private $fieldname;
	public function setFieldname($fieldname){ $this->fieldname = $fieldname; }
	protected function getFieldname(){ return $this->fieldname; }
	
	public static function createInstance($wigiiExecutor, $record, $formId, $submitUrl){
		$fe = new self();
		$fe->setWigiiExecutor($wigiiExecutor);
		$fe->setRecord($record);
		$fe->setFormId($formId);
		$fe->setSubmitUrl($submitUrl);
		return $fe;
	}
	
	protected function actOnCheckedRecord($p, $exec) {
		$r = parent::actOnCheckedRecord($p, $exec);
		$elS = ServiceProvider::getElementService();
		$elS->setState_locked($p, $this->getRecord()->getId(), false);
		return $r;
	}
	
	protected function doRenderForm($p, $exec){
		$this->getRecord()->setState_locked(false);
		return parent::doRenderForm($p, $exec);
	}
}



