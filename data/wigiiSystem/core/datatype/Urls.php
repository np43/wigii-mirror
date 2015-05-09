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
 * Created on 3 déc. 09
 * by LWR
 */
class Urls extends DataTypeInstance {
	
	/**
	* overrides parent class
	* cette méthode contrôle les données du type de donnée. Ce contrôle ne se fait pas
	* automatiquement, si le type de donnée évolue, il faut aussi modifier cette méthode
	*/
	public function checkValues($p, $elementId, $wigiiBag, $field){
		
		$regex_web = '#^(((ht|f)tp(s?))\://)?(www.|[_a-zA-Z0-9\.|-]+.)[_a-zA-Z0-9\.|-]+\.([a-zA-Z0-9]{1,})(\:[0-9]+)*(/($|[a-zA-Z0-9\.\,\;\?\'\\\+&amp;%\$\#\=~_\-]+))*$#';
		$matches = array();
		$value = $field->getValue($elementId, $wigiiBag,"url");
		$params = $field->getXml();
		
		//replace any \ with /
			
		if($field->getValue($elementId, $wigiiBag,"target") =="_file"){
			//no check then
		} else if (!empty($value) && preg_match($regex_web, $value, $matches) != 0){
			//s'il n'y a pas de protocole et que c'est une url du type
			//standard on ajoute un http://
			$prot = $matches[1];
			if(false && $prot==null){
				$field->setValue("http://".$value, $elementId, $wigiiBag);
			}
		} else if(!empty($value)) {
			//dans le cas où ce n'est pas une url standard
			//on donne une erreur, sauf si le paramêtre noCheck est à 1
			//ce paramêtre est utile si l'on veut permettre n'importe quoi dans l'url
			//dans le cas par exemple d'url relative qui du coup
			//sera ajoutée à la suite du host actuel.
			if($params["noCheck"]!="1"){		
				throw new ServiceException("invalidUrl", ServiceException::INVALID_ARGUMENT);	
			}
		}
		//pas de contrôle particulier?
	}
}



