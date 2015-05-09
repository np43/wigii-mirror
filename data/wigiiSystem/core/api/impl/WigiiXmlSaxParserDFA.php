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
 * A data flow activity which implements an XML SAX parser
 * This implementation uses the underyling PHP xml_parser lib.
 * Created by CWE on 7 juin 2013
 */
class WigiiXmlSaxParserDFA implements DataFlowActivityEventDispatcher
{	
	// Object lifecycle
		
	public function reset() {
		$this->freeMemory();
		$this->caseFoldingOption = true;
		$this->outputEncoding = 'UTF-8';
	}
	
	public function freeMemory() {
		unset($this->dfaEventHandler);
		unset($this->phpXmlParser);
	}
	
	
	// configuration
	
	private $caseFoldingOption = true;
	
	/**
	 * Sets or not the CASE FOLDING option.
	 * If true, then element names and attribute names are converted to uppercase equivalent
	 */
	public function setCaseFolding($bool) {
		$this->caseFoldingOption = $bool;
	}
	
	/**
	 * Returns true if CASE FOLDING option is on
	 */
	public function isCaseFolding() {
		return $this->caseFoldingOption;
	}
	
	private $outputEncoding = 'UTF-8';
	/**
	 * Sets the character encoding of the output strings
	 * @param $encoding the encoding name, one of UTF-8, ISO-8859-1 or US-ASCII
	 */
	public function setOutputEncoding($encoding) {
		if(is_null($encoding) || $encoding == '') throw new DataFlowServiceException("invalid encoding. Should be UTF-8, ISO-8859-1 or US-ASCII", DataFlowServiceException::INVALID_ARGUMENT);
		switch(strtoupper($encoding)){
			case "UTF-8":
			case "ISO-8859-1":
			case "US-ASCII":
				$this->outputEncoding = $encoding;
				break;
			default: throw new DataFlowServiceException("invalid encoding. Should be UTF-8, ISO-8859-1 or US-ASCII", DataFlowServiceException::INVALID_ARGUMENT);
		}		
	}		
	
	// stream data event handling
	
	private $phpXmlParser;
	
	public function startOfStream($dataFlowContext) {
		$this->initializeParser();
	}
	
	public function processDataChunk($data, $dataFlowContext) {
		$synt_err = null;
		if(!xml_parse($this->phpXmlParser, $data, false)) $synt_err = $this->createSyntaxError();
		xml_parser_free($this->phpXmlParser);
		if(isset($synt_err)) throw $synt_err;
	}
		
	public function endOfStream($dataFlowContext) {
		$synt_err = null;
		if(!xml_parse($this->phpXmlParser, '', true)) $synt_err = $this->createSyntaxError();
		xml_parser_free($this->phpXmlParser);
		if(isset($synt_err)) throw $synt_err;
	}
			
	// single data event handling
		
	public function processWholeData($data, $dataFlowContext) {
		$this->initializeParser();
		$synt_err = null;
		if(!xml_parse($this->phpXmlParser, $data, true)) $synt_err = $this->createSyntaxError();
		xml_parser_free($this->phpXmlParser);
		if(isset($synt_err)) throw $synt_err;
	}
	
	// specific event handler
	
	private $dfaEventHandler;
	
	public function setEventHandler($eventHandler) {
		$this->dfaEventHandler = $eventHandler;
	}
	
	// Implementation
	
	private function initializeParser() {
		// creates and configures the parser
		$this->phpXmlParser = xml_parser_create($this->outputEncoding);
		xml_parser_set_option($this->phpXmlParser, XML_OPTION_CASE_FOLDING, $this->isCaseFolding());
		// sets the callback functions
		xml_set_element_handler($this->phpXmlParser, 
			array($this, 'actOnStartElement'), 
			array($this, 'actOnEndElement'));
		xml_set_character_data_handler($this->phpXmlParser, array($this, 'actOnCharData'));			
	}
	
	private function createSyntaxError() {		
		$xml_err_code = xml_get_error_code($this->phpXmlParser);
		return new DataFlowServiceException("Xml error: ".xml_error_string($xml_err_code).
		" at line ".xml_get_current_line_number($this->phpXmlParser).
		" at column ".xml_get_current_column_number($this->phpXmlParser), 
			DataFlowServiceException::SYNTAX_ERROR, $xml_err_code);
	}
	
	// PHP SAX parser event handling	
	
	/**
	 * SAX parser Start Element event handler
	 * See http://www.php.net/manual/en/function.xml-set-element-handler.php
	 */
	public function actOnStartElement($parser, $name, $attribs) {		
		$this->dfaEventHandler->actOnStartElement($this, $name, $attribs);
	}
	/**
	 * SAX parser End Element event handler
	 * See http://www.php.net/manual/en/function.xml-set-element-handler.php
	 */
	public function actOnEndElement($parser, $name) {		
		$this->dfaEventHandler->actOnEndElement($this, $name);
	}
	/**
	 * SAX parser Char Data event handler
	 * See http://www.php.net/manual/en/function.xml-set-character-data-handler.php
	 */
	public function actOnCharData($parser, $data) {		
		$this->dfaEventHandler->actOnCharData($this, $data);
	}
}