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
 * Core technical service provider
 * Created by CWE on 3 juin 09
 * Modified by Medair in 2016 to integrate Box
 */
class TechnicalServiceProvider
{
	// singleton implementation
	private static $singleton;

	protected static function getInstance()
	{
		if(!isset(self::$singleton))
		{
			self::$singleton = new TechnicalServiceProvider();
		}
		return self::$singleton;
	}

	/**
	 * Returns true if TechnicalServiceProvider singleton is instanciated
	 */
	protected static function isUp()
	{
		return isset(self::$singleton);
	}

	/**
	 * Registers a technical service provider subclass as current singleton instance
	 * throws TechnicalServiceProviderException if an error occurs
	 */
	protected static function registerSingleInstance($technicalServiceProvider)
	{
		if(isset(self::$singleton)) throw new TechnicalServiceProviderException("Technical Service Provider has already been initialized, cannot change it dynamically", TechnicalServiceProviderException::FORBIDDEN);
		self::$singleton = $technicalServiceProvider;
	}

	// System principal management

	/**
	 * Adds a system principal or a list of system principals to the TechnicalServiceProvider
	 */
	public static function addSystemPrincipal($systemPrincipal)
	{
		self::getInstance()->doAddSystemPrincipal($systemPrincipal);
	}
	protected function doAddSystemPrincipal($systemPrincipal)
	{
		if(is_null($systemPrincipal)) return;
		$this->getSystemPrincipals()->unionPrincipalList($systemPrincipal);
	}
	private $systemPrincipals;
	/**
	 * Returns the list of actual system principals owned by the TechnicalServiceProvider
	 */
	protected function getSystemPrincipals()
	{
		//autowired
		if(!isset($this->systemPrincipals))
		{
			$this->systemPrincipals = PrincipalListArrayImpl::createInstance();
		}
		return $this->systemPrincipals;
	}
	/**
	 * Gets the root principal
	 */
	protected function getRootPrincipal()
	{
		$returnValue = ServiceProvider::getAuthorizationService()->findRootPrincipal($this->getSystemPrincipals());
		if(is_null($returnValue)) throw new TechnicalServiceProviderException("root principal has not been initialized by AuthorizationService", TechnicalServiceProviderException::FORBIDDEN);
		return $returnValue;
	}
	/**
	 * Gets the public principal
	 */
	protected function getPublicPrincipal()
	{
		$returnValue = ServiceProvider::getAuthorizationService()->findPublicPrincipal($this->getSystemPrincipals());
		if(is_null($returnValue)) throw new TechnicalServiceProviderException("public principal has not been initialized by AuthorizationService", TechnicalServiceProviderException::FORBIDDEN);
		return $returnValue;
	}


	// configuration

	private $classConfigurations;

	/**
	 * Stores an ObjectConfigurator instance that will be used to configure
	 * each created object of the given class.
	 * Note that not all classes support this dynamic configuration pattern,
	 * this depends of the implementation decision took in each createXXX method.
	 * @param $className the class name for which to apply the configuration when objects are instanciated
	 * @param $objectConfigurator an ObjectConfigurator instance which is used to configure the new created instance
	 */
	public static function configureClass($className, $objectConfigurator) {
		self::getInstance()->doConfigureClass($className, $objectConfigurator);
	}
	protected function doConfigureClass($className, $objectConfigurator) {
		if(empty($className)) throw new TechnicalServiceProviderException("className cannot be null", TechnicalServiceProviderException::INVALID_ARGUMENT);
		if(is_null($objectConfigurator)) throw new TechnicalServiceProviderException("objectConfigurator cannot be null", TechnicalServiceProviderException::INVALID_ARGUMENT);
		if(!isset($this->classConfigurations)) $this->classConfigurations = array();
		$this->classConfigurations[$className] = $objectConfigurator;
	}
	/**
	 * Stores an array of ObjectConfigurator instances that will be used to configure
	 * each created object of the given classes
	 * Note that not all classes support this dynamic configuration pattern,
	 * this depends of the implementation decision took in each createXXX method.
	 * @param $classConfigurations an array with key=className, value=ObjectConfigurator instance
	 */
	public static function configureClasses($classConfigurations) {
		self::getInstance()->doConfigureClasses($classConfigurations);
	}
	protected function doConfigureClasses($classConfigurations) {
		if(!is_array($classConfigurations)) throw new TechnicalServiceProviderException("classConfigurations should be an array", TechnicalServiceProviderException::INVALID_ARGUMENT);
		foreach($classConfigurations as $className => $objectConfigurator) {
			$this->doConfigureClass($className, $objectConfigurator);
		}
	}

	/**
	 * Configures a given object instance using
	 * the stored configuration if exists.
	 * @param $obj any object
	 */
	public static function configureObject($obj) {
		self::getInstance()->doConfigureObject($obj);
	}
	protected function doConfigureObject($obj) {
		if(is_null($obj)) throw new TechnicalServiceProviderException("object cannot be null", TechnicalServiceProviderException::INVALID_ARGUMENT);
		if(isset($this->classConfigurations)) {
			// gets all parent classes and puts them into a queue
			$class = get_class($obj);
			$classes = array($class);
			$class = get_parent_class($class);
			while($class) {
				array_unshift($classes, $class);
				$class = get_parent_class($class);
			}
			// sets configuration of all parent classes from root to current object class
			foreach($classes as $class) {
				$configurator = $this->classConfigurations[$class];
				if(isset($configurator)) {
					$configurator->configure($obj);
				}
			}
		}
	}

	// technical service providing


	private $mysqlFacade;

	public static function getMySqlFacade()
	{
		return self::getInstance()->getMySqlFacadeInstance();
	}

	/**
	 * default singleton
	 */
	protected function getMySqlFacadeInstance()
	{
		if(!isset($this->mysqlFacade))
		{
			$this->mysqlFacade = $this->createMySqlFacadeInstance();
		}
		return $this->mysqlFacade;
	}

	/**
	 * default as MySqlFacade
	 */
	protected function createMySqlFacadeInstance()
	{
		return new MySqlFacade();
	}
	
	private $elementFileAdminService;
	
	/**
	 * Helper class for managing files within Wigii elements with type of 'Files'
	 *
	 */
    public static function getElementFileAdminService(){
		return self::getInstance()->getElementFileAdminServiceInstance();
	}

	/**
	 * default singleton
	 */
	protected function getElementFileAdminServiceInstance()
	{
		if(!isset($this->elementFileAdminService))
		{
			$this->elementFileAdminService = $this->createElementFileAdminService();
		}
		return $this->elementFileAdminService;
	}

	protected function createElementFileAdminService()
	{
		return new ElementFileAdminService();
	}

	private $argValidator;

	public static function getArgValidator()
	{
		return self::getInstance()->getArgValidatorInstance();
	}

	/**
	 * default singleton
	 */
	protected function getArgValidatorInstance()
	{
		if(!isset($this->argValidator))
		{
			$this->argValidator = $this->createArgValidatorInstance();
		}
		return $this->argValidator;
	}

	/**
	 * default as ArgValidator
	 */
	protected function createArgValidatorInstance()
	{
		return new ArgValidator();
	}

	private $eventSubscriberService;

	public static function getEventSubscriberService()
	{
		return self::getInstance()->getEventSubscriberServiceInstance();
	}

	/**
	 * default singleton
	 */
	protected function getEventSubscriberServiceInstance()
	{
		if(!isset($this->eventSubscriberService))
		{
			$this->eventSubscriberService = $this->createEventSubscriberServiceInstance();
		}
		return $this->eventSubscriberService;
	}

	/**
	 * default as EventSubscriberService
	 */
	protected function createEventSubscriberServiceInstance()
	{
		return new EventSubscriberService();
	}

	private $wigiiEventsDispatcher;

	public static function getWigiiEventsDispatcher()
	{
		return self::getInstance()->getWigiiEventsDispatcherInstance();
	}

	/**
	 * default singleton
	 */
	protected function getWigiiEventsDispatcherInstance()
	{
		if(!isset($this->wigiiEventsDispatcher))
		{
			$this->wigiiEventsDispatcher = $this->createWigiiEventsDispatcherInstance();
		}
		return $this->wigiiEventsDispatcher;
	}

	/**
	 * default as WigiiEventsDispatcher
	 */
	protected function createWigiiEventsDispatcherInstance()
	{
		return new WigiiEventsDispatcher();
	}

	private $fileStatisticService;

	public static function getFileStatisticService()
	{
		return self::getInstance()->getFileStatisticServiceInstance();
	}

	/**
	 * default singleton
	 */
	protected function getFileStatisticServiceInstance()
	{
		if(!isset($this->fileStatisticService))
		{
			$this->fileStatisticService = $this->createFileStatisticServiceInstance();
		}
		return $this->fileStatisticService;
	}

	/**
	 * default as FileStatisticService
	 */
	protected function createFileStatisticServiceInstance()
	{
		return new FileStatisticService();
	}

	private $elementStatisticService;

	public static function getElementStatisticService()
	{
		return self::getInstance()->getElementStatisticServiceInstance();
	}

	/**
	 * default singleton
	 */
	protected function getElementStatisticServiceInstance()
	{
		if(!isset($this->elementStatisticService))
		{
			$this->elementStatisticService = $this->createElementStatisticServiceInstance();
		}
		return $this->elementStatisticService;
	}

	/**
	 * default as ElementStatisticService
	 */
	protected function createElementStatisticServiceInstance()
	{
		return new ElementStatisticService();
	}

	private $globalStatisticService;

	public static function getGlobalStatisticService()
	{
		return self::getInstance()->getGlobalStatisticServiceInstance();
	}

	/**
	 * default singleton
	 */
	protected function getGlobalStatisticServiceInstance()
	{
		if(!isset($this->globalStatisticService))
		{
			$this->globalStatisticService = $this->createGlobalStatisticServiceInstance();
		}
		return $this->globalStatisticService;
	}
	
	/**
	 * default as GlobalStatisticService
	 */
	protected function createGlobalStatisticServiceInstance()
	{
		return new GlobalStatisticService();
	}
	
	private $emailService;

	public static function getEmailService()
	{
		return self::getInstance()->getEmailServiceInstance();
	}

	/**
	 * default singleton
	 */
	protected function getEmailServiceInstance()
	{
		if(!isset($this->emailService))
		{
			$this->emailService = $this->createEmailServiceInstance();
		}
		return $this->emailService;
	}

	/**
	 * default as EmailService
	 */
	protected function createEmailServiceInstance()
	{
		throw new ServiceException("only supported in web impl", ServiceException::UNSUPPORTED_OPERATION);
	}

	private $stringTokenizer;

	public static function getStringTokenizer()
	{
		return self::getInstance()->getStringTokenizerInstance();
	}
	public static function recycleStringTokenizer($stringTokenizer)
	{
		self::getInstance()->recycleStringTokenizerInstance($stringTokenizer);
	}

	/**
	 * default as singlecall recyclable object
	 */
	protected function getStringTokenizerInstance()
	{
		if(isset($this->stringTokenizer))
		{
			$returnValue = $this->stringTokenizer;
			unset($this->stringTokenizer);
			$returnValue->reset();
		}
		else $returnValue = $this->createStringTokenizerInstance();
		return $returnValue;
	}
	protected function recycleStringTokenizerInstance($stringTokenizer)
	{
		if(isset($stringTokenizer))
		{
			$stringTokenizer->freeMemory();
			$this->stringTokenizer = $stringTokenizer;
		}
	}

	/**
	 * default as StringTokenizer
	 */
	protected function createStringTokenizerInstance()
	{
		return new StringTokenizer();
	}

	private $fieldSelectorLogExpParser;

	public static function getFieldSelectorLogExpParser()
	{
		return self::getInstance()->getFieldSelectorLogExpParserInstance();
	}
	/**
	 * default singleton
	 */
	protected function getFieldSelectorLogExpParserInstance()
	{
		if(!isset($this->fieldSelectorLogExpParser))
		{
			$this->fieldSelectorLogExpParser = $this->createFieldSelectorLogExpParserInstance();
		}
		return $this->fieldSelectorLogExpParser;
	}

	/**
	 * default as FieldSelectorLogExpRecordEvaluator
	 */
	protected function createFieldSelectorLogExpRecordEvaluatorInstance()
	{
		return new FieldSelectorLogExpRecordEvaluator();
	}

	private $fieldSelectorLogExpRecordEvaluator;

	public static function getFieldSelectorLogExpRecordEvaluator()
	{
		return self::getInstance()->getFieldSelectorLogExpRecordEvaluatorInstance();
	}
	/**
	 * default singleton
	 */
	protected function getFieldSelectorLogExpRecordEvaluatorInstance()
	{
		if(!isset($this->fieldSelectorLogExpRecordEvaluator))
		{
			$this->fieldSelectorLogExpRecordEvaluator = $this->createFieldSelectorLogExpRecordEvaluatorInstance();
		}
		return $this->fieldSelectorLogExpRecordEvaluator;
	}


	/**
	 * default as FieldSelectorLogExpParser
	 */
	protected function createFieldSelectorLogExpParserInstance()
	{
		return new FieldSelectorLogExpParser();
	}

	private $fieldSelectorFuncExpParser;

	public static function getFieldSelectorFuncExpParser()
	{
		return self::getInstance()->getFieldSelectorFuncExpParserInstance();
	}

	/**
	 * default singleton
	 */
	protected function getFieldSelectorFuncExpParserInstance()
	{
		if(!isset($this->fieldSelectorFuncExpParser))
		{
			$this->fieldSelectorFuncExpParser = $this->createFieldSelectorFuncExpParserInstance();
		}
		return $this->fieldSelectorFuncExpParser;
	}

	/**
	 * default as FieldSelectorFuncExpParser
	 */
	protected function createFieldSelectorFuncExpParserInstance()
	{
		return new FieldSelectorFuncExpParser();
	}

	private $valueListStringMapper;

	public static function getValueListStringMapper($separator=',')
	{
		return self::getInstance()->getValueListStringMapperInstance($separator);
	}
	public static function recycleValueListStringMapper($valueListStringMapper)
	{
		self::getInstance()->recycleValueListStringMapperInstance($valueListStringMapper);
	}

	/**
	 * default as singlecall recyclable object
	 */
	protected function getValueListStringMapperInstance($separator=',')
	{
		if(isset($this->valueListStringMapper))
		{
			$returnValue = $this->valueListStringMapper;
			unset($this->valueListStringMapper);
			$returnValue->reset($separator);
		}
		else $returnValue = $this->createValueListStringMapperInstance($separator);
		return $returnValue;
	}
	protected function recycleValueListStringMapperInstance($valueListStringMapper)
	{
		if(isset($valueListStringMapper))
		{
			$valueListStringMapper->freeMemory();
			$this->valueListStringMapper = $valueListStringMapper;
		}
	}

	/**
	 * default as ValueListStringMapper
	 */
	protected function createValueListStringMapperInstance($separator=',')
	{
		return ValueListStringMapper::createInstance($separator);
	}

	private $valueListArrayMapper;

	public static function getValueListArrayMapper($distinct=false, $possibleSeparator = null, $trim =false)
	{
		return self::getInstance()->getValueListArrayMapperInstance($distinct, $possibleSeparator, $trim);
	}
	public static function recycleValueListArrayMapper($valueListArrayMapper)
	{
		self::getInstance()->recycleValueListArrayMapperInstance($valueListArrayMapper);
	}

	/**
	 * default as singlecall recyclable object
	 */
	protected function getValueListArrayMapperInstance($distinct=false, $possibleSeparator = null, $trim =false)
	{
		if(isset($this->valueListArrayMapper))
		{
			$returnValue = $this->valueListArrayMapper;
			unset($this->valueListArrayMapper);
			$returnValue->reset($distinct, $possibleSeparator, $trim);
		}
		else $returnValue = $this->createValueListArrayMapperInstance($distinct, $possibleSeparator, $trim);
		return $returnValue;
	}
	protected function recycleValueListArrayMapperInstance($valueListArrayMapper)
	{
		if(isset($valueListArrayMapper))
		{
			$valueListArrayMapper->freeMemory();
			$this->valueListArrayMapper = $valueListArrayMapper;
		}
	}

	/**
	 * default as ValueListArrayMapper
	 */
	protected function createValueListArrayMapperInstance($distinct=false, $possibleSeparator = null, $trim =false)
	{
		return ValueListArrayMapper::createInstance($distinct, $possibleSeparator, $trim);
	}

	private $searchBarLogExpParser;

	public static function getSearchBarLogExpParser()
	{
		return self::getInstance()->getSearchBarLogExpParserInstance();
	}

	/**
	 * default singleton
	 */
	protected function getSearchBarLogExpParserInstance()
	{
		if(!isset($this->searchBarLogExpParser))
		{
			$this->searchBarLogExpParser = $this->createSearchBarLogExpParserInstance();
		}
		return $this->searchBarLogExpParser;
	}

	/**
	 * default as SearchBarLogExpParser
	 */
	protected function createSearchBarLogExpParserInstance()
	{
		return new SearchBarLogExpParser();
	}

	private $searchBarOrLogExpParser;

	public static function getSearchBarOrLogExpParser()
	{
		return self::getInstance()->getSearchBarOrLogExpParserInstance();
	}

	/**
	 * default singleton
	 */
	protected function getSearchBarOrLogExpParserInstance()
	{
		if(!isset($this->searchBarOrLogExpParser))
		{
			$this->searchBarOrLogExpParser = $this->createSearchBarOrLogExpParserInstance();
		}
		return $this->searchBarOrLogExpParser;
	}

	/**
	 * default as SearchBarOrLogExpParser
	 */
	protected function createSearchBarOrLogExpParserInstance()
	{
		return new SearchBarOrLogExpParser();
	}

	private $dataFlowService;

	public static function getDataFlowService()
	{
		return self::getInstance()->getDataFlowServiceInstance();
	}

	/**
	 * default singleton
	 */
	protected function getDataFlowServiceInstance()
	{
		if(!isset($this->dataFlowService))
		{
			$this->dataFlowService = $this->createDataFlowServiceInstance();
		}
		return $this->dataFlowService;
	}

	/**
	 * default as DataFlowServiceImpl
	 */
	protected function createDataFlowServiceInstance()
	{
		return new DataFlowServiceImpl();
	}


	private $wplToolbox;

	public static function getWplToolbox()
	{
		return self::getInstance()->getWplToolboxInstance();
	}

	/**
	 * default singleton
	 */
	protected function getWplToolboxInstance()
	{
		if(!isset($this->wplToolbox))
		{
			$this->wplToolbox = $this->createWplToolboxInstance();
		}
		return $this->wplToolbox;
	}

	/**
	 * default as WplToolbox
	 */
	protected function createWplToolboxInstance()
	{
		return new WplToolbox();
	}

	private $fieldWithSelectedSubfieldsListFiller;

	public static function getFieldWithSelectedSubfieldsListFiller()
	{
		return self::getInstance()->getFieldWithSelectedSubfieldsListFillerInstance();
	}

	/**
	 * default singleton
	 */
	protected function getFieldWithSelectedSubfieldsListFillerInstance()
	{
		if(!isset($this->fieldWithSelectedSubfieldsListFiller))
		{
			$this->fieldWithSelectedSubfieldsListFiller = $this->createFieldWithSelectedSubfieldsListFillerInstance();
		}
		return $this->fieldWithSelectedSubfieldsListFiller;
	}

	/**
	 * default as FieldWithSelectedSubfieldsListFiller
	 */
	protected function createFieldWithSelectedSubfieldsListFillerInstance()
	{
		return new FieldWithSelectedSubfieldsListFiller();
	}

	private $wigiiGraphService;

	public static function getWigiiGraphService()
	{
		return self::getInstance()->getWigiiGraphServiceInstance();
	}

	/**
	 * default singleton
	 */
	protected function getWigiiGraphServiceInstance()
	{
		if(!isset($this->wigiiGraphService))
		{
			$this->wigiiGraphService = $this->createWigiiGraphServiceInstance();
		}
		return $this->wigiiGraphService;
	}

	/**
	 * default as WigiiGraphService
	 */
	protected function createWigiiGraphServiceInstance()
	{
		return new WigiiGraphService();
	}

	private $guzzleHelper;

	public static function getGuzzleHelper()
	{
		return self::getInstance()->getGuzzleHelperInstance();
	}

	/**
	 * default singleton
	 */
	protected function getGuzzleHelperInstance()
	{
		if(!isset($this->guzzleHelper))
		{
			$this->guzzleHelper = $this->createGuzzleHelperInstance();
		}
		return $this->guzzleHelper;
	}

	/**
	 * default as GuzzleHelper
	 */
	protected function createGuzzleHelperInstance()
	{
		return new GuzzleHelper();
	}

	private $htmlPurifier;

	/**
	 * http://htmlpurifier.org/docs
	 * The basic code for getting HTML Purifier setup is very simple:
	 * require_once '/path/to/HTMLPurifier.auto.php';
	 *
	 * $config = HTMLPurifier_Config::createDefault();
	 * $purifier = new HTMLPurifier($config);
	 * $clean_html = $purifier->purify($dirty_html);
	 *
	 * Replace $dirty_html with the HTML you want to purify and use $clean_html instead.
	 * While HTML Purifier has a lot of configuration knobs, the default configuration of
	 * HTML Purifier is quite safe and should work for many users.
	 * It's highly recommended to take a look at the full install documentation for more
	 * information, as it will give advice on how to make sure HTML Purifier's output is
	 * matches your page's character encoding
	 */
	public static function getHTMLPurifier()
	{
		return self::getInstance()->getHTMLPurifierInstance();
	}

	/**
	 * default singleton
	 */
	protected function getHTMLPurifierInstance()
	{
		if(!isset($this->htmlPurifier))
		{
			$this->htmlPurifier = $this->createHTMLPurifierInstance();
		}
		return $this->htmlPurifier;
	}

	/**
	 * default as HTMLPurifier
	 */
	protected function createHTMLPurifierInstance()
	{
		$config = HTMLPurifier_Config::createDefault();
		//allow any merge fields in links: change in htmlpurifier-4.5.0.lite/library/HTMLPurifier/AttrDef/URI/Host.php
		//change done for the WigiiSystem implementation:
        //character $ must be allowed in URI in order to use Wigii email mergeData fields
        //$a   = '[a-z]';     // alpha 			changed to	//	$a   = '[$a-z]';     // alpha
        //$an  = '[a-z0-9]';  // alphanum 		changed to	//	$an  = '[$a-z0-9]';  // alphanum
        //$and = '[a-z0-9-]'; // alphanum | "-"	changed to	//	$and = '[$a-z0-9-]'; // alphanum | "-"
        //for allow target="_blank" in html link
		//$config->set('Core.Encoding', 'UTF-8');
		//$config->set('Core.EscapeNonASCIICharacters', true);
		$def = $config->getHTMLDefinition(true);
		$def->addAttribute('a', 'target', 'Enum#_blank,_self,_target,_top');
		return new HTMLPurifier($config);
	}

	private $funcExpBuilder;

	public static function getFuncExpBuilder()
	{
		return self::getInstance()->getFuncExpBuilderInstance();
	}
	/**
	 * Returns a new instance of a FuncExpBuilder
	 */
	public static function createFuncExpBuilderInstance() {
		return self::getInstance()->doCreateFuncExpBuilderInstance();
	}
	/**
	 * default singleton	
	 */
	protected function getFuncExpBuilderInstance()
	{	
		if(!isset($this->funcExpBuilder))
		{
			$this->funcExpBuilder = $this->doCreateFuncExpBuilderInstance();
		}
		return $this->funcExpBuilder;		
	}

	/**
	 * default as FuncExpBuilder
	 */
	protected function doCreateFuncExpBuilderInstance()
	{
		return new FuncExpBuilder();
	}
		
	public static function getFuncExpParameterHolder()
	{
		return self::getInstance()->getFuncExpParameterHolderInstance();
	}

	/**
	 * default as exclusive access object of type FuncExpParameterHolder
	 */
	protected function getFuncExpParameterHolderInstance()
	{
		return ServiceProvider::getExclusiveAccessObject('FuncExpParameterHolder');
	}
	
	/**
	 * @return FieldSelectorLogExpFuncExpBuilder
	 */
	public static function getFieldSelectorLogExpFuncExpBuilder()
	{
		return self::getInstance()->getFieldSelectorLogExpFuncExpBuilderInstance();
	}
	
	/**
	 * default as exclusive access object of type FieldSelectorLogExpFuncExpBuilder
	 */
	protected function getFieldSelectorLogExpFuncExpBuilderInstance()
	{
		return ServiceProvider::getExclusiveAccessObject('FieldSelectorLogExpFuncExpBuilder');
	}
	
	
	/**
	 * Creates a default implementation instance of a WigiiBPLParameter
	 * @return WigiiBPLParameter
	 */
	public static function createWigiiBPLParameterInstance()
	{
		return self::getInstance()->doCreateWigiiBPLParameterInstance();
	}
	/**
	 * default as WigiiBPLParameterArrayImpl
	 */
	protected function doCreateWigiiBPLParameterInstance() {
		return WigiiBPLParameterArrayImpl::createInstance(); 
	}
	
	/**
	 * Creates an instance of a SubElementInMemoryList
	 * @return SubElementInMemoryList
	 */
	public static function createSubElementInMemoryListInstance()
	{
		return self::getInstance()->doCreateSubElementInMemoryListInstance();
	}
	/**
	 * default as SubElementInMemoryList
	 */
	protected function doCreateSubElementInMemoryListInstance() {
		return new SubElementInMemoryList();
	}
	
	/**
	 * @param Closure|String $method a closure representing the code that should be executed or 
	 * a string which is an object method name or a function name
	 * @param Any $obj an optional object instance which holds the method instance that should be executed.
	 * @return RowListCallbackImpl
	 */
	public static function getRowlistCallback($method, $obj=null)
	{
		return self::getInstance()->getRowListCallbackInstance($method, $obj);
	}
	
	/**
	 * @return RowListCallbackImpl default as exclusive access object of type RowListCallbackImpl
	 */
	protected function getRowListCallbackInstance($method, $obj=null)
	{
		$returnValue = ServiceProvider::getExclusiveAccessObject('RowListCallbackImpl');
		$returnValue->setAddRowCallback($method, $obj);
		return $returnValue;
	}
	
	private $boxServiceFormExecutor;
	
	/**
	 * Creates Box.com facade
	 * @return BoxServiceFormExecutor
	 */
	public static function getBoxServiceFormExecutor() {
		return self::getInstance()->getBoxServiceFormExecutorInstance();
	}
	/**
	 * defaults singleton
	 */
	protected function getBoxServiceFormExecutorInstance() {
		if(!isset($this->boxServiceFormExecutor)) {
			$this->boxServiceFormExecutor = $this->createBoxServiceFormExecutorInstance();
		}
		return $this->boxServiceFormExecutor;
	}
	protected function createBoxServiceFormExecutorInstance() {
		return ServiceProvider::createWigiiObject('BoxServiceFormExecutor');
	}
	
	private $qlikSenseFormExecutor;
	
	/**
	 * Creates QlikSense facade
	 * @return QlikSenseFormExecutor
	 */
	public static function getQlikSenseFormExecutor() {
		return self::getInstance()->getQlikSenseFormExecutorInstance();
	}
	/**
	 * defaults singleton
	 */
	protected function getQlikSenseFormExecutorInstance() {
		if(!isset($this->qlikSenseFormExecutor)) {
			$this->qlikSenseFormExecutor = $this->createQlikSenseFormExecutorInstance();
		}
		return $this->qlikSenseFormExecutor;
	}
	protected function createQlikSenseFormExecutorInstance() {
		return ServiceProvider::createWigiiObject('QlikSenseFormExecutor');
	}
	
	/**
	 * default as CfgFieldLogExpEvaluator
	 */
	protected function createCfgFieldLogExpEvaluatorInstance()
	{
	    return new CfgFieldLogExpEvaluator();
	}
	
	private $cfgFieldLogExpEvaluator;
	
	public static function getCfgFieldLogExpEvaluator()
	{
	    return self::getInstance()->getCfgFieldLogExpEvaluatorInstance();
	}
	/**
	 * default singleton
	 */
	protected function getCfgFieldLogExpEvaluatorInstance()
	{
	    if(!isset($this->cfgFieldLogExpEvaluator))
	    {
	        $this->cfgFieldLogExpEvaluator = $this->createCfgFieldLogExpEvaluatorInstance();
	    }
	    return $this->cfgFieldLogExpEvaluator;
	}
	
	
	// base infrastructure

	/**
	 * Creates an empty principal instance
	 * Do not use directly this function, but rather call Principal::createInstance(...)
	 */
	public static function createPrincipalInstance() {
		return self::getInstance()->doCreatePrincipalInstance();
	}
	/**
	 * default as Principal
	 */
	protected function doCreatePrincipalInstance() {
		return new Principal;
	}

	/**
	 * Do not use directly this function, but rather call DebugLogger::getInstance($typeName)
	 */
	public static function getDebugLogger($typeName)
	{
		return self::getInstance()->getDebugLoggerInstance($typeName);
	}

	/**
	 * default creates new instance
	 */
	protected function getDebugLoggerInstance($typeName)
	{
		// if we have additional debug loggers, then uses MultipleDebugLogger
		// else uses system DebugLogger
		if(empty($this->additionalDebugLoggerClasses)) return $this->createDebugLoggerInstance($typeName);
		else return $this->getMultipleDebugLoggerInstance($typeName);
	}

	/**
	 * default as DebugLogger
	 */
	protected function createDebugLoggerInstance($typeName)
	{
		return new DebugLogger($typeName);
	}

	/**
	 * Do not use directly this function, but rather call ExecutionSink::getInstance($type)
	 */
	public static function getExecutionSink($typeName)
	{
		return self::getInstance()->getExecutionSinkInstance($typeName);
	}

	/**
	 * default creates new instance
	 */
	protected function getExecutionSinkInstance($typeName)
	{
		// if we have additional execution sinks, then uses MultipleExecutionSink
		// else uses system ExecutionSink
		if(empty($this->additionalExecutionSinkClasses)) return $this->createExecutionSinkInstance($typeName);
		else return $this->getMultipleExecutionSinkInstance($typeName);
	}

	/**
	 * default as ExecutionSink
	 */
	protected function createExecutionSinkInstance($typeName)
	{
		return new ExecutionSink($typeName);
	}

	private $exceptionSink;

	/**
	 * Do not use directly this function, but rather call ExceptionSink::publish method
	 */
	public static function getExceptionSink()
	{
		return self::getInstance()->getExceptionSinkInstance();
	}

	/**
	 * default singleton
	 */
	protected function getExceptionSinkInstance()
	{
		// if we have additional exception sinks, then uses MultipleExceptionSink
		// else uses system ExceptionSink
		if(empty($this->additionalExceptionSinkClasses)) {
			// exception sink is always a singleton in system
			if(!isset($this->exceptionSink))
			{
				$this->exceptionSink = $this->createExceptionSinkInstance();
			}
			return $this->exceptionSink;
		}
		else return $this->getMultipleExceptionSinkInstance();
	}

	/**
	 * default as ExceptionSink
	 */
	protected function createExceptionSinkInstance()
	{
		return new ExceptionSink();
	}

	/**
	 * Informs the technical service provider to use additionals debug loggers
	 * @param $additionalDebugLoggerClasses an array of class names or one single class name.
	 * Each class name should match a class of type DebugLogger.
	 * It is also possible to pass an array of classNames with attached ObjectConfigurator instances :
	 * an array where key=DebugLogger sub class name, value=ObjectConfigurator model
	 */
	public static function useAdditionalDebugLoggers($additionalDebugLoggerClasses) {
		self::getInstance()->doUseAdditionalDebugLoggers($additionalDebugLoggerClasses);
	}
	protected function doUseAdditionalDebugLoggers($additionalDebugLoggerClasses) {
		if(empty($additionalDebugLoggerClasses)) return;
		if(!isset($this->additionalDebugLoggerClasses)) $this->additionalDebugLoggerClasses = array();
		// array of class names or an array with key=className and value=ObjectConfigurator
		if(is_array($additionalDebugLoggerClasses)) {
			reset($additionalDebugLoggerClasses);
			// array of class names
			if(is_int(key($additionalDebugLoggerClasses))) {
				$values = array_values($additionalDebugLoggerClasses);
				$this->additionalDebugLoggerClasses = array_merge($this->additionalDebugLoggerClasses, array_combine($values, $values));
			}
			// array of classname->configurator or classname->classname
			else $this->additionalDebugLoggerClasses = array_merge($this->additionalDebugLoggerClasses, $additionalDebugLoggerClasses);

		}
		// single class name string
		else $this->additionalDebugLoggerClasses[$additionalDebugLoggerClasses] = $additionalDebugLoggerClasses;
	}
	/**
	 * an array of strings. key=className, value=className.
	 */
	protected $additionalDebugLoggerClasses;

	/**
	 * defaults creates new instance
	 */
	protected function getMultipleDebugLoggerInstance($typeName) {
		$returnValue = $this->createMultipleDebugLoggerInstance($typeName);
		$returnValue->useAdditionalDebugLoggers($this->additionalDebugLoggerClasses);
		return $returnValue;
	}

	/**
	 * Defaults to MultipleDebugLogger
	 */
	protected function createMultipleDebugLoggerInstance($typeName) {
		return new MultipleDebugLogger($this->createDebugLoggerInstance($typeName));
	}

	/**
	 * Informs the technical service provider to use additionals execution sinks
	 * @param $additionalExecutionSinkClasses an array of class names or one single class name.
	 * Each class name should match a class of type ExecutionSink.
	 * It is also possible to pass an array of classNames with attached ObjectConfigurator instances :
	 * an array where key=ExecutionSink sub class name, value=ObjectConfigurator model
	 */
	public static function useAdditionalExecutionSinks($additionalExecutionSinkClasses) {
		self::getInstance()->doUseAdditionalExecutionSinks($additionalExecutionSinkClasses);
	}
	protected function doUseAdditionalExecutionSinks($additionalExecutionSinkClasses) {
		if(empty($additionalExecutionSinkClasses)) return;
		if(!isset($this->additionalExecutionSinkClasses)) $this->additionalExecutionSinkClasses = array();
		// array of class names
		if(is_array($additionalExecutionSinkClasses)) {
			reset($additionalExecutionSinkClasses);
			// array of class names
			if(is_int(key($additionalExecutionSinkClasses))) {
				$values = array_values($additionalExecutionSinkClasses);
				$this->additionalExecutionSinkClasses = array_merge($this->additionalExecutionSinkClasses, array_combine($values, $values));
			}
			// array of classname->configurator or classname->classname
			else $this->additionalExecutionSinkClasses = array_merge($this->additionalExecutionSinkClasses, $additionalExecutionSinkClasses);
		}
		// single class name string
		else $this->additionalExecutionSinkClasses[$additionalExecutionSinkClasses] = $additionalExecutionSinkClasses;
	}
	/**
	 * an array of strings. key=className, value=className.
	 */
	protected $additionalExecutionSinkClasses;

	/**
	 * defaults creates new instance
	 */
	protected function getMultipleExecutionSinkInstance($typeName) {
		$returnValue = $this->createMultipleExecutionSinkInstance($typeName);
		$returnValue->useAdditionalExecutionSinks($this->additionalExecutionSinkClasses);
		return $returnValue;
	}

	/**
	 * Defaults to MultipleExecutionSink
	 */
	protected function createMultipleExecutionSinkInstance($typeName) {
		return new MultipleExecutionSink($this->createExecutionSinkInstance($typeName));
	}

	/**
	 * Informs the technical service provider to use additionals exception sinks
	 * @param $additionalExceptionSinkClasses an array of class names or one single class name.
	 * Each class name should match a class of type ExceptionSink.
	 * It is also possible to pass an array of classNames with attached ObjectConfigurator instances :
	 * an array where key=ExceptionSink sub class name, value=ObjectConfigurator model
	 */
	public static function useAdditionalExceptionSinks($additionalExceptionSinkClasses) {
		self::getInstance()->doUseAdditionalExceptionSinks($additionalExceptionSinkClasses);
	}
	protected function doUseAdditionalExceptionSinks($additionalExceptionSinkClasses) {
		if(empty($additionalExceptionSinkClasses)) return;
		if(!isset($this->additionalExceptionSinkClasses)) $this->additionalExceptionSinkClasses = array();
		// array of class names
		if(is_array($additionalExceptionSinkClasses)) {
			reset($additionalExceptionSinkClasses);
			// array of class names
			if(is_int(key($additionalExceptionSinkClasses))) {
				$values = array_values($additionalExceptionSinkClasses);
				$this->additionalExceptionSinkClasses = array_merge($this->additionalExceptionSinkClasses, array_combine($values, $values));
			}
			// array of classname->configurator or classname->classname
			else $this->additionalExceptionSinkClasses = array_merge($this->additionalExceptionSinkClasses, $additionalExceptionSinkClasses);
		}
		// single class name string
		else $this->additionalExceptionSinkClasses[$additionalExceptionSinkClasses] = $additionalExceptionSinkClasses;
	}
	/**
	 * an array of strings. key=className, value=className.
	 */
	protected $additionalExceptionSinkClasses;

	private $multipleExceptionSink;
	/**
	 * defaults singleton
	 */
	protected function getMultipleExceptionSinkInstance() {
		// exception sink is always a singleton in system
		if(!isset($this->multipleExceptionSink))
		{
			$this->multipleExceptionSink = $this->createMultipleExceptionSinkInstance();
			$this->multipleExceptionSink->useAdditionalExceptionSinks($this->additionalExceptionSinkClasses);
		}
		return $this->multipleExceptionSink;
	}

	/**
	 * Defaults to MultipleExceptionSink
	 */
	protected function createMultipleExceptionSinkInstance() {
		return new MultipleExceptionSink($this->createExceptionSinkInstance());
	}
}
