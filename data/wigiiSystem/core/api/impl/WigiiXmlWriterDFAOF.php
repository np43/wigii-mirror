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
 * A data flow activity output filter which implements an Xml writer
 * Uses the underlying php xml writer lib
 * Created by CWE on 10 juin 2013
 */
class WigiiXmlWriterDFAOF implements DataFlowActivityOutputFilter
{	
	private $_debugLogger;
	private $elementStack;
	private $stackPointer;
	private $flushPointer;
	const STATE_INIT = 0;
	const STATE_PUSH = 1;
	const STATE_TEXT = 2;
	const STATE_CDATA = 3;
	const STATE_XML = 4;
	const STATE_POP = 5;
	const STATE_FINAL = 6;
	private $state;
	
	// Object lifecycle
	
	public function reset() {
		$this->freeMemory();
		$this->skipXmlHeader = false;
		$this->skipEmptyTrees = true;
		$this->outputEncoding = 'UTF-8';
		$this->elementStack = array();
		$this->state = self::STATE_INIT;
		$this->stackPointer = -1;
		$this->flushPointer = -1;
	}
	
	public function freeMemory() {
		unset($this->dataFlowContext);
		unset($this->dataFlowActivity);
		unset($this->phpXmlWriter);
		unset($this->indentString);
		unset($this->elementStack);
	}
	
	
	// Dependency injection
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("WigiiXmlWriterDFAOF");
		}
		return $this->_debugLogger;
	}
	
	private $phpXmlWriter = null;
	protected function getPhpXmlWriter() {
		if(!isset($this->phpXmlWriter)) {
			$this->phpXmlWriter = xmlwriter_open_memory();
			// sets indentation if defined
			if(isset($this->indentString)) {
				xmlwriter_set_indent($this->phpXmlWriter, true);
				xmlwriter_set_indent_string($this->phpXmlWriter, $this->indentString);
			}
			else xmlwriter_set_indent($this->phpXmlWriter, false);
		}
		return $this->phpXmlWriter;
	}
	
	// Link to data flow context for output management
	
	private $dataFlowContext;
	private $dataFlowActivity;
	public function setDataFlowContext($dataFlowContext, $dataFlowActivity) {
		$this->dataFlowContext = $dataFlowContext;
		$this->dataFlowActivity = $dataFlowActivity;
	}
	
	// Configuration
	
	private $skipXmlHeader;
	/**
	 * If true, then does not write the xml header when starting the document
	 */
	public function setSkipXmlHeader($bool) {
		$this->skipXmlHeader = $bool;
	}
	
	private $skipEmptyTrees;
	/**
	 * If true, then does not write empty tags. Also skips entire empty subtrees.
	 * It can result to no data written at all.
	 * An element is considered as non empty if it has at least one attribute or some content not null
	 */
	public function setSkipEmptyTrees($bool) {
		$this->skipEmptyTrees = $bool;
	}
	
	private $indentString = null;
	/**
	 * Sets an string for indentings tags
	 */
	public function setIndentString($s) {
		$this->indentString = $s;
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
	
	
	// Xml writer
	
	/**
	 * Starts a new element given its name
	 * This method supports variable number of arguments:
	 * call writeStartElement($name, $att1Name, $att1Val, $att2Name, $att2Val) etc
	 * The optional variable number of arguments is used to set attributes to the xml element
	 * The syntax is attribute name, followed by attribute value.	
	 * throws DataFlowServiceException::INVALID_ARGUMENT if name is null or if attributes are not matching pairs attName, attValue 
	 * @param $name element name
	 */
	public function writeStartElement($name) {
		if(is_null($name) || $name == '') throw new DataFlowServiceException("element name cannot be null or empty", DataFlowServiceException::INVALID_ARGUMENT);
		$xmlw = $this->getPhpXmlWriter();
		switch($this->state) {
			case self::STATE_INIT:
				if(!$this->skipXmlHeader) xmlwriter_start_document($xmlw, '1.0', $this->outputEncoding, 'yes');
			case self::STATE_PUSH:
			case self::STATE_POP:
				$this->state = self::STATE_PUSH;
				break;
			default:
				throw new DataFlowServiceException("invalid xml state, cannot start an element here", DataFlowServiceException::INVALID_STATE);
		}
		if($this->stackPointer >= 0) {
			$xml = xmlwriter_output_memory($xmlw, true);
			if(isset($xml) && $xml != '') $this->elementStack[$this->stackPointer] = $xml;
		}
		$this->stackPointer++;
		xmlwriter_start_element($xmlw, $name);
				
		// writes attributes if any
		if(func_num_args() > 1) {
			$args = func_get_args();
			$this->writeAttributeSequence($args, 1);
		}
	}
	
	/**
	 * Adds an attribute to the current start element tag
	 * Precondition: this method works only if no content has been written (start element is still open)
	 * This method can be called several times
	 * @param $name attribute name (no checks is done if an attribute with same name already exists)
	 * @param $value attribute value
	 */
	public function writeAttribute($name, $value) {
		if(is_null($name) || $name=='') throw new DataFlowServiceException("attribute name cannot be null or empty", DataFlowServiceException::INVALID_ARGUMENT);
		// does not write any emptry attribute if skip empty trees is true
		if($this->skipEmptyTrees && (is_null($value) || $value=='')) return;
		$xmlw = $this->getPhpXmlWriter();
		switch($this->state) {
			case self::STATE_PUSH:
				$this->state = self::STATE_PUSH;
				break;
			default:
				throw new DataFlowServiceException("invalid xml state, cannot write an attribute here", DataFlowServiceException::INVALID_STATE);			
		}
		xmlwriter_write_attribute($xmlw, $name, $value);
		$xml = xmlwriter_output_memory($xmlw, true);
		$this->elementStack[$this->stackPointer] = $xml;
		$this->flushElementStack();		
	}
	
	/**
	 * Writes an array of attributes listed in a sequence
	 * (att1Name, att1Val, att2Name, att2Val, etc)
	 * throws DataFlowServiceException::INVALID_ARGUMENT if attributes are not matching pairs attName, attValue
	 * @param $attributes an array of attributes in a sequence (passed by ref)
	 * @param $startIndex the startIndex where to find the first attribute
	 */
	public function writeAttributeSequence(&$attributes, $startIndex=0) {
		if(!is_array($attributes)) throw new DataFlowServiceException("attributes should be an array", DataFlowServiceException::INVALID_ARGUMENT);
		$n = count($attributes);
		if($n > $startIndex) {
			$i = $startIndex;
			while($i < $n) {
				// gets att name
				$attName = $attributes[$i];
				$i++; 
				// gets att value
				if($i >= $n) throw new DataFlowServiceException("attribute $attName is missing the parameter value", DataFlowServiceException::INVALID_ARGUMENT);
				else $attVal = $attributes[$i];
				$i++;
				// writes attribute
				$this->writeAttribute($attName, $attVal);
			}
		}
	}
	
	/**
	 * Ends the current element
	 */
	public function writeEndElement() {
		$xmlw = $this->getPhpXmlWriter();
		$writeFullEndElement = false;
		switch($this->state) {
			case self::STATE_PUSH:
			case self::STATE_POP:
			case self::STATE_TEXT:
				if($this->stackPointer > 0) $this->state = self::STATE_POP;
				else $this->state = self::STATE_FINAL;
				break;
			case self::STATE_CDATA:
				xmlwriter_end_cdata($xmlw);
				if($this->stackPointer > 0) $this->state = self::STATE_POP;
				else $this->state = self::STATE_FINAL;
				break;
			case self::STATE_XML:
				if($this->stackPointer > 0) $this->state = self::STATE_POP;
				else $this->state = self::STATE_FINAL;
				$writeFullEndElement = true;
				break;
			default:
				throw new DataFlowServiceException("invalid xml state, cannot end an element here", DataFlowServiceException::INVALID_ARGUMENT);				
		}
		if($writeFullEndElement) xmlwriter_full_end_element($xmlw);
		else xmlwriter_end_element($xmlw);
		if($this->state == self::STATE_FINAL && !$this->skipXmlHeader) xmlwriter_end_document($xmlw);
		if($this->flushPointer < $this->stackPointer && $this->skipEmptyTrees) {
			$xml = xmlwriter_output_memory($xmlw, true);			
			// extracts starting '>' if present and saves it in the parent
			if(!empty($xml) && $xml[0] == '>') {
				$this->elementStack[$this->stackPointer] = '>';
				$this->flushElementStack();
				$this->stackPointer--;
				$this->flushPointer = $this->stackPointer;
			}
			else $this->stackPointer--;			
		}
		else {
			$xml = xmlwriter_output_memory($xmlw, true);
			$this->elementStack[$this->stackPointer] = $xml;
			$this->flushElementStack();
			$this->stackPointer--;
			$this->flushPointer = $this->stackPointer;
		}
	}
	
	/**
	 * Writes a complete element (start, attributes, content, end)
	 * This method supports variable number of arguments:
	 * call writeTextElement($name, $content, $att1Name, $att1Val, $att2Name, $att2Val) etc
	 * The optional variable number of arguments is used to set attributes to the xml element
	 * The syntax is attribute name, followed by attribute value.	
	 * throws DataFlowServiceException::INVALID_ARGUMENT if name is null or if attributes are not matching pairs attName, attValue
	 * @param $name element name
	 * @param $content element content written as XML text, not CData
	 */
	public function writeTextElement($name, $content) {
		$this->writeStartElement($name);
		if(func_num_args() > 2) {
			$args = func_get_args();		
			$this->writeAttributeSequence($args, 2);
		}
		$this->writeText($content);
		$this->writeEndElement();
	}
	
	/**
	 * Writes a complete element (start, attributes, content, end)
	 * This method supports variable number of arguments:
	 * call writeCDataElement($name, $content, $att1Name, $att1Val, $att2Name, $att2Val) etc
	 * The optional variable number of arguments is used to set attributes to the xml element
	 * The syntax is attribute name, followed by attribute value.	
	 * throws DataFlowServiceException::INVALID_ARGUMENT if name is null or if attributes are not matching pairs attName, attValue
	 * @param $name element name
	 * @param $content element content written as XML CData
	 */
	public function writeCDataElement($name, $content) {
		$this->writeStartElement($name);
		if(func_num_args() > 2) {
			$args = func_get_args();		
			$this->writeAttributeSequence($args, 2);
		}
		$this->writeCData($content);
		$this->writeEndElement();
	}
	
	/**
	 * Writes some text in the current open element
	 * This method can be called several time, as long as writeEndElement has not been called
	 * @param $content element content written as XML text
	 */
	public function writeText($content) {
		$xmlw = $this->getPhpXmlWriter();
		switch($this->state) {
			case self::STATE_PUSH:
			case self::STATE_TEXT:
				$this->state = self::STATE_TEXT;
				break;
			default:
				throw new DataFlowServiceException("invalid xml state, cannot write text here", DataFlowServiceException::INVALID_STATE);
		}
		xmlwriter_text($xmlw, $content);
		$xml = xmlwriter_output_memory($xmlw, true);
		$this->elementStack[$this->stackPointer] = $xml;
		$this->flushElementStack();
	}
	
	/**
	 * Writes some CData content in the current open element
	 * This method can be called several time, as long as writeEndElement has not been called
	 * @param $content element content written as XML CData
	 */
	public function writeCData($content) {
		$xmlw = $this->getPhpXmlWriter();
		switch($this->state) {
			case self::STATE_PUSH:
				xmlwriter_start_cdata($xmlw);
			case self::STATE_CDATA:
				$this->state = self::STATE_CDATA;
				break;
			default:
				throw new DataFlowServiceException("invalid xml state, cannot write CData here", DataFlowServiceException::INVALID_STATE);
		}
		xmlwriter_text($xmlw, $content);
		$xml = xmlwriter_output_memory($xmlw, true);
		$this->elementStack[$this->stackPointer] = $xml;
		$this->flushElementStack();
	}
	
	/**
	 * Writes some raw xml in the current open element
	 * The xml string is not checked, could lead to an invalid xml document
	 * This method can be called several time, as long as writeEndElement has not been called
	 * @param $xml the xml string to be inserted as children of the current element
	 */
	public function writeXml($xml) {		
		switch($this->state) {
			case self::STATE_PUSH:
			case self::STATE_XML:
				$this->state = self::STATE_XML;
				break;
			default:
				throw new DataFlowServiceException("invalid xml state, cannot write raw xml here", DataFlowServiceException::INVALID_STATE);
		}
		$this->elementStack[$this->stackPointer] = $xml;
		$this->flushElementStack();
	}
	
	// Implementation
	
	private function flushElementStack() {
		if($this->flushPointer < $this->stackPointer) {
			while($this->flushPointer < $this->stackPointer) {
				$this->flushPointer++;
				$this->dataFlowContext->writeResultToOutput($this->elementStack[$this->flushPointer], $this->dataFlowActivity);
				$this->elementStack[$this->flushPointer] = null;
			}
		}
		else {
			$this->dataFlowContext->writeResultToOutput($this->elementStack[$this->flushPointer], $this->dataFlowActivity);
			$this->elementStack[$this->flushPointer] = null;
		}
	}
}