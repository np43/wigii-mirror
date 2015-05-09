<?php
/**
 * Report_623954
 * Created by CWE 19.2.2013
 */
class Report_623954 extends ReportingElementEvaluatorWrapper
{		
	/**
	 * Returns the report as html
	 */
	public function getReportContentAsHtml() {					
		return "<p>Another generated report with a table</p><table border='1'><tr><th>Header 1</th><th>Header 2</th></tr><tr><td>row 1, cell 1</td><td>row 1, cell 2</td></tr><tr><td>row 2, cell 1</td><td>row 2, cell 2</td></tr></table>";		
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