<?php
/**
 *  This file is part of Wigii (R) software.
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
 * Created on 24 juil. 09
 * by LWR
 */

interface TranslationService {

	/**
	 * translate the key in either the current language of the service, or
	 * if defined in the language passed in parameter.
	 * the translation will first look inside the xmlNode if defined.
	 * The translation Service will always look to the principal passed in parameter
	 * to select the right dictionary to use depending on client and wigiiNamespace
	 */
	public function translate($principal, $key, $xmlNode=null, $lang=null);
	/**
	 * alias from translate()
	 */
	public function t($principal, $key, $xmlNode=null, $lang=null);
	/**
	 * the h function replace all ' with \' in the translation result
	 */
	public function h($principal, $key, $xmlNode=null, $lang=null);

	/**
	 * Returns current language in session
	 */
	public function getLanguage();
	/**
	 * Sets current language in session
	 */
	public function setLanguage($language);

	/**
	 * Returns an array of installed language in the system
	 */
	public function getInstalledLanguage();
	
	/**
	 * Returns an array of languages that can be selected by the user 
	 * @param string $key language key. If provided, then returns the language name for the given key
	 * @return Array an array of languages where key is a language key and value is the language name (label)
	 */
	public function getVisibleLanguage($key = null);
}


