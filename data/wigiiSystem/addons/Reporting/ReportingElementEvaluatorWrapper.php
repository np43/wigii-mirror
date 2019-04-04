<?php
/**
 *  This file is part of Wigii (R) software.
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
 * Wrapper around a ReportingElementEvaluator
 * Created by CWE 19.2.2013
 */
class ReportingElementEvaluatorWrapper extends ReportingElementEvaluator
{
	private $reportingElementEvaluator;
	public function setReportingElementEvaluator($reportingElementEvaluator) {
		$this->reportingElementEvaluator = $reportingElementEvaluator;
	}
	
	// current context accessible for reporting
	
	/**
	 * Returns the element currently evaluated
	 */
	protected function getElement() {return $this->reportingElementEvaluator->getElement();}

	/**
	 * Returns the value of a field stored in underlying wigiiBag given a fieldSelector
	 */
	protected function getFieldValue($fieldSelector) {return $this->reportingElementEvaluator->getFieldValue($fieldSelector);}
	
	/**
	 * Returns the record currently evaluated
	 */
	protected function getRecord(){return $this->reportingElementEvaluator->getRecord();}

	/**
	 * Returns the principal currently evaluating this record
	 */
	protected function getPrincipal(){return $this->reportingElementEvaluator->getPrincipal();}

	/**
	 * Returns the field currently evaluated
	 */
	protected function getCurrentField(){return $this->reportingElementEvaluator->getCurrentField();}

	/**
	 * Updates current field subfield value in wigii bag with new value
	 */
	protected function updateCurrentFieldSubFieldValue($subFieldName, $newValue){return $this->reportingElementEvaluator->updateCurrentFieldSubFieldValue($subFieldName, $newValue);}

	/**
	 * Evaluates function arg and returns its value
	 */
	protected function evaluateArg($arg){return $this->reportingElementEvaluator->evaluateArg($arg);}
}