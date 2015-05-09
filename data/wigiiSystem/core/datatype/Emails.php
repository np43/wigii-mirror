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
class Emails extends DataTypeInstance{
	
	const EXTERNAL_ACCESS_STOP = 0;
	const EXTERNAL_ACCESS_VIEW = 1;
	const EXTERNAL_ACCESS_EDIT = 2;
	const PROOF_STATUS_NONE = 0;
	const PROOF_STATUS_VALIDATED = 1;
	const PROOF_STATUS_DELETED = 2;
	public function checkValues($p, $elementId, $wigiiBag, $field){

		$params = $field->getXml();
		$val = $field->getValue($elementId, $wigiiBag);
		//$get[$name."_value"];
		if($params["isMultiple"]=="1"){
			if(!validateEmails($val)) throw new ServiceException("invalidEmails",ServiceException::INVALID_ARGUMENT); 
		} else {
			if(!validateEmail($val)) throw new ServiceException("invalidEmail",ServiceException::INVALID_ARGUMENT); 
		}
		//on pourrait ici encore contrôler les hosts des emails pour interdire par exemple
		//les emails jetable.org, no-log.org etc... pour le moment on n'y fait rien
	}
}


