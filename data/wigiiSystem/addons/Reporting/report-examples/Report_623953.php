<?php
/**
 * Report_623953
 * Created by CWE 19.2.2013
 */
class Report_623953 extends ReportingElementEvaluatorWrapper
{		
	/**
	 * Returns the report as html
	 */
	public function getReportContentAsHtml() {					
		$first_name = $this->getFieldValue(FieldSelector::createInstance('first_name'));
		$last_name = $this->getFieldValue(FieldSelector::createInstance('last_name'));
		if($first_name != null) {
			return "<p>Hello $first_name $last_name with a beautiful image :</p><img src='images/icones/tango/150x150/emblems/address-book.png'/>";
		}
		else return $this->getFieldValue(FieldSelector::createInstance("reportHtmlContent"));		
	}
	
	/**
	 * Create a file to download the report as a pdf document
	 */
	public function getReportAsPdf() {
		$first_name = $this->getFieldValue(FieldSelector::createInstance('first_name'));
		$last_name = $this->getFieldValue(FieldSelector::createInstance('last_name'));
		if($first_name != null) {
			return array("name"=>"$first_name $last_name report", "path"=>"", "type"=>".pdf", "size"=>0, "mime"=>"application/pdf", "date"=>time(), "user"=>$this->getPrincipal()->getRealUserId(), "username"=>$this->getPrincipal()->getRealUsername());
		}		
		else {
			return array(
				"name"=>$this->getFieldValue(FieldSelector::createInstance("reportPdf", "name")), 
				"path"=>$this->getFieldValue(FieldSelector::createInstance("reportPdf", "path")), 
				"type"=>$this->getFieldValue(FieldSelector::createInstance("reportPdf", "type")), 
				"size"=>$this->getFieldValue(FieldSelector::createInstance("reportPdf", "size")), 
				"mime"=>$this->getFieldValue(FieldSelector::createInstance("reportPdf", "mime")), 
				"date"=>$this->getFieldValue(FieldSelector::createInstance("reportPdf", "date")), 
				"user"=>$this->getFieldValue(FieldSelector::createInstance("reportPdf", "user")), 
				"username"=>$this->getFieldValue(FieldSelector::createInstance("reportPdf", "username"))
			);
		}
	}
	
	/**
	 * Returns html to display an hyperlink to download the report as a pdf document
	 */
	public function getReportAsPdfLink() {
		$first_name = $this->getFieldValue(FieldSelector::createInstance('first_name'));
		$last_name = $this->getFieldValue(FieldSelector::createInstance('last_name'));
		if($first_name != null) {
			return '<a href="/wigii?first_name='.$first_name.'" target="_blank">Click here to download</a>';
		}		
		else return $this->getFieldValue(FieldSelector::createInstance("reportPdfLink"));
	}
	
	/**
	 * Returns html to display an hyperlink to download the report as an excel document
	 */
	public function getReportAsExcelLink() {
		return '<a href="" target="_blank">Click here to download</a>';
	}
	
}