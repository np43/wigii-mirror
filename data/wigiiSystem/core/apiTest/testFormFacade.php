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

/*
 * Created on 31 juil. 09
 * by LWR
 */


?>
<div id='FormFacadegetElementForm' style='background-color:#ddd; display:none; '></div><div style='clear:both;'></div>
<div id="FormFacadegetContactFormButton" style="cursor:pointer; border:#000 solid 1px; background-color:#ccc; color:#000; font-weight:bold; width:200px; text-align:center; padding:5px;">Get Contact form!</div>
<script type="text/javascript" >
$('#FormFacadegetContactFormButton').click(function(){
	update("FormFacadegetElementForm/E-library/Contacts/getElementForm");
});
</script>
<?

?>
<div id='FormFacadegetElementForm2' style='background-color:#ddd; display:none; '></div><div style='clear:both;'></div>
<div id="FormFacadegetContactFormButton2" style="cursor:pointer; border:#000 solid 1px; background-color:#ccc; color:#000; font-weight:bold; width:200px; text-align:center; padding:5px;">Change Blobs</div>
<script type="text/javascript" >
$('#FormFacadegetContactFormButton2').click(function(){
	update("FormFacadegetElementForm2/E-library/Contacts/changeBlobs/100122");
});
</script>
<?

?>
<div id='FormFacadegetLoginForm' style='background-color:#ddd; display:none; '></div><div style='clear:both;'></div>
<div id="FormFacadegetLoginFormButton" style="cursor:pointer; border:#000 solid 1px; background-color:#ccc; color:#000; font-weight:bold; width:200px; text-align:center; padding:5px;">Login in!</div>
<script type="text/javascript" >
$('#FormFacadegetLoginFormButton').click(function(){
	update("FormFacadegetLoginForm/E-library/Filemanager/getLoginForm");
});
</script>
<?

?>
<div id="FormFacadegetMainPrincipal" style="cursor:pointer; border:#000 solid 1px; background-color:#ccc; color:#000; font-weight:bold; width:200px; text-align:center; padding:5px;">Get Actual Main Principal</div>
<script type="text/javascript" >
$('#FormFacadegetMainPrincipal').click(function(){
	update("Alert/E-library/Filemanager/getMainPrincipal");
});
</script>
<?

/**
 * Tests FormFacade
 * Created by LWR 31 july
 */
class Test_FormFacade_main extends WigiiApiTest
{
	public function __construct()
	{
		parent::__construct('Test_FormFacade_main','FormFacade main methods');
	}
	public function run()
	{
		$dbL = DebugLogger::getInstance("Test_FormFacade_main");
		$authS = ServiceProvider::getAuthenticationService();
		$p = $authS->getMainPrincipal();

		$form = FormFacade::createInstance();

		$this->assertNotNull("FormFacade retrieved", $form);
//
//		//action bidon
//		$action = "testForm/E-library/Filemanager/getForm";
//		$config = ServiceProvider::getConfigService()->m($p, "Contacts")->fields;
//		$form->set($p, "login_form", $config, $action);
//		$form->setMetrics(150, 400);
//
//		$form->addErrorToField($p, "first_name", "mandatory");
//		echo "<div id='testForm'>";
//		echo $form->fetch($p);
//		echo "</div>";

//		$form = new ihmForm("login_form");
//
//		$form->addField("login", "Strings");
//
//		echo $form->fetch();

	}
}

TestRunner::test(new Test_FormFacade_main());


