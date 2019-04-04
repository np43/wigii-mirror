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
 * A Helper to manage report output 
 * Created by CWE on 26.06.2013
 */
interface ReportingOutputHelper {
	/**
	 * Returns a DataFlowActivitySelectorList describing the output stream where to send the report
	 * Precondition: the report should be a single file.	 
	 * @param Principal $principal the current principal
	 * @param string $reportName the name of the report, that will be part of the final file name stored into wigii
	 * @param string $mime the mime type of the report (for instance application/pdf)
	 * @param string $type the file extension with the dot (for instance .pdf)	
	 * type and mime cannot be both null, at least one should be defined, and if both are defined, then they should match together
	 * @param ObjectConfigurator $configurator an oject configurator which can add optional arguments such as size (the file size in byte), setInputEncoding (the encoding of the input file)
	 * @return DataFlowActivitySelectorList The ReportFacade should call DataFlowService::startStream with the returned DataFlowActivitySelectorList
	 */
	public function getDFASLForSingleReport($principal, $reportName, $mime, $type, $configurator=null);
	
	/**
	 * Returns a DataFlowActivitySelectorList describing the output stream where to send the different part names composing the multipart report
	 * Precondition: the report should be composed of several files.
	 * @param Principal $principal the current principal
	 * @param string $reportName the name of the report, that will be part of the final zip name stored into wigii
	 * @param ReportingFacade $reportFacade an instance of the ReportFacade that will provide each part of the report
	 * @return DataFLowA$ The ReportFacade should call DataFlowService::startStream with the returned DataFlowActivitySelectorList
	 * Postcondition: the callback method processReportPart on the ReportingFacade will be called for each part
	 */
	public function getDFASLForMultipartReport($principal, $reportName, $reportFacade);
}