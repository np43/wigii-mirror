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
 * Created on 24/06/2013
 * by LWR
 */
class Links extends DataTypeInstance{
	const LINKS_TYPE_SUBITEM = 0;
	const LINKS_TYPE_LINK = 1;
	const LINKS_TYPE_QUERY = 2;
	
	/**
	* overrides parent class
	 * cette méthode contrôle les données du type de donnée. Ce contrôle ne se fait pas
	 * automatiquement, si le type de donnée évolue, il faut aussi modifier cette méthode
	 */
	public function checkValues($p, $elementId, $wigiiBag, $field){
		//calculate value based on diff between originalIds and newids
		$value = $field->getValue($elementId, $wigiiBag,"value");
		if($value==null) $value = 0;
		$originalIds = explode(",", $_POST[$field->getFieldName()."_originalIds"]);
		$newIds = explode(",", $_POST[$field->getFieldName()."_newIds"]);
		//calculation is incremented based on the change. originalIds may not include the complete list
		//i.e. the principal could not have access to all linked items
		$field->setValue($value+(count($newIds)-count($originalIds)), $elementId, $wigiiBag,"value");
	}
	
	public static function linkTypeToString($type) {
		switch($type) {
			case Links::LINKS_TYPE_SUBITEM: return 'subitem';
			case Links::LINKS_TYPE_LINK: return 'link';
			case Links::LINKS_TYPE_QUERY: return 'query';
			default: throw new ServiceException('unsupported link type: '.$type);
		}
	}
	public static function linkTypeFromString($type) {
		switch(strtolower($type)) {
			case 'subitem': return Links::LINKS_TYPE_SUBITEM;
			case 'link': return Links::LINKS_TYPE_LINK;
			case 'query': return Links::LINKS_TYPE_QUERY;
			default: throw new ServiceException('unsupported link type: '.$type);
		}
	}
}



