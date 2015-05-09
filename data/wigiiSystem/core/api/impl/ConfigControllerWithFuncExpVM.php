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
 * A configuration controller which integrates a func exp VM.
 * Created by CWE on 03 March 2014
 */
abstract class ConfigControllerWithFuncExpVM implements ConfigController
{	
	private $lockedForUse = true;
	private $vm;
	
	// Object lifecycle
	
	public function reset() {
		$this->freeMemory();
		$this->lockedForUse = true;
	}
	
	public function freeMemory() {
		unset($this->vm);
		$this->lockedForUse = false;
	}

	public function isLockedForUse() {
		return $this->lockedForUse;
	}
	
	// Configuration
	
	private $funcExpVMClassName;
	/**
	 * Sets the FuncExp VM class to use.
	 */
	public function setFuncExpVMClassName($className) {
		$this->funcExpVMClassName = $className;
	}
	/**
	 * Defaults to FuncExpVM
	 */
	protected function getFuncExpVMClassName() {
		if(!isset($this->funcExpVMClassName) || $this->funcExpVMClassName == '') {
			$this->funcExpVMClassName = 'FuncExpVM';
		}
		return $this->funcExpVMClassName;
	}
	
	private $vmModules;
	/**
	 * Loads a list of classes in the current context of the VM
	 * @param Array|String $modules an array of class names or one string
	 */
	public function useVMModules($modules) {
		$this->vmModules = $modules;
	}
	
	// ConfigController implementation
	
	public function processConfigurationNode($principal, $xmlConfig, $getWritableNode, $lp) {
		$this->vm = null;		
		$returnValue = null;
		try {
			// gets vm linked to parent evaluator
			$this->vm = $this->getFuncExpVM($principal, $this->getFuncExpVMClassName(), 
					$this->getElementEvaluator($principal, $this->getElementEvaluatorClassName($xmlConfig)));
			// configures the vm
			if(isset($this->vmModules)) $this->vm->useModules($this->vmModules);
			$this->vm->setFreeParentEvaluatorOnFreeMemory(true);
			
			// processes the configuration node
			$returnValue = $this->doProcessConfigurationNode($principal, $xmlConfig, $getWritableNode, $lp);
				
			// frees vm
			$this->vm->freeMemory();
		}
		catch(Exception $e) {
			if(isset($this->vm)) $this->vm->freeMemory();
			throw $e;
		}
		return $returnValue;
	}
	
	/**
	 * Does the job of processing the configuration node, but in an environment containing a configured VM.
	 * See method 'processConfigurationNode' for details about arguments.
	 * @param Principal $principal
	 * @param SimpleXMLElement $xmlConfig
	 * @param CallableObject $getWritableNode
	 * @param Array $lp
	 */
	abstract protected function doProcessConfigurationNode($principal, $xmlConfig, $getWritableNode, $lp);
	
	// FuncExp VM helpers
	
	/**
	 * Evaluates a FuncExp.
	 * @return Any returns the value of the evaluated FuncExp.
	 */
	protected function evaluateFuncExp($funcExp) {
		return $this->vm->evaluateFuncExp($funcExp, $this);
	}
	
	/**
	 * Returns a reference on the current instantiated FuncExp VM
	 * that can be used to lookup information inside the executing environment.
	 * @return FuncExpVM a FuncExpVM instance
	 */
	protected function getCurrentFuncExpVM() {
		return $this->vm;
	}
	
	// implementation
	
	/**
	 * FuncExpVM instantiator. Defaults to ServiceProvider::getFuncExpVM
	 * Redefine this method only if you need to change the implementation.
	 * @param ElementEvaluator $elementEvaluator an optional ElementEvaluator to be used as a parent evaluator
	 */
	protected function getFuncExpVM($principal, $className, $elementEvaluator=null) {
		return ServiceProvider::getFuncExpVM($principal, $elementEvaluator, $className);
	}
	
	/**
	 * Gets the ElementEvaluator class name to be used given the xml configuration node
	 * @param SimpleXMLElement $xml
	 * @return String the class name or null if not defined
	 */
	protected function getElementEvaluatorClassName($xml) {
		$x = $xml->xpath('parameters');
		if($x) return (string)$x[0]['Element_evaluator'];
		else return null;
	}
	
	/**
	 * Gets an ElementEvaluator given its class name
	 * @param String $className
	 * @return ElementEvaluator
	 */
	protected function getElementEvaluator($principal, $className) {
		return ServiceProvider::getElementEvaluator($principal, $className);
	}
}