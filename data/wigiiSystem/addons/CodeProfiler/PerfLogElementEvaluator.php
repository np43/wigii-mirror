<?php
/**
 * ElementEvaluator to display perf log
 * Created by CWE 9.2.2013
 */
class PerfLogElementEvaluator extends ElementEvaluator
{
	
	/**
	 * Fetches the performance log and displays it in html
	 */
	public function displayPerfLog() {
		return TechnicalServiceProviderCodeProfilingImpl::getCodeProfiler()->getHTMLPerformanceLog($this->getPrincipal());
	}
	
}