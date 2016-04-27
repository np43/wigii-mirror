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
 * A Reporting Engine facade 
 * Created by CWE on 26.06.2013
 */
interface ReportingFacade {
	// Object lifecycle
	
	/**
	 * Resets the object to its initial state
	 */
	public function reset();
	/**
	 * Frees the memory used by this object
	 */
	public function freeMemory();
	/**
	 * Returns true if this facade is already in use
	 * The method reset should set this flag to true
	 * The method freeMemory should set this flag to false
	 */
	public function isLockedForUse();
	
	
	// Dependency injection
	
	/**
	 * Sets the ReportingOutputHelper to be used to save the report stream	 
	 */
	public function setReportingOutputHelper($reportingOutputHelper);
	
	
	// Reporting functions 
	
	
	/**
	 * Executes a report and stores the created file into the given output field of type Files
	 * @param $principal the current principal
	 * @param $reportName the name of the report to be executed by the Reporting Engine
	 * @param $reportDefinition a wigii element containing the report definition (parameters configuration and values, last report execution value)
	 * @param $outputField the Field of type Files which should receive the report result
	 * @param $format the output format name (in upper case letters), one of HTML, PDF, XLS, RTF, etc. Should throw an INVALID_ARGUMENT exception if format is not supported
	 * @param $reportParams a FieldSelectorList containing the report parameters if any
	 */
	public function executeReport($principal, $reportName, $reportDefinition, $outputField, $format, $reportParams=null);
	
	
	// Multi-part report callback
	
	/**
	 * Callback to process a part of a multi-part report.
	 * The facade should call DataFlowService::startStream with the given dataFlowActivitySelectorList
	 * Then call DataFlowService::processDataChunk to consume the report part stream and then close the stream
	 * @param $reportPartName the name of the report part to process (for instance, the name of an attached image)
	 * @param $dataFlowActivitySelectorList the output stream descriptor where to send the report part.
	 * The facade can add some additional configuration parameters to the dataFlowActivitySelectorList as needed
	 */
	public function processReportPart($reportPartName, $dataFlowActivitySelectorList);
}