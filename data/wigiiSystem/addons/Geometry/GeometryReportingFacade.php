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
 * A reporting facade for the Geometry Addon
 * Created by CWE on 16.07.2013
 */
class GeometryReportingFacade implements ReportingFacade {
	private $_debugLogger;
	private $_executionSink;
	private $reportingOutputHelper;	
	private $lockedForUse = true;
	
	// Object lifecycle
		
	public function reset() {
		$this->freeMemory();
		$this->lockedForUse = true;
	}
	
	public function freeMemory() {
		$this->lockedForUse = false;
		unset($this->reportingOutputHelper);	
	}
	
	public function isLockedForUse() {
		return $this->lockedForUse;
	}
	
	
	// Dependency injection
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("GeometryReportingFacade");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("GeometryReportingFacade");
		}
		return $this->_executionSink;
	}
	
	private $dflowS;
	public function setDataFlowService($dataFlowService)
	{
		$this->dflowS = $dataFlowService;
	}
	protected function getDataFlowService()
	{
		// autowired
		if(!isset($this->dflowS))
		{
			$this->dflowS = ServiceProvider::getDataFlowService();
		}
		return $this->dflowS;
	}
	
	public function setReportingOutputHelper($reportingOutputHelper) {
		$this->reportingOutputHelper = $reportingOutputHelper;
	}
	protected function getReportingOutputHelper() {
		return $this->reportingOutputHelper;
	}	
	
	// Reporting functions 
		
	public function executeReport($principal, $reportName, $reportDefinition, $outputField, $format, $reportParams=null) {
		$this->executionSink()->publishStartOperation("executeReport", $principal);
		try
		{
			if($format != 'TXT' && $format != 'txt') throw new GeometryException("only supports building text reports.", GeometryException::INVALID_ARGUMENT);
			// gets input expression: simplifiedExp field or first input expression.
			if(is_null($reportParams) || $reportParams->isEmpty()) throw new GeometryException("no input expression defined.", GeometryException::INVALID_ARGUMENT);
			$first = true; $pageFormat = 'A4P';
			foreach($reportParams->getListIterator() as $fs) {
				if($fs->getFieldName() == 'simplifiedExp') {
					$inputExpFs = $fs;
					break;
				}
				elseif($fs->getFieldName() == 'pageFormat') {
					$pageFormat = $reportDefinition->getFieldValue('pageFormat');
				}
				elseif($first) {
					$inputExpFs = $fs;
					$first = false;
				}				
			}
			
			// evaluates input exp
			$o = evalfx($principal, stripslashes($reportDefinition->getFieldValue($inputExpFs->getFieldName())), 'Geometry2DFL', 'GeometryCliFL');
			//$this->debugLogger()->write(($o instanceof FuncExp ? TechnicalServiceProvider::getFieldSelectorFuncExpParser()->funcExpToString($o) : (is_object($o) ? get_class($o) : $o)));
			
			// gets single report dfasl
			$reportDfasl = $this->getReportingOutputHelper()->getDFASLForSingleReport($principal, $reportName, null, '.txt');
			
			// adds a stage which plots the graph or ensures data is converted to string
			$graphDFA = dfas('DrawGraphGeometryDFA', 
					'setReportName', $reportName, 
					'setReportingOutputHelper', $this->getReportingOutputHelper(),
					'setPageFormat', $pageFormat);
			
			// if returned object is a data flow selector, then executes the data flow
			if($o instanceof DataFlowSelector) {
				$src = $o->getSource();
				$dfasl = $o->getDataFlowActivitySelectorList();		
				if(is_null($dfasl)) {
					$dfasl = dfasl($graphDFA);
					$o->setDataFlowActivitySelectorList($dfasl);
				}
				else $dfasl->addDataFlowActivitySelectorInstance($graphDFA);
				
				// appends reportDfasl to dfasl
				foreach($reportDfasl->getListIterator() as $dfas) {
					$dfasl->addDataFlowActivitySelectorInstance($dfas);
				}
				
				// runs the data flow				
				$this->getDataFlowService()->processDataFlowSelector($principal, $o);				
			}
			// else displays or serializes the object.
			else {	
				// clones report dfasl and prepends graphDFA
				$dfasl = dfasl($graphDFA);
				// appends reportDfasl to dfasl
				foreach($reportDfasl->getListIterator() as $dfas) {
					$dfasl->addDataFlowActivitySelectorInstance($dfas);
				}
				$this->getDataFlowService()->processWholeData($principal, (empty($o) ? 'Input expression evaluated to NULL. No report to build.' : $o), $dfasl);			
			}
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("executeReport", $e, $principal);
			throw $e;
		}
		$this->executionSink()->publishEndOperation("executeReport", $principal);
	}
	
	// Multi-part report callback
		
	public function processReportPart($reportPartName, $dataFlowActivitySelectorList) {
		throw new GeometryException("unsupported operation", GeometryException::UNSUPPORTED_OPERATION);
	}		
}

/**
 * A Geometry DFA which plots a graph
 * Created by CWE on 02 September 2014
 */
class DrawGraphGeometryDFA implements DataFlowActivity
{
	private $points;
	private $plotGraph;
	private $ordinal;
	
	// Object lifecycle

	public function reset() {
		$this->freeMemory();
		$this->plotGraph = false;
		$this->ordinal = 0;
		$this->reportName = 'Resulting graph';
		$this->pageFormat = 'A4P';
	}
	public function freeMemory() {
		unset($this->points);
		unset($this->reportingOutputHelper);
		unset($this->reportName);
		unset($this->pageFormat);
	}

	private $_debugLogger;
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("DrawGraphGeometryDFA");
		}
		return $this->_debugLogger;
	}
	
	// configuration
	
	private $reportName;
	public function setReportName($reportName) {
		$this->reportName = $reportName;
	}
	
	private $reportingOutputHelper;
	public function setReportingOutputHelper($reportingOutputHelper) {
		$this->reportingOutputHelper = $reportingOutputHelper;
	}
	
	private $pageFormat;
	public function setPageFormat($pageFormat) {
		$this->pageFormat = $pageFormat;
	}
	
	// stream data event handling

	public function startOfStream($dataFlowContext) {
		$this->plotGraph = false;
		$this->ordinal = 0;
	}
	public function processDataChunk($data, $dataFlowContext) {
		//$this->debugLogger()->logBeginOperation('processDataChunk');
		// if first data, then determines if plot graph or convert to string
		if($this->ordinal < 1) {
			// plot graph if data instance of WigiiGraphNode
			$this->plotGraph = ($data instanceof WigiiGraphNode);
			$this->points = array();
		}
		
		// if plot graph: puts points into buffer and sets ordinal
		if($this->plotGraph) {
			$data->setAttribute('ordinal', $this->ordinal);
			$this->points[] = $data;
			//$this->debugLogger()->write('stores point '.$this->ordinal);
		}
		// else converts data to string
		else $dataFlowContext->writeResultToOutput($this->data2string($data), $this);
		$this->ordinal++;
		//$this->debugLogger()->logEndOperation('processDataChunk');
	}
	public function endOfStream($dataFlowContext) {
		// if plot graph: generates html + javascript
		if($this->plotGraph) {
			// changes file type to html
			$this->reportingOutputHelper->getDFASLForSingleReport($dataFlowContext->getPrincipal(), $this->reportName, null, '.html');
			// writes html + js
			$this->writeHtmlForGraph($dataFlowContext);
		}
	}


	// single data event handling

	public function processWholeData($data, $dataFlowContext) {
		$this->startOfStream($dataFlowContext);
		// if WigiiGraph and not empty, then processes each node
		if($data instanceof WigiiGraph && !$data->isEmpty()) {
			foreach($data->getGraphNodeIterator() as $node) {
				$this->processDataChunk($node, $dataFlowContext);
			}
		}
		// else processes data as a whole.
		else $this->processDataChunk($data, $dataFlowContext);
		$this->endOfStream($dataFlowContext);
	}
	
	// implementation
	
	/**
	 * Converts any kind of data to a string representation.
	 * This method is the last chance to convert data to string before it is written to the report.
	 * @param Any $data
	 * @return String
	 */
	protected function data2string($data) {
		if(is_string($data)) return $data;
		elseif($data instanceof FuncExp) return TechnicalServiceProvider::getFieldSelectorFuncExpParser()->funcExpToString($data);
		elseif(is_object($data) && !($data instanceof stdClass)) {
			if(method_exists($data, 'toString')) return $data->toString();
			elseif(method_exists($data, '__toString()')) return (string)$data;
			else return get_class($data);
		}
		else return json_encode($data);
	}
	
	protected function writeHtmlForGraph($dataFlowContext) {
		$title = $this->reportName;
		$js = $this->getJsForGraph();

		switch($this->pageFormat) {
			// A4 landscape
			case 'A4L':
				$height = '21cm';
				$width = '29.7cm';
				$paddingLeft = '46px';
				$paddingTop = '140px';
				$size = 'A4 landscape';
				$canvasWidth=1024;
				$canvasHeight=512;
				break;
				// A4 portrait
			default:
				$width = '21cm';
				$height = '29.7cm';
				$paddingTop = '46px';
				$paddingLeft = '140px';
				$size = 'A4 portrait';
				$canvasWidth=512;
				$canvasHeight=1024;
		}
		
		$html = <<<GRAPHHTML
<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="content-type" content="text/html; charset=UTF-8">
  <title>$title</title>
  <style type='text/css'>
    body {
		margin: 0;
        padding: 0;
        background-color: #FAFAFA;
        font-family: verdana,​helvetica,​arial,​sans-serif;
		font-size: 12px;
    }
    * {
        box-sizing: border-box;
        -moz-box-sizing: border-box;
    }
    .page {
        width: $width;
        height: $height;
        padding-top: $paddingTop;
		padding-left: $paddingLeft;		
        margin: 1cm auto;
        border: 1px #1F1F5C solid;
        border-radius: 5px;
        background: white;
        box-shadow: 0 0 5px rgba(0, 0, 0, 0.2);
    }
    .cell {
        padding: 0;        
        margin: 0;				
    }	
    
    @page {
        size: $size;
        margin: 0;		
    }
    @media print {
        .page {
            margin: 0;
            border: initial;
            border-radius: initial;
            width: initial;
            min-height: initial;
            box-shadow: initial;
            background: initial;
            page-break-after: always;
        }
    }
  </style>
  <script>
  function getScriptParentElement() {
		var scriptTag = document.getElementsByTagName('script');
		scriptTag = scriptTag[scriptTag.length - 1];
		return scriptTag.parentNode;
	}		
	function getCanvas() {
		return document.getElementById("canvas_"+getScriptParentElement().id);
	}
	function drawGraph(nodes, c, canvas) {
		if(nodes.length > 1) {
			// autoscaling
			xmin = 0; xmax = 0; ymin = 0; ymax = 0;
			for(i=0;i<nodes.length;i++) {
				if(nodes[i].x < xmin) xmin = nodes[i].x;
				if(nodes[i].x > xmax) xmax = nodes[i].x;
				if(nodes[i].y < ymin) ymin = nodes[i].y;
				if(nodes[i].y > ymax) ymax = nodes[i].y;
			}
			if(ymax-ymin > 0) {
				sy = (canvas.height-6)/(ymax-ymin);
			}
			else {
				sy = (canvas.height/2)/Math.abs(ymax);
			}
			if(xmax-xmin > 0) {
				sx = (canvas.width-6)/(xmax-xmin);
			}
			else {
				sx = (canvas.width/2)/Math.abs(xmax);
			}
			if(Math.abs(sx) < Math.abs(sy)) sy = (sy < 0?-1:1)*Math.abs(sx);
			else sx = (sx < 0?-1:1)*Math.abs(sy);
			cx = (xmax+xmin)/2.0; cy = (ymax+ymin)/2.0;
			
			// plotting
			var ctx=canvas.getContext("2d");	
			edges = {};										
			for(i=0;i<nodes.length;i++) {
				x1 = canvas.width/2 + sx*(nodes[i].x - cx);
				y1 = canvas.height/2 - sy*(nodes[i].y - cy);
				// draws point
				ctx.beginPath();
				ctx.fillStyle=c;
				ctx.arc(x1, y1, 2, 0, 2*Math.PI);
				ctx.fill();
				// draws edges
				ctx.lineWidth=2;
				ctx.strokeStyle = c;
				if(nodes[i].links && nodes[i].links.length > 0) {
					for(j=0;j<nodes[i].links.length;j++) {
						n = nodes[i].links[j];
						if(0 <= n && n < nodes.length) {
							drawEdge = false;
							if(n > i) {
								edges["("+i+"("+n+"))"] = true;
								drawEdge = true;
							}
							else drawEdge = (n < i) && !edges["("+n+"("+i+"))"];
							if(drawEdge) {
								x2 = canvas.width/2 + sx*(nodes[n].x - cx);
								y2 = canvas.height/2 - sy*(nodes[n].y-cy);
								
								ctx.moveTo(x1,y1);
								ctx.lineTo(x2,y2);
								ctx.stroke();
							}
						}
					}
				}
			}						
		}
	}
  </script>
</head>
<body>	
	<div class="page">
		<div id="c1" class="cell" style="height: {$canvasHeight}px; width:{$canvasWidth}px;">
			<canvas id="canvas_c1" height="$canvasHeight" width="$canvasWidth">
				Your browser does not support the HTML5 canvas tag.
			</canvas>			
			<script>
				$js
			</script>
		</div>    
	</div>
</body>
</html>
GRAPHHTML;
		
		$dataFlowContext->writeResultToOutput($html, $this);
	}
	
	protected function getJsForGraph() {
		$js = '';
		if(!empty($this->points)) {
			$firstP = true;
			$js = 'drawGraph([';
			foreach($this->points as $p) {
				if($firstP) $firstP = false;
				else $js .= ',';
				$js .= '{';
				$js .= 'x:'.$p->getAttribute('ptX');
				$js .= ',y:'.$p->getAttribute('ptY');
				if($p->hasLinks()) {
					$js .= ',links:[';
					$firstL = true;
					foreach($p->getLinksIterator() as $linkName=>$destP) {
						if($firstL) $firstL = false;
						else $js .= ',';
						$js .= $destP->getAttribute('ordinal');
					}
					$js .= ']';
				}
				$js .= '}';
			}
			$js .= '], "#003366", getCanvas());';
		}
		return $js;
	}
}