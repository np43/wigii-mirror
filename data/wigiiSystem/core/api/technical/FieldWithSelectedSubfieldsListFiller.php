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
 * A class used to fill a FieldWithSelectedSubfieldsList based on a FieldList and a FieldSelectorList
 * Created by CWE on 14 juin 2013
 */
class FieldWithSelectedSubfieldsListFiller
{
	/**
	 * Fills the list by copying the FieldList, but using the FieldSelectorList if defined 
	 * for a given field, if field selector list defines some subfields, then only those will be selected,
	 * else if the field selector list selects the field without sufields defined, then all the subfields will be selected,
	 * else if the field selector list does not select the field or any subfield, then the field is not put in the list
	 */
	public function fillFromFieldList($fieldWithSelectedSubfieldsList, $fieldList, $fieldSelectorList=null) {		
		if(is_null($fieldWithSelectedSubfieldsList)) throw new ListException("fieldWithSelectedSubfieldsList cannot be null", ListException::INVALID_ARGUMENT);
		if(is_null($fieldList)) return;		
		// if a fieldSelectorList is defined, then fills only the selected fields and subfields
		if(isset($fieldSelectorList)) {
			// get selected fields and subfields			
			$empty = true;
			foreach($fieldSelectorList->getListIterator() as $fs) {
				// ignores element attribute selectors
				if(!$fs->isElementAttributeSelector()) {
					$fName = $fs->getFieldName();					
					$field = $fieldWithSelectedSubfieldsList->getFieldWithSelectedSubfields($fName);					
					// adds field to the list
					if(!isset($field)) {
						$field = $this->createFieldWithSelectedSubfieldsInstance($fieldList->getField($fName));
						$fieldWithSelectedSubfieldsList->addFieldWithSelectedSubfields($field); $empty = false;
					}
					$subFieldName = $fs->getSubFieldName();
					// selects subfield
					if(isset($subFieldName)) {
						$field->selectSubfield($subFieldName);
					}					
				}
			}
			if(!$empty) {
				// processes the selected fields and adds all the subfields if whole field is selected
				foreach($fieldWithSelectedSubfieldsList->getListIterator() as $field) {
					// selects all subfields if no one has been selected
					if(!$field->hasSelectedSubfields()) {
						$field->selectAllsubfields();
					}					
				}
			}
		}
		// else fills all the fields if not empty
		elseif(!$fieldList->isEmpty()) {			
			// copies each field with all subfields
			foreach($fieldList->getListIterator() as $field) {
				$field = $this->createFieldWithSelectedSubfieldsInstance($field);
				$field->selectAllsubfields();
				$fieldWithSelectedSubfieldsList->addFieldWithSelectedSubfields($field);
			}
		}		
	}
	
	protected function createFieldWithSelectedSubfieldsInstance($field) {
		return FieldWithSelectedSubfields::createInstance($field);
	}
}