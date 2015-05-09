<?php
/**
 * Report_623955
 * Created by CWE 19.2.2013
 */
class Report_623955 extends ReportingElementEvaluatorWrapper
{		
	/**
	 * Returns the report as html
	 */
	public function getReportContentAsHtml() {
		$gender = $this->getFieldValue(FieldSelector::createInstance('gender'));
		if($gender != null) {
			return "<p>Parameter is $gender</p>";
		}
		else return $this->getFieldValue(FieldSelector::createInstance("reportHtmlContent"));	
	}
	
	/**
	 * Returns html to display an hyperlink to download the report as a pdf document
	 */
	public function getReportAsPdfLink() {
		return '<a href="" target="_blank">Click here to download</a>';
	}
	
	/**
	 * Returns html to display an hyperlink to download the report as an excel document
	 */
	public function getReportAsExcelLink() {
		return '<a href="" target="_blank">Click here to download</a>';
	}
	
}