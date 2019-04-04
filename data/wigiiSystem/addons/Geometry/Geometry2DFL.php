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

/**
 * 2D Geometry Func Exp library 
 * Created by CWE on 8 octobre 2013
 */
class Geometry2DFL extends FuncExpVMAbstractFL
{	
	// Dependency injection
	
	private $_debugLogger;
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("Geometry2DFL");
		}
		return $this->_debugLogger;
	}
	
	private $dflowS;
	/**
	 * Injects a DataFlowService to be used by this library
	 * @param DataFlowService $dataFlowService
	 */
	public function setDataFlowService($dataFlowService)
	{
		$this->dflowS = $dataFlowService;
	}
	/**
	 * Gets the injected DataFlowService
	 * @return DataFlowService
	 */
	protected function getDataFlowService()
	{
		// autowired
		if(!isset($this->dflowS))
		{
			$this->dflowS = ServiceProvider::getDataFlowService();
		}
		return $this->dflowS;
	}
	
	// Point
	
	/**
	 * Returns the X coordinate of a given point.<br/>
	 * FuncExp signature : <code>ptX(point, dfa)</code><br/>
	 * Where arguments are :
	 * - Arg(0) point : evaluates to a WigiiGraphNode representing a point.
	 * - Arg(1) dfa : optional value, if set should evaluate to a FuncExpDFA. 
	 * In that case, that means that the funcexp is called in a DataFlow context, else it is called individually.
	 * @return String|Number the x coordinate of the given point or 0 if none is given.
	 */
	public function ptX($args) {return $this->getWigiiGraphNodeAttribute('ptX', $args, 0);}
	
	/**
	 * Sets the X coordinate of a given point.<br/>
	 * FuncExp signature : <code>ptSetX(point[, dfa], value)</code><br/>
	 * Where arguments are :
	 * - Arg(0) point : evaluates to a WigiiGraphNode representing a point.
	 * - Arg(1) dfa|value : if instance of FuncExpDFA then x coordinate is set in a data flow context 
	 * and this argument is a reference to the DataFlowActivity, else this argument is the x coordinate.
	 * - Arg(2) value : if we are in a DataFlow context, then this argument is the x value, else this argument is ignored.
	 * @return WigiiGraphNode returns the modified point	 
	 */
	public function ptSetX($args) {return $this->setWigiiGraphNodeAttribute('ptX', $args);}
	
	/**
	 * Updates the X coordinate of a given point.<br/>
	 * FuncExp signature : <code>ptUpdX(point, dfa, funcExp)</code><br/>
	 * Where arguments are :
	 * - Arg(0) point : evaluates to a WigiiGraphNode representing a point.
	 * - Arg(1) dfa : a reference to the DataFlowActivity
	 * - Arg(2) funcExp : the func exp used to update the x coordinate based on previous value.
	 * @return WigiiGraphNode returns the modified point
	 */
	public function ptUpdX($args) {return $this->updateWigiiGraphNodeAttribute('ptX', $args);}
	
	/**
	 * Returns the Y coordinate of a given point.<br/>
	 * FuncExp signature : <code>ptY(point, dfa)</code><br/>
	 * Where arguments are :
	 * - Arg(0) point : evaluates to a WigiiGraphNode representing a point.
	 * - Arg(1) dfa : optional value, if set should evaluate to a FuncExpDFA. In that case, 
	 * that means that ptY is called in a DataFlow context, else the function is called individually.
	 * @return String|Number the y coordinate of the given point or 0 if none is given.
	 */
	public function ptY($args) {return $this->getWigiiGraphNodeAttribute('ptY', $args, 0);}
	
	/**
	 * Sets the Y coordinate of a given point.<br/>
	 * FuncExp signature : <code>ptSetY(point[, dfa], value)</code><br/>
	 * Where arguments are :
	 * - Arg(0) point : evaluates to a WigiiGraphNode representing a point.
	 * - Arg(1) dfa|value : if instance of FuncExpDFA then y coordinate is set in a data flow context 
	 * and this argument is a reference to the DataFlowActivity, else this argument is the y coordinate.
	 * - Arg(2) value : if we are in a DataFlow context, then this argument is the y value, else this argument is ignored.
	 * @return WigiiGraphNode returns the modified point	 
	 */
	public function ptSetY($args) {return $this->setWigiiGraphNodeAttribute('ptY', $args);}
	
	/**
	 * Updates the Y coordinate of a given point.<br/>
	 * FuncExp signature : <code>ptUpdY(point, dfa, funcExp)</code><br/>
	 * Where arguments are :
	 * - Arg(0) point : evaluates to a WigiiGraphNode representing a point.
	 * - Arg(1) dfa : a reference to the DataFlowActivity
	 * - Arg(2) funcExp : the func exp used to update the y coordinate based on previous value.
	 * @return WigiiGraphNode returns the modified point
	 */
	public function ptUpdY($args) {return $this->updateWigiiGraphNodeAttribute('ptY', $args);}
	
	/**
	 * Returns the color of a given point.<br/>
	 * FuncExp signature : <code>ptColor(point, dfa)</code><br/>
	 * Where arguments are :
	 * - Arg(0) point : evaluates to a WigiiGraphNode representing a point.
	 * - Arg(1) dfa : optional value, if set should evaluate to a FuncExpDFA. In that case, 
	 * that means that ptColor is called in a DataFlow context, else the function is called individually.
	 * @return String|Number the color of the given point or null if none is given.
	 */
	public function ptColor($args) {return $this->getWigiiGraphNodeAttribute('ptColor', $args);}
	
	/**
	 * Sets the color of a given point.<br/>
	 * FuncExp signature : <code>ptSetColor(point[, dfa], value)</code><br/>
	 * Where arguments are :
	 * - Arg(0) point : evaluates to a WigiiGraphNode representing a point.
	 * - Arg(1) dfa|value : if instance of FuncExpDFA then color is set in a data flow context 
	 * and this argument is a reference to the DataFlowActivity, else this argument is the color.
	 * - Arg(2) value : if we are in a DataFlow context, then this argument is the color, else this argument is ignored.
	 * @return WigiiGraphNode returns the modified point	 
	 */
	public function ptSetColor($args) {return $this->setWigiiGraphNodeAttribute('ptColor', $args);}
	
	/**
	 * Returns the weight of a given point.<br/>
	 * FuncExp signature : <code>ptWeight(point, dfa)</code><br/>
	 * Where arguments are :
	 * - Arg(0) point : evaluates to a WigiiGraphNode representing a point.
	 * - Arg(1) dfa : optional value, if set should evaluate to a FuncExpDFA. In that case, 
	 * that means that ptWeight is called in a DataFlow context, else the function is called individually.
	 * @return String|Number the weight of the given point or 0 if none is given.
	 */
	public function ptWeight($args) {return $this->getWigiiGraphNodeAttribute('ptWeight', $args, 0);}
	
	/**
	 * Sets the weight of a given point.<br/>
	 * FuncExp signature : <code>ptSetWeight(point[, dfa], value)</code><br/>
	 * Where arguments are :
	 * - Arg(0) point : evaluates to a WigiiGraphNode representing a point.
	 * - Arg(1) dfa|value : if instance of FuncExpDFA then weight is set in a data flow context 
	 * and this argument is a reference to the DataFlowActivity, else this argument is the weight.
	 * - Arg(2) value : if we are in a DataFlow context, then this argument is the weight value, else this argument is ignored.
	 * @return WigiiGraphNode returns the modified point	 
	 */
	public function ptSetWeight($args) {return $this->setWigiiGraphNodeAttribute('ptWeight', $args);}
	
	/**
	 * Returns the ordinal of a given point.<br/>
	 * FuncExp signature : <code>ptOrdinal(point, dfa)</code><br/>
	 * Where arguments are :
	 * - Arg(0) point : evaluates to a WigiiGraphNode representing a point.
	 * - Arg(1) dfa : optional value, if set should evaluate to a FuncExpDFA. In that case,
	 * that means that ptOrdinal is called in a DataFlow context, else the function is called individually.
	 * @return String|Number the ordinal of the given point or 0 if none is given.
	 */
	public function ptOrdinal($args) {return $this->getWigiiGraphNodeAttribute('ordinal', $args, 0);}
	
	/**
	 * Creates an empty point.<br/>
	 * FuncExp signature : <code>emptyPoint(plot, ordinal)</code><br/>
	 * Where arguments are :
	 * - Arg(0) plot : evaluates to a WigiiGraph representing a plot
	 * - Arg(last) ordinal: an optional ordinal number
	 * @return WigiiGraphNode a WigiiGraphNode living in the given WigiiGraph, representing a point in the plot.
	 */
	public function emptyPoint($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new FuncExpEvalException("the funcexp emptyPoint takes one argument which should evaluate to a non null WigiiGraph", FuncExpEvalException::INVALID_ARGUMENT);
		if($args[0] instanceof WigiiGraph) $wigiiGraph = $args[0];
		else {
			$wigiiGraph = $this->evaluateArg($args[0]);
			if(!isset($wigiiGraph) || !($wigiiGraph instanceof WigiiGraph)) throw new FuncExpEvalException("the funcexp emptyPoint takes one argument which should evaluate to a non null WigiiGraph", FuncExpEvalException::INVALID_ARGUMENT);
		}
		$returnValue = $wigiiGraph->createGraphNode();
		if($nArgs > 1 && isset($args[$nArgs-1])) {
			$returnValue->setAttribute('ordinal', $this->evaluateArg($args[$nArgs-1]));			
		}
		return $returnValue;
	}
	
	/**
	 * Creates a point given its x and y coordinates, its color and its weight,
	 * and links it to the given WigiiGraph
	 * FuncExp signature : <code>point(WigiiGraph, x, y, color, weight, ordinal)</code><br/>
	 * Where arguments are :
	 * - Arg(0) WigiiGraph: an instance of a WigiiGraph in which the point should be created. 
	 * - Arg(1) x: the x coordinate
	 * - Arg(2) y: the y coordinate
	 * - Arg(3) color: the color
	 * - Arg(4) weight: the weight	
	 * - Arg(5) ordinal: the point ordinal in the graph 
	 */
	public function point($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new FuncExpEvalException("the funcexp point takes at least one argument which should evaluate to a non null WigiiGraph", FuncExpEvalException::INVALID_ARGUMENT);
		if($args[0] instanceof WigiiGraph) $wigiiGraph = $args[0];
		else { 
			$wigiiGraph = $this->evaluateArg($args[0]);
			if(!isset($wigiiGraph) || !($wigiiGraph instanceof WigiiGraph)) throw new FuncExpEvalException("the funcexp emptyPoint takes at least one argument which should evaluate to a non null WigiiGraph", FuncExpEvalException::INVALID_ARGUMENT);
		}
		$returnValue = $wigiiGraph->createGraphNode();
		if($nArgs > 1) $returnValue->setAttribute('ptX', $this->evaluateArg($args[1]));
		if($nArgs > 2) $returnValue->setAttribute('ptY', $this->evaluateArg($args[2]));
		if($nArgs > 3) $returnValue->setAttribute('ptColor', $this->evaluateArg($args[3]));
		if($nArgs > 4) $returnValue->setAttribute('ptWeight', $this->evaluateArg($args[4]));
		if($nArgs > 5) $returnValue->setAttribute('ordinal', $this->evaluateArg($args[5]));
		return $returnValue;
	}
	
	// Geometry objects
	
	/**
	 * Creates an empty plot.<br/>
	 * FuncExp signature : <code>emptyPlot(var=null,persistInDb=false)</code><br/>
	 * Where arguments are :
	 * - Arg(0) var : optional argument which should be an instance of a FieldSelector. 
	 * If set then the created graph will be stored under this variable, else the graph is not stored into a variable.
	 * - Arg(1) persistInDb : optional argument which evaluates to a boolean. If true, then the WigiiGraph
	 * is persisted into the DB else it resides only into memory. By default is false.
	 * @return WigiiGraph a WigiiGraph instance representing a plot.
	 */
	public function emptyPlot($args) {
		$nArgs = $this->getNumberOfArgs($args);
		$var = null; $persistInDb = false;
		if($nArgs == 1) {
			if($args[0] instanceof FieldSelector) $var = $args[0];
			else {
				$persistInDb = $this->evaluateArg($args[0]);
				if($persistInDb instanceof FieldSelector) {
					$var = $persistInDb;
					$persistInDb = false;
				}
			}
		}
		elseif($nArgs > 1) {
			if($args[0] instanceof FieldSelector) $var = $args[0];
			else {
				$var = $this->evaluateArg($args[0]);
				if(isset($var) && !($var instanceof FieldSelector)) throw new FuncExpEvalException("var should be a FieldSelector", FuncExpEvalException::INVALID_ARGUMENT);				
			}
			$persistInDb = $this->evaluateArg($args[1]);
		}
		
		$returnValue = null;
		if(isset($var)) $returnValue = $this->evaluateFieldSelector($var);
		if(!isset($returnValue)) {
			// should return a WigiiGraph implementation which is auto persisted into the DB.
			// Not supported yet
			if($persistInDb) throw new FuncExpEvalException("The persistence of a WigiiGraph into the DB is not implemented yet.", FuncExpEvalException::NOT_IMPLEMENTED);			
			else $returnValue = WigiiGraphArrayImpl::createInstance();							
			if(isset($var)) $this->getFuncExpVMServiceProvider()->getFuncExpVMContext()->setVariable($var, $returnValue);
		}
		return $returnValue;		
	}	
			
	/**
	 * Creates a path given a set of points
	 * FuncExp signature : <code>path(point1, point2, ...)</code><br/>
	 * Where arguments are :
	 * - Arg(0..n) pointI: a FuncExp which creates WigiiGraphNode instance acting as a point and living in the given graph.
	 * Before calling the FuncExp, the array of arguments is augmented with :
	 * - a reference to the WigiiGraph as first argument
	 * - the ordinal value as last argument.
	 * The other arguments of the func exp are untouched.
	 * @return WigiiGraphArrayImpl a WigiiGraph instance which holds into memory or null if no points
	 */
	public function path($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs > 0) {
			$returnValue = WigiiGraphArrayImpl::createInstance();
			$lastPoint = null;
			for($i = 0; $i < $nArgs; $i++) {
				if(!($args[$i] instanceof FuncExp)) throw new FuncExpEvalException("the pointI argument should be a FuncExp which creates a WigiiGraphNode", FuncExpEvalException::INVALID_ARGUMENT);
				$point = $args[$i];
				$pArgs = $point->getArguments();
				if(isset($pArgs)) {
					array_unshift($pArgs, $returnValue);
					$pArgs[] = $i+1;
					$point->setArguments($pArgs);					
				}
				else $point->setArguments(array($returnValue, $i+1));
				$point = $this->evaluateArg($point);
				if(isset($lastPoint)) {
					$lastPoint->setLink('next', $point);
					$point->setLink('previous', $lastPoint);
				}	
				$lastPoint = $point;			
			}
			return $returnValue;
		}
		else return null;
	}
		
	/**
	 * Creates a polygon given a set of points
	 * FuncExp signature : <code>polygon(point1, point2, ...)</code><br/>
	 * Where arguments are :
	 * - Arg(0..n) pointI: a FuncExp which creates WigiiGraphNode instance acting as a point and living in the given graph.
	 * Before calling the FuncExp, the array of arguments is augmented with :
	 * - a reference to the WigiiGraph as first argument
	 * - the ordinal value as last argument.
	 * The other arguments of the func exp are untouched.
	 * @return WigiiGraphArrayImpl a WigiiGraph instance which holds into memory or null if no points
	 */
	public function polygon($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs > 0) {
			$returnValue = WigiiGraphArrayImpl::createInstance();
			$lastPoint = null; $firstPoint = null;
			for($i = 0; $i < $nArgs; $i++) {
				if(!($args[i] instanceof FuncExp)) throw new FuncExpEvalException("the pointI argument should be a FuncExp which creates a WigiiGraphNode", FuncExpEvalException::INVALID_ARGUMENT);
				$point = $args[i];
				$pArgs = $point->getArguments();
				if(isset($pArgs)) {
					array_unshift($pArgs, $returnValue);
					$pArgs[] = $i+1;
					$point->setArguments($pArgs);					
				}
				else $point->setArguments(array($returnValue, $i+1));
				$point = $this->evaluateArg($point);		
				if(isset($lastPoint)) {
					$lastPoint->setLink('next', $point);
					$point->setLink('previous', $lastPoint);
				}	
				$lastPoint = $point;
				if(!isset($firstPoint)) $firstPoint = $point;	
			}
			if($nArgs > 2) {
				$lastPoint->setLink('next', $firstPoint);
				$firstPoint->setLink('previous', $lastPoint);
			}
			return $returnValue;
		}
		else return null;
	}
	
	// Code generators
	
	/**
	 * FuncExp DataFlow Activity : Generates a func exp call for 'path' based on a flow of points.
	 * FuncExp signature : <code>genCodeForPath(point, dfa)</code><br/>
	 * Where arguments are :
	 * - Arg(0) point: the current point in the running data flow
	 * - Arg(1) dfa: a reference to the underlying FuncExpDFA instance
	 * @return FuncExp an instance of a 'path' func exp.
	 */
	public function genCodeForPath($args) {		
		$dfa = $args[1];
		switch($dfa->getState()) {
			case FuncExpDFA::FUNCEXP_DFA_SINGLE_DATA:
				return FuncExp::createInstance('path', array(
					FuncExp::createInstance('point', 
						array($this->ptX($args), $this->ptY($args), 
							$this->ptColor($args), $this->ptWeight($args)))));
				break;
			case FuncExpDFA::FUNCEXP_DFA_STARTSTREAM:
				$dfa->setValueInContext('path', FuncExp::createInstance('path'));
				break;
			case FuncExpDFA::FUNCEXP_DFA_RUNNING:
				$dfa->getValueInContext('path')->addArgument(FuncExp::createInstance('point', 
					array($this->ptX($args), $this->ptY($args), 
						$this->ptColor($args), $this->ptWeight($args))));				
				break;
			case FuncExpDFA::FUNCEXP_DFA_ENDSTREAM:
				return $dfa->getValueInContext('path');
				break;
		}		
	}
	
	// 2D Curves
	
	/**
	 * FuncExp DataFlow Activity : Generates some points following a logarithmic spiral, centered on the oncoming point.
	 * FuncExp signature : <code>logarithmicSpiral(data, dfa, nbPoints, nbRevolutions, radius, growth)</code><br/>
	 * Where arguments are :
	 * - Arg(0) data: the current data chunk in the running data flow
	 * - Arg(1) dfa: a reference to the underlying FuncExpDFA instance
	 * - Arg(2) nbPoints: the number of points on the spiral
	 * - Arg(3) nbRevolutions: the 2*pi multiple defining the number of revolutions.
	 * - Arg(4) radius: the initial radius
	 * - Arg(5) growth: the growth factor
	 * @return WigiiGraphNode generates nbPoints following the logarithmic spiral centered on the incoming point,
	 * with radius starting with r = radius; and then growing at r *= growth rate;
	 * and angle starting with a = angle = 2*pi*nbRevolutions/nbPoints; and then changing at a += angle;
	 */
	public function logarithmicSpiral($args) {
		$nArgs = $this->getNumberOfArgs($args);
		$data = $args[0];
		$dfa = $args[1];
		if($dfa->isStartStream() || $dfa->isSingleData()) {
			$nbPoints = $this->evaluateArg($args[2]);
			$nbRevolutions = $this->evaluateArg($args[3]);
			$radius = $this->evaluateArg($args[4]);
			$growth = $this->evaluateArg($args[5]);
			if($dfa->isStartStream()) {
				$dfa->setValueInContext('nbPoints', $nbPoints);
				$dfa->setValueInContext('nbRevolutions', $nbRevolutions);
				$dfa->setValueInContext('radius', $radius);
				$dfa->setValueInContext('growth', $growth);
			}
		}
		if($dfa->isRunning() || $dfa->isSingleData()) {
			if($dfa->isRunning()) {
				$nbPoints = $dfa->getValueInContext('nbPoints');
				$nbRevolutions = $dfa->getValueInContext('nbRevolutions');
				$radius = $dfa->getValueInContext('radius');
				$growth = $dfa->getValueInContext('growth');
			}
			
			$angle = 2*pi()*$nbRevolutions/$nbPoints;
			$a = 0; $r = $radius;
			$cx = $data->getAttribute('ptX');
			$cy = $data->getAttribute('ptY');
			$g = $data->getWigiiGraph();
			for($i=0;$i<$nbPoints;$i++) {
				$x = $cx+$r*cos($a);
				$y = $cy+$r*sin($a);
				$a += $angle;
				$r *= $growth;
				$p = $g->createGraphNode();
				$p->setAttribute('ordinal', $i);
				$p->setAttribute('ptX', $x);
				$p->setAttribute('ptY', $y);
				$dfa->writeResultToOutput($p);
			}
		}
	}
	
	// Geometric transformations
	
	/**
	 * FuncExp DataFlow Activity : Applies an affine transformation on a flow of points.
	 * The transformation includes rotation, scaling and translation.
	 * FuncExp signature : <code>affineT(data, dfa, rotAngle, scale, xTrans, yTrans)</code><br/>
	 * Where arguments are :
	 * - Arg(0) data: the current data chunk in the running data flow
	 * - Arg(1) dfa: a reference to the underlying FuncExpDFA instance
	 * - Arg(2) rotAngle: the rotation angle in radian. Default to 0.
	 * - Arg(3) scale: the scaling factor. Defaults to 1.
	 * - Arg(4) xTrans: the translation value along the x axis. Default to 0.
	 * - Arg(5) yTrans: the translation value along the y axis. Defaults to 0.
	 * @return WigiiGraphNode the transformed points such as 
	 * x1 = (cos(angle)*x0-sin(angle)*y0)*scale+xTrans
	 * y1 = (sin(angle)*x0+cos(angle)*y0)*scale+yTrans
	 */
	public function affineT($args) {
		$nArgs = $this->getNumberOfArgs($args);
		$data = $args[0];
		$dfa = $args[1];
		if($dfa->isStartStream() || $dfa->isSingleData()) {
			if($nArgs > 2) $rotAngle = $this->evaluateArg($args[2]);
			else $rotAngle = 0;
			if($nArgs > 3) $scale = $this->evaluateArg($args[3]);
			else $scale = 1;
			if($nArgs > 4) $xTrans = $this->evaluateArg($args[4]);
			else $xTrans = 0;
			if($nArgs > 5) $yTrans = $this->evaluateArg($args[5]);
			else $yTrans = 0;
			$rotCos = cos($rotAngle);
			$rotSin = sin($rotAngle);
			if($dfa->isStartStream()) {
				$dfa->setValueInContext('rotAngle', $rotAngle);
				$dfa->setValueInContext('rotCos', $rotCos);
				$dfa->setValueInContext('rotSin', $rotSin);
				$dfa->setValueInContext('scale', $scale);
				$dfa->setValueInContext('xTrans', $xTrans);
				$dfa->setValueInContext('yTrans', $yTrans);
			}
		}
		if($dfa->isRunning() || $dfa->isSingleData()) {
			if($dfa->isRunning()) {
				$rotAngle = $dfa->getValueInContext('rotAngle');
				$rotCos = $dfa->getValueInContext('rotCos');
				$rotSin = $dfa->getValueInContext('rotSin');
				$scale = $dfa->getValueInContext('scale');
				$xTrans = $dfa->getValueInContext('xTrans');
				$yTrans = $dfa->getValueInContext('yTrans');
			}
				
			$x = $data->getAttribute('ptX');
			$y = $data->getAttribute('ptY');
			$data->setAttribute('ptX', ($rotCos*$x-$rotSin*$y)*$scale+$xTrans);
			$data->setAttribute('ptY', ($rotSin*$x+$rotCos*$y)*$scale+$yTrans);
			return $data;
		}
	}
	
	/**
	 * FuncExp DataFlow Activity : Mirrors a flow of points.
	 * The transformation includes rotation and scaling.
	 * FuncExp signature : <code>mirrorT(data, dfa, rotAngle, xScale, yScale)</code><br/>
	 * Where arguments are :
	 * - Arg(0) data: the current data chunk in the running data flow
	 * - Arg(1) dfa: a reference to the underlying FuncExpDFA instance
	 * - Arg(2) rotAngle: the rotation angle in radian. Default to 0.
	 * - Arg(3) xScale: the X scaling factor. Defaults to 1.
	 * - Arg(4) yScale: the Y scaling factor. Defaults to 1.
	 * - Arg(5) portion: number between 0 and 1 defining the portion of points to mirror starting from the end.
	 * If 1 then all points a mirrored, if 0.5 then only half of the points are mirrored. Defaults to 1. 
	 * @return WigiiGraphNode first returns the original points in the original order,
	 * then returns the transformed points in the reverse order.
	 * The resulting flow is equal to p1, p2, ..., pn, pn+1, pn+2, ..., p2n, such as:
	 * p1,...,pn are the original points, pn+1, ..., p2n are the transformed points, so that:
	 * p2n-i+1(x) = (cos(rotAngle)*pi(x)-sin(rotAngle)*pi(y))*xScale
	 * p2n-i+1(y) = (sin(rotAngle)*pi(x)+cos(rotAngle)*pi(y))*yScale
	 * and i=1..n
	 */
	public function mirrorT($args) {
		$nArgs = $this->getNumberOfArgs($args);
		$data = $args[0];
		$dfa = $args[1];
		if($dfa->isStartStream() || $dfa->isSingleData()) {
			if($nArgs > 2) $rotAngle = $this->evaluateArg($args[2]);
			else $rotAngle = 0;
			if($nArgs > 3) $xScale = $this->evaluateArg($args[3]);
			else $xScale = 1;
			if($nArgs > 4) $yScale = $this->evaluateArg($args[4]);
			else $yScale = 1;
			if($nArgs > 5) {
				$portion = $this->evaluateArg($args[5]);
				if($portion <= 0) $portion = 0;
				elseif($portion > 1) $portion = 1; 
			}
			else $portion = 1;
			$rotCos = cos($rotAngle);
			$rotSin = sin($rotAngle);
			$numberOfPoints = 0;
			if($dfa->isStartStream()) {
				$dfa->setValueInContext('rotAngle', $rotAngle);
				$dfa->setValueInContext('rotCos', $rotCos);
				$dfa->setValueInContext('rotSin', $rotSin);
				$dfa->setValueInContext('xScale', $xScale);
				$dfa->setValueInContext('yScale', $yScale);
				$dfa->setValueInContext('numberOfPoints', $numberOfPoints);
				$dfa->setValueInContext('portion', $portion);
			}
		}
		if($dfa->isRunning() || $dfa->isSingleData()) {
			if($dfa->isRunning()) {
				$rotAngle = $dfa->getValueInContext('rotAngle');
				$rotCos = $dfa->getValueInContext('rotCos');
				$rotSin = $dfa->getValueInContext('rotSin');
				$xScale = $dfa->getValueInContext('xScale');
				$yScale = $dfa->getValueInContext('yScale');
				$numberOfPoints = $dfa->getValueInContext('numberOfPoints');
			}
	
			// creates new point which is the transformation of the current point
			$x = $data->getAttribute('ptX');
			$y = $data->getAttribute('ptY');
			$p = $data->getWigiiGraph()->createGraphNode();
			$p->setAttribute('ptX', ($rotCos*$x-$rotSin*$y)*$xScale);
			$p->setAttribute('ptY', ($rotSin*$x+$rotCos*$y)*$yScale);
			$numberOfPoints++;
			// stores the point in context
			$dfa->setValueInContext($numberOfPoints, $p);
			$dfa->setValueInContext('numberOfPoints', $numberOfPoints);
			// output the current point
			$dfa->writeResultToOutput($data);
		}
		if($dfa->isEndStream() || $dfa->isSingleData()) {
			if($dfa->isEndStream()) {
				$numberOfPoints = $dfa->getValueInContext('numberOfPoints');
				$portion = $dfa->getValueInContext('portion');
			}
			// outputs the new points in reverse order
			$ordinal = $numberOfPoints+1;
			$stop = intval($numberOfPoints*(1-$portion));
			for($i = $numberOfPoints; $i > $stop; $i--) {
				$p = $dfa->getValueInContext($i);
				$p->setAttribute('ordinal', $ordinal);
				$ordinal++;
				$dfa->writeResultToOutput($p);
			}
		}
	}
	
	/**
	 * FuncExp DataFlow Activity : Shifts a flow of points so that the last points has the given coordinates.
	 * FuncExp signature : <code>endOnPointT(data, dfa, x, y)</code><br/>
	 * Where arguments are :
	 * - Arg(0) data: the current data chunk in the running data flow
	 * - Arg(1) dfa: a reference to the underlying FuncExpDFA instance
	 * - Arg(2) x: the x coordinate of the point on which the flow should end. Default to 0.
	 * - Arg(3) y: the y coordinate of the point on which the flow should end. Default to 0.
	 * @return WigiiGraphNode returns the flow of points p1, p2, ..., pn such that
	 * pi(x) = pi(x) - pn(x) + x
	 * pi(y) = pi(y) - pn(y) + y
	 * and i=1..n 
	 */
	public function endOnPointT($args) {
		$nArgs = $this->getNumberOfArgs($args);
		$data = $args[0];
		$dfa = $args[1];
		if($dfa->isStartStream() || $dfa->isSingleData()) {
			if($nArgs > 2) $x = $this->evaluateArg($args[2]);
			else $x = 0;
			if($nArgs > 3) $y = $this->evaluateArg($args[3]);
			else $y = 0;
			$numberOfPoints = 0;
			if($dfa->isStartStream()) {
				$dfa->setValueInContext('x', $x);
				$dfa->setValueInContext('y', $y);
				$dfa->setValueInContext('numberOfPoints', $numberOfPoints);
			}
		}
		if($dfa->isRunning() || $dfa->isSingleData()) {
			if($dfa->isRunning()) {				
				$numberOfPoints = $dfa->getValueInContext('numberOfPoints');
			}
	
			// stores the point into the buffer
			$numberOfPoints++;
			$dfa->setValueInContext($numberOfPoints, $data);
			$dfa->setValueInContext('numberOfPoints', $numberOfPoints);
			// stores the current coordinates as last coordinates
			$lastX = $data->getAttribute('ptX');
			$lastY = $data->getAttribute('ptY');
			$dfa->setValueInContext('lastX', $lastX);
			$dfa->setValueInContext('lastY', $lastY);
		}
		if($dfa->isEndStream() || $dfa->isSingleData()) {
			if($dfa->isEndStream()) {
				$numberOfPoints = $dfa->getValueInContext('numberOfPoints');
				$lastX = $dfa->getValueInContext('lastX');
				$lastY = $dfa->getValueInContext('lastY');
				$x = $dfa->getValueInContext('x');
				$y = $dfa->getValueInContext('y');
			}
			// outputs the shifted points
			for($i = 1; $i <= $numberOfPoints; $i++) {
				$p = $dfa->getValueInContext($i);
				$p->setAttribute('ptX', $p->getAttribute('ptX') - $lastX + $x);
				$p->setAttribute('ptY', $p->getAttribute('ptY') - $lastY + $y);
				$dfa->writeResultToOutput($p);
			}
		}
	}
	
	// Algebraic expressions
	
	/**
	 * FuncExp DataFlow Activity : Computes a sum of algebraic terms.
	 * Each algebraic term is of the form a*power(data, b), where a and b are rational numbers.<br/>
	 * FuncExp signature : <code>algebSum(data, dfa, coeffNum, coeffDenom, expNum, expDenom)</code><br/>
	 * Where arguments are :
	 * - Arg(0) data: the current data chunk in the running data flow
	 * - Arg(1) dfa: a reference to the underlying FuncExpDFA instance
	 * - Arg(2) coeffNum: evaluates to an array of numbers beeing the coefficient numerators
	 * - Arg(3) coeffDenom: evaluates to an optional array of number beeing the coefficient denominators. If omitted, then 1 is assumed.
	 * - Arg(4) expNum: evaluates to an array of numbers beeing the exponent numerators
	 * - Arg(5) expDenom: evaluates to an optional array of numbers beeing the exponent denominators. If omitted, then 1 is assumed.
	 * @return Float sum(i: coeffNum(i)/coeffDenom(i)*power(data, expNum(i)/expDenom(i)))
	 */
	public function algebSum($args) {
		$nArgs = $this->getNumberOfArgs($args);
		$data = $args[0];
		$dfa = $args[1];		
		if($nArgs > 2) {
			$nCoeffNum = 0;
			$nCoeffDenom = 0;
			$nExpNum = 0;
			$nExpDenom = 0;
			
			if($dfa->isStartStream() || $dfa->isSingleData()) {
				if(is_numeric($args[2])) $coeffNum = array($args[2]);
				elseif(is_array($args[2])) $coeffNum = $args[2];
				else {
					$coeffNum = $this->evaluateArg($args[2]);
					if(is_numeric($coeffNum)) $coeffNum = array($coeffNum);
					elseif(!is_array($coeffNum)) throw new FuncExpEvalException("coeffNum should be an array of numbers", FuncExpEvalException::INVALID_ARGUMENT);
				}
				$nCoeffNum = count($coeffNum);
				
				if($nArgs == 4 || $nArgs == 5) {
					if(is_numeric($args[3])) $expNum = array($args[3]);
					elseif(is_array($args[3])) $expNum = $args[3];
					else {
						$expNum = $this->evaluateArg($args[3]);
						if(is_numeric($expNum)) $expNum = array($expNum);
						elseif(!is_array($expNum)) throw new FuncExpEvalException("expNum should be an array of numbers", FuncExpEvalException::INVALID_ARGUMENT);
					}
					$nExpNum = count($expNum);
					
					if($nArgs == 5) {
						if(is_numeric($args[4])) $expDenom = array($args[4]);
						elseif(is_array($args[4])) $expDenom = $args[4];
						else {
							$expDenom = $this->evaluateArg($args[4]);
							if(is_numeric($expDenom)) $expDenom = array($expDenom);
							elseif(!is_array($expDenom)) throw new FuncExpEvalException("expDenom should be an array of numbers", FuncExpEvalException::INVALID_ARGUMENT);
						}
						$nExpDenom = count($expDenom);
					}
				}
				elseif($nArgs > 4) {
					if(is_numeric($args[3])) $coeffDenom = array($args[3]);
					elseif(is_array($args[3])) $coeffDenom = $args[3];
					else {
						$coeffDenom = $this->evaluateArg($args[3]);
						if(is_numeric($coeffDenom)) $coeffDenom = array($coeffDenom);
						elseif(!is_array($coeffDenom)) throw new FuncExpEvalException("coeffDenom should be an array of numbers", FuncExpEvalException::INVALID_ARGUMENT);
					}
					$nCoeffDenom = count($coeffDenom);						
				}
				
				if($nArgs >= 5) {
					if(is_numeric($args[4])) $expNum = array($args[4]);
					elseif(is_array($args[4])) $expNum = $args[4];
					else {
						$expNum = $this->evaluateArg($args[4]);
						if(is_numeric($expNum)) $expNum = array($expNum);
						elseif(!is_array($expNum)) throw new FuncExpEvalException("expNum should be an array of numbers", FuncExpEvalException::INVALID_ARGUMENT);
					}
					$nExpNum = count($expNum);						
				}
				
				if($nArgs >= 6) {
					if(is_numeric($args[5])) $expDenom = array($args[5]);
					elseif(is_array($args[5])) $expDenom = $args[5];
					else {
						$expDenom = $this->evaluateArg($args[5]);
						if(is_numeric($expDenom)) $expDenom = array($expDenom);
						elseif(!is_array($expDenom)) throw new FuncExpEvalException("expDenom should be an array of numbers", FuncExpEvalException::INVALID_ARGUMENT);
					}
					$nExpDenom = count($expDenom);						
				}
				
				if($dfa->isStartStream()) {
					$dfa->setValueInContext('coeffNum', $coeffNum);
					$dfa->setValueInContext('coeffDenom', $coeffDenom);
					$dfa->setValueInContext('expNum', $expNum);
					$dfa->setValueInContext('expDenom', $expDenom);
				}
			}
			if($dfa->isRunning() || $dfa->isSingleData()) {
				if($dfa->isRunning()) {
					$coeffNum = $dfa->getValueInContext('coeffNum');
					$nCoeffNum = count($coeffNum);
					$coeffDenom = $dfa->getValueInContext('coeffDenom');
					$nCoeffDenom = count($coeffDenom);
					$expNum = $dfa->getValueInContext('expNum');
					$nExpNum = count($expNum);
					$expDenom = $dfa->getValueInContext('expDenom');
					$nExpDenom = count($expDenom);
				}								
				
				$n = $nCoeffNum;
				if($nExpNum > $n) $n = $nExpNum;
				$returnValue = 0.0;
				for($i = 0; $i < $n; $i++) {
					if($i < $nCoeffNum) {
						$cNum = $coeffNum[$i];
						if(!is_numeric($cNum)) {
							$cNum = $this->evaluateArg($cNum);
							if(!is_numeric($cNum)) throw new FuncExpEvalException("coefficient numerator should evaluate to a number", FuncExpEvalException::INVALID_ARGUMENT);
						}
						if($i < $nCoeffDenom) {
							$cDenom = $coeffDenom[$i];
							if(!is_numeric($cDenom)) {
								$cDenom = $this->evaluateArg($cDenom);
								if(!is_numeric($cDenom)) throw new FuncExpEvalException("coefficient denominator should evaluate to a number", FuncExpEvalException::INVALID_ARGUMENT);
							}
							if(empty($cDenom)) throw new FuncExpEvalException("coefficient denominator cannot be zero", FuncExpEvalException::INVALID_ARGUMENT);
							else $c = ((float)$cNum)/((float)$cDenom);  
						}
						else $c = (float)$cNum;
					}
					else $c = 1.0;
					
					if($i < $nExpNum) {
						$eNum = $expNum[$i];
						if(!is_numeric($eNum)) {
							$eNum = $this->evaluateArg($eNum);
							if(!is_numeric($eNum)) throw new FuncExpEvalException("exponent numerator should evaluate to a number", FuncExpEvalException::INVALID_ARGUMENT);
						}
						if($i < $nExpDenom) {
							$eDenom = $expDenom[$i];
							if(!is_numeric($eDenom)) {
								$eDenom = $this->evaluateArg($eDenom);
								if(!is_numeric($eDenom)) throw new FuncExpEvalException("exponent denominator should evaluate to a number", FuncExpEvalException::INVALID_ARGUMENT);
							}
							if(empty($eDenom)) throw new FuncExpEvalException("exponent denominator cannot be zero", FuncExpEvalException::INVALID_ARGUMENT);
							else $e = ((float)$eNum)/((float)$eDenom);  
						}
						else $e = (float)$eNum;
					}
					else $e = 1.0;
					
					$returnValue += $c*pow((float)$data, $e);
				}
				
				return $returnValue;
			}
		}
		elseif($dfa->isRunning() || $dfa->isSingleData()) return (float)$data;
	}
	
	/**
	 * FuncExp DataFlow Activity : Returns the quotient of two algebraic functions	 
	 * FuncExp signature : <code>algebQuotient(data, dfa, dividendFunction, divisorFunction)</code><br/>
	 * Where arguments are :
	 * - Arg(0) data: the current data chunk in the running data flow
	 * - Arg(1) dfa: a reference to the underlying FuncExpDFA instance
	 * - Arg(2) dividendFunction: an instance of a FuncExp DataFlow Activity which is an algebraic function on the current data in the data flow.
	 * - Arg(3) divisorFunction: an instance of a FuncExp DataFlow Activity which is an algebraic function on the current data in the data flow.
	 * @return Float dividendFunction(data)/divisorFunction(data)
	 */
	public function algebQuotient($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 4 || !($args[2] instanceof FuncExp) || !($args[3] instanceof FuncExp)) throw new FuncExpEvalException("the funcExp algebQuotient takes two arguments of type FuncExp: the dividendFunction and the divisorFunction", FuncExpEvalException::INVALID_ARGUMENT);
		$data = $args[0]; 
		$dfa = $args[1];
		$dataFlowArgs = array($data, $dfa);
		// evaluates dividend
		$dividend = $args[2];
		$fArgs = $dividend->getArguments();
		if(isset($fArgs)) $dividend->setArguments(array_merge($dataFlowArgs, $fArgs));
		else $dividend->setArguments($dataFlowArgs);
		$dividend = $this->evaluateArg($dividend);
		 
		// evaluates divisor
		$divisor = $args[3];
		$fArgs = $divisor->getArguments();
		if(isset($fArgs)) $divisor->setArguments(array_merge($dataFlowArgs, $fArgs));
		else $divisor->setArguments($dataFlowArgs);
		$divisor = $this->evaluateArg($divisor);
		
		// evaluates division
		if($dfa->isRunning() || $dfa->isSingleData()) {
			if(empty($divisor)) throw new FuncExpEvalException("divisorFunction returned 0 therefore division produces a division by zero error", FuncExpEvalException::DIVISION_BY_ZERO);
			else return ((float)$dividend)/((float)$divisor);
		}
	}
	
	/**
	 * FuncExp DataFlow Activity : Computes a polynomial functions knowing its zeros.
	 * FuncExp signature : <code>basicPolynomialFunction(data, dfa, zero1, zero2, ..., zeroN)</code><br/>
	 * Where arguments are :
	 * - Arg(0) data: the current data chunk in the running data flow
	 * - Arg(1) dfa: a reference to the underlying FuncExpDFA instance
	 * - Arg(2) factor: a multiplier factor
	 * - Arg(3..n) zeroI: the x value for which the polynomial function is null.
	 * @return Float product(I: (data - zeroI))
	 */
	public function basicPolynomialFunction($args) {
		$nArgs = $this->getNumberOfArgs($args);
		$data = $args[0];
		$dfa = $args[1];
		if($dfa->isStartStream() || $dfa->isSingleData()) {
			if($nArgs > 2) $factor = $this->evaluateArg($args[2]);
			else $factor = 1.0;
			$nbZeros = $nArgs - 3;
			if($nbZeros > 0) {
				$zeros = array();				
				for($i = 3; $i < $nbZeros+3; $i++) {
					$zeros[] = $this->evaluateArg($args[$i]);
				}
			}			
			if($dfa->isStartStream()) {
				$dfa->setValueInContext('nbZeros', $nbZeros);
				$dfa->setValueInContext('factor', $factor);
				if($nbZeros > 0) $dfa->setValueInContext('zeros', $zeros);
			}
		}
		if($dfa->isRunning() || $dfa->isSingleData()) {
			if($dfa->isRunning()) {
				$nbZeros = $dfa->getValueInContext('nbZeros');
				$factor = $dfa->getValueInContext('factor');
				$zeros = $dfa->getValueInContext('zeros');
			}
	
			if($nbZeros > 0) {
				$returnValue = $factor;
				for($i=0;$i<$nbZeros;$i++) {
					$returnValue *= ($data - $zeros[$i]);
				}
				return $returnValue;
			}			
		}
	}
	
	// Sequences
	
	/**
	 * Returns a DataFlow source which generates a linear sequence of numbers
	 * FuncExp signature : <code>linearSequence(length, factor, shift, alternateSign)</code><br/>
	 * Where arguments are :
	 * - Arg(0) length: evaluates to a positive integer which is the length of the sequence. 
	 * Will compute the sequence for integers in range 1..length.
	 * - Arg(1) factor: evaluates to a number which will be used as a multiplier factor
	 * - Arg(2) shift: evaluates to a number which will be used as a shift value.
	 * - Arg(3) alternateSign: evaluates to a boolean. If true then the signs of the numbers in the sequence alternate.
	 * Once positive, once negative. Else always positive.
	 * @return DataFlowDumpable returns an instance of a DataFlowDumpable object which can be used a data flow source.
	 */
	public function linearSequence($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 4) throw new FuncExpEvalException("funcExp linearSequence should have at least four arguments: length, factor, shift and alternateSign", FuncExpEvalException::INVALID_ARGUMENT);
		$returnValue = ServiceProvider::getExclusiveAccessObject('LinearSequenceDFC');
		$returnValue->setLength($this->evaluateArg($args[0]));
		$returnValue->setFactor($this->evaluateArg($args[1]));
		$returnValue->setShift($this->evaluateArg($args[2]));
		$returnValue->setAlternateSign($this->evaluateArg($args[3]));
		return $returnValue;
	}
	
	/**
	 * Returns a DataFlow source which generates a range of numbers
	 * or packs a flow of positive integers into a range.
	 * FuncExp signature : <code>range(start, end, numberOfValues)</code><br/>
	 * Where arguments are :
	 * - Arg(0) start: evaluates to the starting number of the range
	 * - Arg(1) end: evaluates to ending number of the range
	 * - Arg(2) numberOfValues: evaluates to a positive integer which is the number of values calculated in the range
	 * @return DataFlowDumpable|float returns an instance of a DataFlowDumpable object which can be used a data flow source,
	 * or directly the current number in the range if the function is used in a data flow context.
	 */
	public function range($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 3) throw new FuncExpEvalException("funcExp range should have at least three arguments: start, end, numberOfValues", FuncExpEvalException::INVALID_ARGUMENT);
		$start = $this->evaluateArg($args[0]);
		$end = $this->evaluateArg($args[1]);
		// detects data flow context
		if($end instanceof FuncExpDFA) {			
			$data = $start;
			$dfa = $end;
			
			if($dfa->isStartStream() || $dfa->isSingleData()) {
				if($nArgs < 5) throw new FuncExpEvalException("funcExp range should have at least three arguments: start, end, numberOfValues", FuncExpEvalException::INVALID_ARGUMENT);
				$start = $this->evaluateArg($args[2]);
				$end = $this->evaluateArg($args[3]);
				$numberOfValues = $this->evaluateArg($args[4]);
				if($numberOfValues < 2) throw new FuncExpEvalException('numberOfValues must be >= 2', FuncExpEvalException::INVALID_ARGUMENT);
				if($dfa->isStartStream()) {
					$dfa->setValueInContext('start', $start);
					$dfa->setValueInContext('end', $end);
					$dfa->setValueInContext('numberOfValues', $numberOfValues);
				}
			}
			if($dfa->isRunning() || $dfa->isSingleData()) {
				if($dfa->isRunning()) {
					$start = $dfa->getValueInContext('start');
					$end = $dfa->getValueInContext('end');
					$numberOfValues = $dfa->getValueInContext('numberOfValues');
				}				
				return $data*($end-$start)/($numberOfValues-1) + ($start*$numberOfValues-$end)/($numberOfValues-1);
			}
		}
		// else returns a configured LinearSequence data flow connector
		else {
			$numberOfValues = $this->evaluateArg($args[2]);
			if($numberOfValues < 2) throw new FuncExpEvalException('numberOfValues must be >= 2', FuncExpEvalException::INVALID_ARGUMENT);
			$returnValue = ServiceProvider::getExclusiveAccessObject('LinearSequenceDFC');
			$returnValue->setLength($numberOfValues);
			$returnValue->setFactor(($end-$start)/($numberOfValues-1));
			$returnValue->setShift($start - ($end-$start)/($numberOfValues-1));
			$returnValue->setAlternateSign(false);
			return $returnValue;
		}
	}
	
	// Numbers
	
	/**
	 * Returns the quotient of two numbers
	 * FuncExp signature : <code>fraction(a, b)</code><br/>
	 * Where arguments are :
	 * - Arg(0) a: dividend
	 * - Arg(1) b: divisor
	 * @return Float returns the quotient equal to a/b;
	 * @throws FuncExpEvalException if b is equal to zero.
	 */
	public function fraction($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 2) throw new FuncExpEvalException("funcExp fraction should have two arguments: dividend and divisor", FuncExpEvalException::INVALID_ARGUMENT);
		$dividend = $this->evaluateArg($args[0]);
		$divisor = $this->evaluateArg($args[1]);
		if($divisor == 0) throw new FuncExpEvalException("divisor cannot equal zero", FuncExpEvalException::INVALID_ARGUMENT);
		return $dividend/$divisor;
	}
	
	/**
	 * Returns a multiple of pi
	 * FuncExp signature : <code>pi(multiple)</code><br/>
	 * Where arguments are :
	 * - Arg(0) multiple: optional number which is used as a multiplier of pi. Defaults to 1.
	 * @return Float returns multiple*pi
	 */
	public function pi($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs > 0) {
			$multiple = $this->evaluateArg($args[0]);
			return pi()*$multiple;
		}
		else return pi();		
	}
}

// Inner classes

/**
 * A data flow connector used to generate a linear sequence of numbers
 * Created by CWE on 27 janvier 2014
 */
class LinearSequenceDFC implements DataFlowDumpable {		
	private $lockedForUse = true;
	
	// Object lifecycle
	
	public function reset() {
		$this->freeMemory();	
		$this->lockedForUse = true;
		$this->length = 0;
		$this->factor = 0;
		$this->shift = 0;
		$this->alternateSign = false;				
	}	
	public function freeMemory() {		
		$this->lockedForUse = false;	
	}
		
	public function isLockedForUse() {
		return $this->lockedForUse;
	}
	
	public static function createInstance($length, $factor, $shift, $alternateSign=false) {
		$returnValue = new self();
		$returnValue->reset();
		$returnValue->setLength($length);
		$returnValue->setFactor($factor);
		$returnValue->setShift($shift);
		$returnValue->setAlternateSign($alternateSign);
		return $returnValue;
	}
	
	// Configuration
	
	private $length;
	public function setLength($length) {
		if(!is_numeric($length)) throw new FuncExpEvalException("length should be a positive integer", FuncExpEvalException::INVALID_ARGUMENT);
		else $this->length = $length;
	}
	
	private $factor;
	public function setFactor($factor) {
		if(!is_numeric($factor)) throw new FuncExpEvalException("factor should be a number", FuncExpEvalException::INVALID_ARGUMENT);
		else $this->factor = $factor;
	}
	
	private $shift;
	public function setShift($shift) {
		if(!is_numeric($shift)) throw new FuncExpEvalException("shift should be a number", FuncExpEvalException::INVALID_ARGUMENT);
		else $this->shift = $shift;
	}
	
	private $alternateSign;
	public function setAlternateSign($alternateSign) {
		if($alternateSign) $this->alternateSign = true; else $this->alternateSign = false;
	}
	
	// DataFlowDumpable implementation
	
	public function dumpIntoDataFlow($dataFlowService, $dataFlowContext) {
		$negative = false;
		for($i = 1; $i <= $this->length; $i++) {
			$dataFlowService->processDataChunk(($negative ? -($this->factor*$i+$this->shift) : $this->factor*$i+$this->shift), $dataFlowContext);
			if($this->alternateSign) $negative = !$negative;
		}
	}	
}