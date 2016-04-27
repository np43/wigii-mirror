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

/**
 * My customized ElementEvaluator
 * Created by LWR on 28 may 10
 * Updated by CWE on 4 decembre 13
 */
class CustomizedElementEvaluator extends ElementEvaluator
{
	
	private $myTrm;
	/**
	 * A template record manager that can be used to do some formatting on demand
	 */
	private function getTrm(){
		if(!isset($this->myTrm)){
			$this->myTrm = TemplateRecordManager::createInstance();
		}
		return $this->myTrm;
	}	
	
	/**
	 * If you need to translate/format all Field Selector values customize the evaluateRecord function 
	 * in the following way.
	 * @see impl/RecordEvaluator::evaluateFuncExp()
	 */
	public function evaluateFuncExp($funcExp, $caller=null) {
		$this->setTranslateAllValues(true);
		return parent::evaluateFuncExp($funcExp, $caller);
	}
}