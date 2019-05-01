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
REM Setups Wigii environment variables
REM Created by CWE on 15.04.2019
REM 
set WIGII_LEGALENTITY=YourOrganisation
set WIGII_DEFAULT_NEWCLIENT_PACK=WigiiOrg\company\newClient
set WIGII_ENV=C:\Users\xxxx\Documents\dev\php-src\Wigii_git
set WIGII_DB_USER=wgi
set WIGII_DB_PWD=mywgi-pass
set WIGII_REDIRECT_EMAIL=xxx@xxx.xxx
set WIGII_PHP_ENV=C:\wamp\bin\php\php7.3.1
set WIGII_MYSQL_ENV=C:\wamp\bin\mysql\mysql5.7.24
set WIGII_MYSQL_ROOTPWD=xxxx

set WIGII_ADMIN_HOME=%~dp0
IF %WIGII_ADMIN_HOME:~-1%==\ SET WIGII_ADMIN_HOME=%WIGII_ADMIN_HOME:~0,-1%
set WIGII_CLI=%WIGII_ENV%\data\wigiiSystem\core\_cliImplExecutor
for /f "tokens=1 delims=-" %%a in ("%~n0") do (set WIGII_DEVELOPER=%%a)
rem loads server passwords
if exist %WIGII_ADMIN_HOME%\%WIGII_LEGALENTITY%-passwords.bat (call %WIGII_ADMIN_HOME%\%WIGII_LEGALENTITY%-passwords.bat)
rem sets new client pack
if not "%WIGII_DEFAULT_NEWCLIENT_PACK%"=="" set WIGII_NEWCLIENT_PACK=%WIGII_ADMIN_HOME%\configPack\%WIGII_DEFAULT_NEWCLIENT_PACK%