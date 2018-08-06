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
 * A data flow activity which maps PHP stdClass instances to an HTML table
 * Created by CWE on July 20th 2018
 */
class MapObject2HtmlTableDFA extends ObjectDFAWithFuncExpVM
{			
	// Object lifecycle
		
	public function reset() {
		parent::reset();	
	}	
	public function freeMemory() {
		unset($this->obj2HtmlMap);
		unset($this->htmlHeader);
		unset($this->htmlId);
		unset($this->keyField);
		parent::freeMemory();
	}
	
	// dependency injection
	
	private $_debugLogger;
	private function debugLogger() {
		if (!isset ($this->_debugLogger)) {
			$this->_debugLogger = DebugLogger :: getInstance("MapObject2HtmlTableDFA");
		}
		return $this->_debugLogger;
	}	
	
	// configuration
	
	private $obj2HtmlMap;
	/**
	 * Sets the map used to create an Html table using functional expressions on the incoming stdClass instance.
	 * @param array $map an array which keys are the html table column names 
	 * and the values are FuncExp or FieldSelector instances.
	 * example: array('name' => fx('concat', fs('first_name'), ' ', fs('last_name')),
	 * 				  'city' => fx('concat', fx('getAttr', fs('addr1'), 'zip_code'), ' ', fx('getAttr', fs('addr1'), 'city')),
	 * 				  'age' => fs('age'))	 
	 */
	public function setObject2HtmlTableMap($map) {
	    if(!isset($map)) unset($this->obj2HtmlMap);
		if(!is_array($map)) throw new DataFlowServiceException("object2HtmlTableMap should be an array", DataFlowServiceException::INVALID_ARGUMENT);
		$this->obj2HtmlMap = $map;
	}	
	
	private $htmlHeader;
	/**
	 * Sets the array used to create the HTML table headers
	 * @param array $map an array which keys are the html table column names and the values are the HTML table header labels
	 */
	public function setHtmlTableHeader($header) {
	    if(!isset($header)) unset($this->htmlHeader);
	    if(!is_array($header)) throw new DataFlowServiceException("htmlTableHeader should be an array", DataFlowServiceException::INVALID_ARGUMENT);
	    $this->htmlHeader = $header;
	}	

	private $htmlId;
	/**
	 * Defines an HTML ID to be put on the DIV containing the HTML table	 
	 * @param String $htmlId HTML ID
	 */
	public function setHtmlId($htmlId) {
	    $this->htmlId = $htmlId;
	}
	
	private $htmlClass;
	/**
	 * Defines an HTML class to be put on the DIV containing the HTML table
	 * @param String $class class name
	 */
	public function setHtmlClass($class) {
	    $this->htmlClass = $class;
	}
	
	private $keyField;
	/**
	 * Defines the name of the field to be used as a key
	 * If set and an HTML ID is given, then each table row gets an HTML ID equal to #HtmlID_objKeyValue
	 * @param String $name field name
	 */
	public function setKeyField($name) {
	    $this->keyField = $name;
	}	
	
	// stream data event handling
	
	public function startOfStream($dataFlowContext) {
	   parent::startOfStream($dataFlowContext);
	   
	   $headerHtml = '<div';
	   if(isset($this->htmlId)) {
	       $headerHtml .= ' id="'.$this->htmlId.'">';
	   }
	   $headerHtml .= ' class="SBIB ui-corner-all preview" style="overflow:auto;width: 100%;">';
	   $headerHtml .= '<table><tbody><tr class="header">';
	   $header = $this->htmlHeader;
	   if(!isset($header) && isset($this->obj2HtmlMap)) {
	       $header = array_keys($this->obj2HtmlMap);
	       $header = array_combine($header, $header);
	   }
	   if(!empty($header)) {
	       foreach($header as $k => $v) {
	           $headerHtml .= '<th class="lH">';
	           $headerHtml .= $v;
	           $headerHtml .= '</th>';
	       }
	   }
	   $headerHtml .= '</tr>';
	   $dataFlowContext->writeResultToOutput($headerHtml, $this);
	}
	
	public function endOfStream($dataFlowContext) {
	    $dataFlowContext->writeResultToOutput('</tbody></table></div>', $this);
	   parent::endOfStream($dataFlowContext);
	}
	
	// object event handling
				
	protected function processObject($obj, $dataFlowContext) {
		$rowHtml = '<tr';
		if(isset($this->htmlId)) {
		    $rowId = $this->getObjectId($obj);
		    if($rowId) {
		        $rowId = 'id="'.$this->htmlId."_".$rowId.'"';
		        $rowHtml .= ' '.$rowId;
		    }
		}
        $rowHtml .= '>';
		// evaluates each object attribute
		if(isset($this->obj2HtmlMap)) {
			foreach($this->obj2HtmlMap as $attr => $funcExp) {
			    $rowHtml .= '<td';
			    if(isset($attr)) {
			        $colClass = 'class="col_'.$attr.'"';
			        $rowHtml .= ' '.$colClass;
			    }
			    $rowHtml .= '><div>';
				$rowHtml .= $this->evaluateFuncExp($funcExp);
				$rowHtml .= '</div></td>';
			}
		}				
		$rowHtml .= '</tr>';
		
		// writes the HTML row to output
		$dataFlowContext->writeResultToOutput($rowHtml, $this);
	}
	
	private function getObjectId($obj) {
	    $returnValue = null;
	    if(isset($this->keyField)) {
	        if($obj instanceof ElementP || $obj instanceof Element) $returnValue = $obj->getDbEntity()->getFieldValue($this->keyField);
	        else $returnValue = $obj->{$this->keyField};
	    }
	    return $returnValue;
	}
}