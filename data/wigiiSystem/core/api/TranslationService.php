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
}


