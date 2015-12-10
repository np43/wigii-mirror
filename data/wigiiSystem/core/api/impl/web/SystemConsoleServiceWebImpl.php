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
 * SystemConsoleService web implementation
 * Created on 24 juil. 09 by LWR
 * Modified by CWE on 01.12.2015 to support JS notifications
 */
class SystemConsoleServiceWebImpl extends SystemConsoleServiceImpl {

	private $_debugLogger;
	private $_executionSink;

	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("SystemConsoleServiceWebImpl");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("SystemConsoleServiceWebImpl");
		}
		return $this->_executionSink;
	}

	protected $displayEnabled;
	protected function setDisplayEnabled($var){ $this->displayEnabled = $var; }
	protected function isDisplayEnabled(){ return $this->displayEnabled; }

	public function __construct($displayEnabled = false){
		parent::__construct();
		$this->setDisplayEnabled($displayEnabled);
	}

	protected function shouldStoreMessage($classname) {

		//exclude
//		switch ($classname) {
//			case "DbAdminServiceImpl" :
//			case "ConfigServiceImpl_0" :
//			case "ConfigServiceCoreImpl_0" :
//			case "ConfigServiceSubElementImpl_0" :
//			case "ConfigServiceImpl_1" :
//			case "ConfigServiceCoreImpl_1" :
//			case "ConfigServiceSubElementImpl_1" :
//			case "FieldSelectorFuncExpParser" :

//			case "FormExecutor" :
//			case "WigiiExecutor" :
//			case "ElementServiceImpl" :
//			case "GroupAdminServiceImpl" :
//			case "UserAdminServiceImpl" :
//			case "TranslationServiceImpl" :
//			case "AttributeExpConfigController" :
//			case "DataFlowServiceImpl" :
//			case "ConfigServiceCoreImpl" :
//			case "ConfigServiceImpl" :
//			case "ConfigServiceSubElementImpl" :
//			case "MySqlFacade" :
//			case "MySqlFacadeWithPConnections" :
//			case "MySqlFacadeCodeProfilingImpl" :
//			case "ReportingElementEvaluator" :
//			case "SystemConsoleServiceWebImpl";
//			case "ExecutionServiceWebImpl";
//			case "DataFlowServiceImpl" :
//			case "GuzzleHelper" :
//			case "JasperReportsFacade" :
//				return false;
//		}
		return true;
	}

	public function isMessagePending(){
		if($this->messages==null) return false;
		return true;
	}
	public function logMessageInFile(){
		if(isset($this->messages)){
			//write in a log file:
			global $DEBUG_EXECUTION_LOG_FILEPATH;
			$p = ServiceProvider::getAuthenticationService()->getMainPrincipal();
			$pLabel = null;
			if($p->getRealUser()!=null){
				$pLabel = $p->getRealUser()->getUsername();
			} else if ($p->getAttachedUser() != null){
				$pLabel = $p->getAttachedUser()->getUsername();
			} else {
				$pLabel = $p->getUserLabel();
			}
			$logFile = $DEBUG_EXECUTION_LOG_FILEPATH."_".$pLabel.".txt";
			//eput($logFile);
			$ress = fopen($logFile, "a");

			fwrite($ress, "\n-----------------------------------------------------");
			fwrite($ress, "\n-----------------------------------------------------");
			fwrite($ress, "\n"."Start: ".date("H:i:s",intval($this->startTime)));
			fwrite($ress, "\n-----------------------------------------------------");
			fwrite($ress, "\n-----------------------------------------------------");
			foreach($this->messages as $seq=>$message){
				$mess = $message["attachedClass"]." : ".$message["message"];
				fwrite($ress, "\n".$mess);
				//add nb of space to make the title always at the same place
				//detect last line of message, and count last line:
				$last = strrchr("\n", $mess);
				$l = strlen($mess);
				if(($l-$last) > 100){
					fwrite($ress, "\n");
					fwrite($ress, str_repeat(" ",100));
				} else {
					fwrite($ress, str_repeat(" ",100-($l-$last)));
				}
				fwrite($ress, "|| ".$message["type"]."(+".number_format($message["diffTime"],4).", ".number_format($message["diffTimeRel"],4).($message["diffTimeOp"]!==null ? ", ".number_format($message["diffTimeOp"],4) : '').")");
			}
			//footer
			fwrite($ress, "\n\n".str_repeat(str_repeat("#", 120)."\n", 10)."\n\n\n");
			fclose($ress);
		}
	}
	public function flushMessages()
	{
		$this->executionSink()->log("start flushMessages");
		if(isset($this->messages))
		{
			//in the case of an upload of a file
			//the request is made from an iframe, that means we cannot refer to external HTML with JS
			if($this->isDisplayEnabled() && $_FILES == null){
				//create header of the system console to allow filtering
?>
$("#systemConsole").css('display','block');
$("#systemConsole .header .close").remove();
$("#systemConsole .header").removeClass("header").addClass("oldHeader");
$("#systemConsole .messages").css('display','none').removeClass("messages").addClass("oldMessages");
$("#systemConsole").prepend("<div class='header'><span class='headerItem start' >Start: <?=date("H:i:s",intval($this->startTime))." ".number_format($this->getMicroTime()-$this->startTime, 4);?></span><span class='headerItem all' >+</span></div><div class='messages' <? if (!TEST_ON) echo " style='display:none;' "; ?> ></div>");
$("#systemConsole .header span.start").click(function(){
	$(".message, .messageData", $(this).parent().next()).css('display','block');
	$(this).parent().next().toggle();
});
$("#systemConsole .header span.all").click(function(){
	$(".message, .messageData", $(this).parent().next()).css('display','block');
});
<?
				$typeNb = 0;
				foreach($this->typeList as $type=>$nb){
?>
$("#systemConsole .header").append("<span class='headerItem type<?=$typeNb;?>' ><?=$type;?> (<?=$nb;?>)</span>");
$("#systemConsole .header span.type<?=$typeNb;?>").click(function(){
	$("#systemConsole .messages").css('display','block');
	$(".message, .messageData", $(this).parent().next()).css('display','none');
	$(".type<?=$typeNb;?>", $(this).parent().next()).css('display','block');
});
<?
					$this->typeList[$type] = "type".$typeNb; //on remplace le nb d'élément par le typeNb afin de pouvoir facilement faire le lien dans la suite
					$typeNb ++;
				}
?>
$("#systemConsole .header").prepend("<span class='headerItem close' >x</span>");
$("#systemConsole .header span.close").click(function(){
	$("#systemConsole").css('display','none');
});
<?

				$len = count($this->messages);
				$i = 0;
				foreach($this->messages as $seq=>$message){
					$i++;
					//it is very important to replace all the \n by html code and then eliminate completly every line break.
					//because other wise, the JS will not be able to evaluate correctly the code
					$message["message"] = str_replace("START OPERATION", "<font style='color:#0073FF'>START OPERATION</font>", $message["message"]);
					$message["message"] = str_replace("END OPERATION", "<font style='color:#0073FF'>END OPERATION</font>", $message["message"]);
					$message["message"] = str_replace("\n", "", str_replace("\r", "", nl2br(str_replace('"', '\\"',str_replace("\\", "/", $message["message"])))));
					$message["backTrace"] = str_replace("\n", "", str_replace("\r", "", nl2br(str_replace('"', '\\"',str_replace("\\", "/", $message["backTrace"])))));
					//$message["message"] = str_replace('"', '\\"', $message["message"]);
?>
$("#systemConsole .messages").append("<div><div class='message <?=$this->typeList[$message["type"]];?> <?=$message["attachedClass"];?>' ><?=$message["message"];?><?=($message["diffTimeOp"]!==null ? ($message["diffTimeOp"] > 0.1 ? ", <font style='color:#f00;font-weight:bold;'>".number_format($message["diffTimeOp"],4).'</font>' : ($message["diffTimeOp"] > 0.01 ? ", <font style='color:#FF7500;font-weight:bold;'>".number_format($message["diffTimeOp"],4).'</font>' : ", <font style='color:#0073FF;font-weight:bold;'>".number_format($message["diffTimeOp"],4)).'</font>') : '');?></div><div class='messageData <?=$this->typeList[$message["type"]];?> <?=$message["attachedClass"];?>'><?=$message["type"];?> <?=$message["backTrace"];?> (+<?=number_format($message["diffTime"],4);?>, <?=($message["diffTimeRel"] > 0.001 ? "<font style='color:#AE00FF;font-weight:bold;'>".number_format($message["diffTimeRel"],4).'</font>' : number_format($message["diffTimeRel"],4));?>):</div></div>");
<?
				}
			}

			$this->logMessageInFile();

			unset($this->messages);
		}

		//end operation flushMessages
		$microTime = $this->getMicroTime();
		$diffTime = $microTime - $this->startTime;
		$message = "end flushMessages";
		$attachedClass = "SystemConsoleServiceWebImpl";
		//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++."end flushMessages"] = microtime(true);
?>
$("#systemConsole .messages").append("<div><div class='message' ><?=$message;?></div><div class='messageData'><?=$attachedClass;?> (+<?=number_format($diffTime,4);?>)</div></div>");
<?

	}

//	public function getJSCode(){
//		echo $this->JSCode;
//	}
	public function flushJSCode()
	{
		$this->executionSink()->publishStartOperation("flushJSCode");
		if(isset($this->JSCode))
		{
//			echo preg_replace('/([^\']?.*\/\/.*)/', '', implode("", $this->JSCode));
			echo implode("", $this->JSCode);
			unset($this->JSCode);
		}
		$this->executionSink()->publishEndOperation("flushJSCode");
	}
	public function isJsCodePending(){
		if($this->JSCode==null) return false;
		return true;
	}
	public function getJsCodeIterator(){
		if(!isset($this->JSCode)) return array();
		return $this->JSCode;
	}
	public function flushJSCodeForRequest($requestId)
	{
		//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++."start SystemConsoleService flushJSCodeForRequest $requestId"] = microtime(true);
		$this->executionSink()->publishStartOperation("flushJSCodeForRequest");
		if(isset($this->JSCode) && isset($this->JSCode[$requestId]))
		{
//			echo preg_replace('/([^\']?.*\/\/.*)/', '', $this->JSCode[$requestId]);
			echo $this->JSCode[$requestId];
			unset($this->JSCode[$requestId]);
		}
		//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++."end SystemConsoleService flushJSCodeForRequest $requestId"] = microtime(true);
		$this->executionSink()->publishEndOperation("flushJSCodeForRequest");
	}
	
	public function isJsNotifPending(){
		if($this->JSNotif==null) return false;
		return true;
	}
	public function getJsNotifIterator(){
		if(!isset($this->JSNotif)) return array();
		return $this->JSNotif;
	}
	public function flushJSNotif() {
		$this->executionSink()->publishStartOperation("flushJSNotif");
		if(isset($this->JSNotif))
		{
			$exec = ServiceProvider::getExecutionService();			
			// if updating, then creates one request-answer per JS notif
			if($exec->getIsUpdating()) {					
				$transport = (object)array('notification'=>null);
				foreach($this->JSNotif as $jsNotif) {
					echo ExecutionServiceImpl :: answerRequestSeparator;
					echo 'Wigii_HelpService';
					echo ExecutionServiceImpl :: answerParamSeparator; 
					echo 'json';
					echo ExecutionServiceImpl :: answerParamSeparator;
					$transport->notification = $jsNotif;
					echo json_encode($transport);
				}
			}
			// else returns an array with all JS notif
			else {
				$transport = array();
				foreach($this->JSNotif as $jsNotif) {
					$transport[] = (object)array('notification'=>$jsNotif);
				}
				echo json_encode($transport);
			}
			unset($this->JSNotif);
		}
		$this->executionSink()->publishEndOperation("flushJSNotif");
	}

}


