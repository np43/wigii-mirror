<?php
/**
 *  This file is part of Wigii (R) software.
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
 * Light Client Template Record Manager implementation
 * Created by Medair (CWE,LMA) on 16.12.2016
 */
class LightClientTRM extends TemplateRecordManager {

	
	// Dependency injection
	
	private $_debugLogger;
	private $_executionSink;
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("LightClientTRM");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("LightClientTRM");
		}
		return $this->_executionSink;
	}
	
	
	
	// Object lifecycle
	
	/**
	 * @return LightClientTRM
	 */	
	public static function createInstance() {
		$returnValue = new self();
		return $returnValue;
	}

	
	// Implementation
	
	private $translationService;
	public function setTranslationService($translationService){
		$this->translationService = $translationService;
		return $this;
	}
	protected function getTranslationService(){
		//autowired
		if(!isset($this->translationService)){
			$this->translationService = ServiceProvider::getTranslationService();
		}
		return $this->translationService;
	}
	//alias of t (translate) method of the Translation service
	public function t($text, $node=null){
		return $this->getTranslationService()->t($this->getP(), $text, $node);
	}
	
	// Gett all data and convert it in object. Create an arborescence of groups and fields
	private $stack;
	private $nodes;
	private function getDataObject(){
		$this->stack = new SplStack();
		foreach($this->getRecord()->getFieldList()->getListIterator() as $k=>$v){
			
			//Get the XML of the field
			$fieldXml = $v->getXml();
			
			
			if($v->getDataType() !== null && $fieldXml['groupStart'] != '1' && $fieldXml['groupEnd'] != '1'){
				$node = $this->nodes[] = (object) $this->genericFormat($k, $v, $currentGroup);
				
				if(!$this->stack->isEmpty()){
					$top = $this->stack->top();
					$node->parent = $top;
					$top->childs[]= $node;
				}
			}
			if($fieldXml['groupStart'] == '1'){
				$group = $this->nodes[] = (object)$this->groupFormat($v);
				if(!$this->stack->isEmpty()){
					$top = $this->stack->top();
					$group->parent = $top;
					$top->childs[]= $group;
				}
				$this->stack->push($group); 
			}
			if($fieldXml['groupEnd'] == '1'){
				if($this->stack->pop());
			}
		}
		
		return $this->nodes;
	}
	
	//Create an array of a field element. This array will contain all information to show a field
	private function genericFormat($k, $v, $parent){
		//get the XML
		$fieldXml = $v->getXml();
	
		$valueTab['name'] = $v->getFieldName();
		$valueTab['value'] = $this->getRecord()->getFieldValue($k);
		$valueTab['formatValue'] = $this->formatValueFromRecord($v->getFieldName(), null, $this->getRecord());
		$valueTab['dataType'] = $v->getDatatype()->getDataTypeName();
		$valueTab['parent'] = null;
		$valueTab['childs'] = null;
		$valueTab['label'] = trim(strip_tags($this->t($v->getFieldName(), $fieldXml)));
	
		$fieldName = $v->getFieldName();
		$field = $v;
		
		switch ($this->currentScenario){
			case 'read':
				$dr = $this->getDetailRenderer();
				break;
			case 'update':
				$dr = $this->getFormRenderer();
				break;
		}
	
		//Get the diplayValue TPL
		/*
			ob_start();
			$dr->actOnField($v, $v->getDatatype());
			$valueTab['fieldRenderer'] = ob_get_contents();
			ob_end_clean();
			*/
		$dr->actOnField($v, $v->getDatatype());
		$valueTab['fieldRenderer'] = $this->getHtmlAndClean();
	
		//Get the label TPL
		$path = DATATYPE_PATH.$v->getDataType()->getDataTypeName()."_displayLabel.tpl.php";
		if($fieldXml['enableDynamicAttributes'] == '1' && file_exists($path)){
			ob_start();
			include($path);
			$valueTab['labelRenderer'] = ob_get_contents();
			ob_end_clean();
		}else{
			$valueTab['labelRenderer'] = $valueTab['label'];
		}
	
		//var_dump($fieldXml);
	
		return $valueTab;
	}
	
	//Create an array of a group element
	private function groupFormat($v){
		$fieldXml = $v->getXml();
		
		$valueTab['name'] = $v->getFieldName();
		$valueTab['parent'] = null;
		
		$valueTab['childs'] = null;
		$valueTab['status'] = 'groupStart';
		$valueTab['dataType'] = 'group';
		$valueTab['label'] = ($fieldXml['noLabel'] == '1')?'>':trim(strip_tags($this->t($v->getFieldName(), $fieldXml)), " \t\n\r\0\x0B:");
		$valueTab['labelNoSpace'] = str_replace('(', '', str_replace('+', '', str_replace(' ', '', trim(strip_tags($this->t($v->getFieldName(), $fieldXml)), " \t\n\r\0\x0B:)"))));
		$valueTab['fieldXml'] = $fieldXml;
		
		return $valueTab;
	}
	
	//Old function to get Data as an array, but without the arborescence
	private function getData(&$valueTab, &$groups, &$arbo){
		foreach($this->getRecord()->getFieldList()->getListIterator() as $k=>$v){
				
			//get fieldXML
			$fieldXml = $v->getXml();
				
			if($v->getDataType() !== null){
			
				$valueTab[$k] = $this->genericFormat($k, $v, $currentGroup);
		
				//$valueTab[$k]['fieldXml'] = $fieldXml;
				$group[$current]['field'] = $valueTab;
			}
				
			//Début et fin de groupe
			if($fieldXml['groupStart'] == '1'){
				$level++;
				$valueTab[$k]['name'] = $v->getFieldName();
				$valueTab[$k]['parentName'] = $currentGroup;
				$valueTab[$k]['status'] = 'groupStart';
				$valueTab[$k]['dataType'] = 'group';
				$valueTab[$k]['label'] = trim(strip_tags($this->t($v->getFieldName(), $fieldXml)), " \t\n\r\0\x0B:");
				$valueTab[$k]['labelNoSpace'] = str_replace('+', '', str_replace(' ', '', trim(strip_tags($this->t($v->getFieldName(), $fieldXml)), " \t\n\r\0\x0B:)(")));
				$valueTab[$k]['fieldXml'] = $fieldXml;
				$currentGroup = $v->getFieldName();
				//$group[$level]['groupName'] = $v->getFieldName();
		
				//var_dump($fieldXml);
			}
			if($fieldXml['groupEnd'] == '1'){
				//*
				$valueTab[$k]['name'] = $v->getFieldName();
				$valueTab[$k]['parentName'] = $valueTab[$curentGroup]['parentName'];
				$valueTab[$k]['status'] = 'groupEnd';
				$valueTab[$k]['dataType'] = 'group';
				//$valueTab[$k]['fieldXml'] = $fieldXml;
				//*/
		
				$level--;
				$currentGroup = $valueTab[$curentGroup]['parentName'];
				$group[$level]['groupName'] = $v->getFieldName();
			}
				
			//var_dump($valueTab);
		}
		
		//echo $this->createArbo(0, 0, $valueTab);
		
		$groups = $this->getGroup($valueTab);
		
		foreach($groups as $k => $v){
			foreach($valueTab as $k1 => $v1){
				//var_dump($v1['parentName']);
				if($v1['parentName'] == $k && $v1['dataType'] != 'group'){
					$arbo[$k][] = $v1;
				}
		
			}
			if(empty($arbo[$k])){
				unset($groups[$k]);
			}
				
		}
	}
	
	//Old function to get all groups. 
	private function getGroup($valueTab){
		$groupStack = new SplQueue();
		$groups;
		foreach($valueTab as $k => $v){
			if($v['dataType'] == 'group' && $v['status'] == 'groupStart'){
				$groups[$v['name']]['label'] = $v['label'];
				$groups[$v['name']]['labelNoSpace'] = $v['labelNoSpace'];
				$groups[$v['name']]['parent'] = $v['parentName'];
				$groupStack->push($groups[$v['name']]);
			}
		}
		
		//var_dump($groupStack);
		return $groups;
	}
	
	//Show the view template
	private $currentScenario;
	public function initTwig($scenario){
	
		$this->currentScenario = $scenario;
		
		//Chargement de Twig
		$loader = new Twig_Loader_Filesystem(CORE_PATH.'_webImplExecutor/templates/twig');
		$twig = new Twig_Environment($loader, array(
				//'cache' => CORE_PATH.'_webImplExecutor/templates/twig/cache',
		));
	
		$this->getData($valueTab, $groups, $arbo);
	
		$ObjectModel = $this->getDataObject();
		
		foreach ($ObjectModel as $v){
			if(!isset($v->parent) && $v->dataType == 'group'){
				$level1[] = $v;
			}
		}
		
		$subgroup = true;
		
		foreach ($level1 as $l1){
			foreach($l1->childs as $l2){
				if($l2->dataType == 'group'){
					$level2[] = $l2;
					//var_dump($l2);
				}
			}
		}
		
		$rootUrl = SITE_ROOT_forFileUrl;
		
		//var_dump($arbo);
		
		//var_dump($this->formatValueFromRecord('phases', null, $this->getRecord()));
		$this->put($twig->render('index.twig', compact('valueTab', 'groups', 'arbo', 'objectModel', 'level1', 'scenario', 'subgroup', 'rootUrl')));
	
	}
	
}



