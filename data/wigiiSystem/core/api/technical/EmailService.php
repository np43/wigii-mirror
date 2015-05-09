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


interface EmailService {
	
	/**
	 * create and return a new instance of WigiiEmail
	 * @return WigiiEmail
	 */
	public function getEmailInstance();
	
	/**
	 * send the WigiiEmail
	 * @param $p
	 * @param $email : WigiiEmail
	 * @param $mergeData = null, array(key:email->value:array(key:merge field->value:merge data))
	 * @return nb of asynch emails to send
	 */
	public function send($p, $email, $mergeData=null);
	
}