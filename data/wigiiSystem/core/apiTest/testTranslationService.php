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
 * Tests UserAdminService
 * Created by LWR on 7 july 09
 */
class Test_TranslationService_translate extends WigiiApiTest
{
	public function __construct()
	{
		parent::__construct('Test_TranslationService_translate','TranslationService translate methods');
	}
	public function run()
	{
		$debL = TechnicalServiceProvider::getDebugLogger("Test_TranslationService_translate");
		
		$uAS = ServiceProvider::getTranslationService();
		$this->assertNotNull('TranslationService retrieved', $uAS);
		
		$authS = ServiceProvider::getAuthenticationService();
		if(!$authS->isMainPrincipalMinimal()) $authS->logout();
		
		$p = null;
		$key = "Filemanager";
		$lang = null; //"fr"; //"en";
		$xmlNode = null;
		$resultShouldBe= "Fichiers";
		$result = $uAS->t($p, $key, $xmlNode, $lang);
		$this->assertEqual("Principal = ".put($p).": Translate key: $key  in: ".$result.", should be: ".$resultShouldBe, $result, $resultShouldBe);
		$debL->write("<br/>-------------------------");
		
		$authS->login("admin", "admin", "LOCAL");
		$p = $authS->getMainPrincipal();
		//force the current module to be Filemanager
//		$p->getLastPreference("module")->setModuleName("Filemanager");
		$uAS->setExecutionModule(ServiceProvider::getModuleAdminService()->getModule($p, "Filemanager"));
		
//		$resultShouldBe= "Fichiers pour LOCAL héhé mais ç'emty";
//		$result = $uAS->t($p, $key, $xmlNode, $lang);
//		$this->assertEqual("Principal = ".put($p->getUsername()).": Translate key: $key  in: ".$result.", should be: ".$resultShouldBe, $result, $resultShouldBe);
		
		$key = "school";
		$resultShouldBe= "école vide les documents";
		$result = $uAS->t($p, $key, $xmlNode, $lang);
		$this->assertEqual("Principal = ".put($p->getUsername()).": Translate key: $key  in: ".$result.", should be: ".$resultShouldBe, $result, $resultShouldBe);
		
		$debL->write("<br/>-------------------------");
		
		$key = "school";
		$lang = "fr";
		$resultShouldBe= (string) ServiceProvider::getConfigService()->mf($p, "Contacts")->emergencyContact->label;
		$xmlNode = ServiceProvider::getConfigService()->mf($p, "Contacts")->emergencyContact;
		
		$result = $uAS->t($p, $key, $xmlNode, $lang);
		$this->assertEqual("Principal = ".put($p->getUsername()).": Translate key: $key  in: ".$result.", should be: ".$resultShouldBe, $result, $resultShouldBe);
		
		//$this->fail("Test not implemented yet");
	}
}

TestRunner::test(new Test_TranslationService_translate());


