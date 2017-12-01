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
 * Created by LWR on 3 déc. 09
 * Modified by CWE on 11.03.2016 to limit Blobs size to prevent loss of data and keep CKEditor running
 * Modified by Medair(CWE) on 22.09.2017 to limit Blobs size to 512Ko if htmlArea=1, to 8Mo if persisted in DB, and no limit if not persisted.
 */
class Blobs extends DataTypeInstance {
	
	/**
	* overrides parent class
	* cette méthode contrôle les données du type de donnée. Ce contrôle ne se fait pas
	* automatiquement, si le type de donnée évolue, il faut aussi modifier cette méthode
	*/
	public function checkValues($p, $elementId, $wigiiBag, $field){
		$val=$wigiiBag->getValue($elementId, 'Blobs', $field->getFieldName());
		$fieldXml = $field->getXml();
		if($val && $fieldXml['doNotPersist'] !='1') {		    
			$size = strlen($val);
			/* CWE 11.03.2016: limits the Blobs size to 512Ko to keep CKEditor running smoothly. Blobs SQL limit is MediumText: 16Mo.*/
			if($fieldXml['htmlArea']=='1') $allowed = 512*1024;
			/* CWE 22.09.2017: else allows 8Mo */
			else $allowed = 8*1024*1024;
			if($size>$allowed) {
				throw new RecordException(str_replace(array('$$chars$$', '$$size$$','$$allowed$$'), array($size-$allowed, $size,$allowed), ServiceProvider::getTranslationService()->t($p,'exceedBlobsLimit')), RecordException::INVALID_ARGUMENT);
			}
		}
	}
}


