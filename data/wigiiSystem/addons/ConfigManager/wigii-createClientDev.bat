@echo off
REM
REM This file is part of Wigii (R) software.
REM Wigii is developed to inspire humanity. To Humankind we offer Gracefulness, Righteousness and Goodness.
REM 
REM Wigii is free software: you can redistribute it and/or modify it 
REM under the terms of the GNU General Public License as published by
REM the Free Software Foundation, either version 3 of the License, 
REM or (at your option) any later version.
REM 
REM Wigii is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; 
REM without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
REM See the GNU General Public License for more details.
REM
REM A copy of the GNU General Public License is available in the Readme folder of the source code.  
REM If not, see <http://www.gnu.org/licenses/>.
REM
REM @copyright  Copyright (c) 2019  Wigii.org
REM @author     <http://www.wigii.org/system>      Wigii.org 
REM @link       <http://www.wigii-system.net>      <https://github.com/wigii/wigii>   Source Code
REM @license    <http://www.gnu.org/licenses/>     GNU General Public License
REM

REM
REM Creates a new client in dev environment
REM Created by CWE on 16.04.2019
REM
set RETURNVALUE=0
echo Changes code page to UTF-8
chcp 65001
if "%WIGII_CLI%"=="" (echo Wigii ERROR: Wigii dev CLI is not defined. Call %~nx0 from USER-adminConsole.bat & set RETURNVALUE=1009 & goto end)
set WIGII_ENV_BACKUP=%WIGII_ENV%

:chooseNewClientModules
set WIGII_NEWCLIENT_MODULES=Activities;CMS;Catalog;CatalogOrders;Contacts;CustomerOrders;Dimensions;Encashments;Equipments;Espace;Events;Filemanager;Journal;LegalEntities;Plan;Portal;Projects;Scripts;Subscriptions;Tasks;TimeAllocation
echo Client %1 will be activated with the modules :
echo %WIGII_NEWCLIENT_MODULES%
echo.
echo Press enter if OK with current proposition or select a subset of modules
set /P WIGII_CHOSEN_MODULES=" "
if not "%WIGII_CHOSEN_MODULES%"=="" set WIGII_NEWCLIENT_MODULES=%WIGII_CHOSEN_MODULES%

:createClientDev
call %WIGII_CLI%\wigii_createClient.bat %*
SET RETURNVALUE=%ERRORLEVEL%
rem if client has been created using a config pack, then removes unwanted client_*_config.xml files from client config folder
rem new namespace are created by reading directly from config pack new client folder.
if "%WIGII_DEFAULT_NEWCLIENT_PACK%"=="" goto end
del /Q %WIGII_ENV_BACKUP%\data\wigiiSystem\configs\%1\client_*_config.xml > nul
del /Q %WIGII_ENV_BACKUP%\data\wigiiSystem\configs\%1\client_*_config_g.xml > nul
goto end
:end
REM clears all variables and exits with return value
exit /b %RETURNVALUE%