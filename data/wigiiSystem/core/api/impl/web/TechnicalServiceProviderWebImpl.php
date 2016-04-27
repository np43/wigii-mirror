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

/* TechnicalServiceProvider implementation which integrates with wigii web site
 * Created by CWE on 8 juin 09
 */
class TechnicalServiceProviderWebImpl extends TechnicalServiceProvider
{
	private $enableExecutionSink;
	private $enableDebugLogger;
	private $enableGlobalStatistic;
	private $enableElementStatistic;
	private $enableFileStatistic;

	/**
	 * Creates new instance and registers itself in API.
	 */
	public static function start($enableExecutionSink=false, $enableDebugLogger=false, $enableGlobalStatistic=true, $enableElementStatistic = true, $enableFileStatistic= true)
	{
		$instance = new TechnicalServiceProviderWebImpl();
		parent::registerSingleInstance($instance);
		$instance->setEnableExecutionSink($enableExecutionSink);
		$instance->setEnableDebugLogger($enableDebugLogger);
		$instance->setEnableGlobalStatistic($enableGlobalStatistic);
		$instance->setEnableElementStatistic($enableElementStatistic);
		$instance->setEnableFileStatistic($enableFileStatistic);
	}

	protected function getEnableExecutionSink()
	{
		return $this->enableExecutionSink;
	}
	protected function setEnableExecutionSink($enableExecutionSink)
	{
		$this->enableExecutionSink = $enableExecutionSink;
	}
	protected function getEnableDebugLogger()
	{
		return $this->enableDebugLogger;
	}
	protected function setEnableDebugLogger($enableDebugLogger)
	{
		$this->enableDebugLogger = $enableDebugLogger;
	}
	protected function getEnableGlobalStatistic()
	{
		return $this->enableGlobalStatistic;
	}
	protected function setEnableGlobalStatistic($enableGlobalStatistic)
	{
		$this->enableGlobalStatistic = $enableGlobalStatistic;
	}
	protected function getEnableElementStatistic()
	{
		return $this->enableElementStatistic;
	}
	protected function setEnableElementStatistic($enableElementStatistic)
	{
		$this->enableElementStatistic = $enableElementStatistic;
	}
	protected function getEnableFileStatistic()
	{
		return $this->enableFileStatistic;
	}
	protected function setEnableFileStatistic($enableFileStatistic)
	{
		$this->enableFileStatistic = $enableFileStatistic;
	}


	protected function createExceptionSinkInstance()
	{
		$exps = new ExceptionSinkWebImpl();
		$exps->setSystemConsoleEnabled($this->getEnableExecutionSink());
		return $exps;
	}
	protected function createExecutionSinkInstance($typeName)
	{
		$exs = new ExecutionSinkWebImpl($typeName);
		$exs->setEnabled($this->getEnableExecutionSink());
		return $exs;
	}
	protected function createDebugLoggerInstance($typeName)
	{
		$dbl = new DebugLoggerWebImpl($typeName);
		$dbl->setEnabled($this->getEnableDebugLogger());
		return $dbl;
	}

	private $notificationService;
	public static function getNotificationService()
	{
		return parent::getInstance()->getNotificationServiceInstance();
	}
	/**
	 * default singleton
	 */
	protected function getNotificationServiceInstance()
	{
		if(!isset($this->notificationService))
		{
			$this->notificationService = $this->createNotificationServiceInstance();
			// sets root principal
			$this->notificationService->setRootPrincipal($this->getRootPrincipal());
		}
		return $this->notificationService;
	}
	protected function createNotificationServiceInstance()
	{
		return new NotificationService();
	}

	protected function createEmailServiceInstance()
	{
		return new EmailServiceWebImpl();
	}
	protected function createGlobalStatisticServiceInstance()
	{
		$r = new GlobalStatisticService();
		$r->setEnabled($this->getEnableGlobalStatistic());
		return $r;
	}
	protected function createElementStatisticServiceInstance()
	{
		$r = new ElementStatisticService();
		$r->setEnabled($this->getEnableElementStatistic());
		return $r;
	}
	protected function createFileStatisticServiceInstance()
	{
		$r = new FileStatisticService();
		$r->setEnabled($this->getEnableFileStatistic());
		return $r;
	}
	protected function createEventSubscriberServiceInstance()
	{
		return new EventSubscriberServiceWebImpl();
	}
}



