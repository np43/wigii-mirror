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
 * A map of CalculatedFieldSelectors.
 * Key is the FieldSelector, value is the FuncExp
 * Created by CWE on 27 janvier 2014
 */
interface CalculatedFieldSelectorMap extends ObjectList
{
	/**
	 * Sets a CalculatedFieldSelector in the map.
	 * Key is the FieldSelector, value is the FuncExp.
	 * Any existing func exp under the same field selector key is replaced.
	 * @param CalculatedFieldSelector $calculatedFieldSelector
	 */
	public function setCalculatedFieldSelector($calculatedFieldSelector);
	
	/**
	 * Sets a CalculatedFieldSelector into the map given a FieldName, optional SubfieldName and FuncExp.
	 * @param String $fieldName the field name
	 * @param FuncExp $funcExp the FuncExp
	 * @param String $subFieldName an optional subFieldName
	 * 
	 */
	public function setCalculatedFieldSelectorByFieldName($fieldName, $funcExp, $subFieldName=null);
	
	/**
	 * Gets FuncExp associated to a given FieldSelector stored into the map
	 * @param FieldSelector $fieldSelector
	 * @return FunExp the selected FuncExp or null if not found
	 */
	public function getFuncExp($fieldSelector);
	
	/**
	 * Gets FuncExp associated to a given FieldSelector stored into the map
	 * given a FieldName and an optional SubFieldName
	 * @param String $fieldName fieldname
	 * @param String $subFieldName optional subfield name
	 * @return FuncExp the selected FuncExp or null if not found
	 */
	public function getFuncExpByFieldName($fieldName, $subFieldName=null);
}