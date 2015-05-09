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
 * An XML SAX parser events definition
 * Created by CWE on 7 juin 2013
 */
interface WigiiXmlSaxEvents
{	
	/**
	 * SAX parser Start Element event handler	 
	 * @param $parser a reference to the WigiiXmlSaxParserDFA
	 * @param $name the name of the starting element
	 * @param $attribs an associative array containing the xml attributes of the elements (key, value pairs)
	 */
	public function actOnStartElement($parser, $name, $attribs);
	
	/**
	 * SAX parser End Element event handler
 	 * @param $parser a reference to the WigiiXmlSaxParserDFA
	 * @param $name the name of the ending element	 
	 */
	public function actOnEndElement($parser, $name);
	
	/**
	 * SAX parser Char Data event handler	 
 	 * @param $parser a reference to the WigiiXmlSaxParserDFA
	 * @param $data a string containing a portion of the element value (xml text).
	 * This event can be triggered several times in order to retrieve all the xml text	 
	 */
	public function actOnCharData($parser, $data);
}