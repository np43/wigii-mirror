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
REM Refreshes an existing client development environment from production server. Configuration files and database are refreshed.
REM Created by CWE on 16.04.2019
REM 

set WIGII_CLIENT=%1
set USAGE=%0 wigii_client
set RETURNVALUE=0
echo Changes code page to UTF-8
chcp 65001

if "%WIGII_CLIENT%"=="" (echo Wigii ERROR: Wigii client is not defined. Usage: %USAGE% & set RETURNVALUE=1009 & goto end)
if "%WIGII_ADMIN_HOME%"=="" (echo Wigii ERROR: WIGII_ADMIN_HOME is not defined. Call %~nx0 from USER-adminConsole.bat & set RETURNVALUE=1009 & goto end)
IF %WIGII_ADMIN_HOME:~-1%==\ SET WIGII_ADMIN_HOME=%WIGII_ADMIN_HOME:~0,-1%
if "%WIGII_LEGALENTITY%"=="" (echo Wigii ERROR: WIGII_LEGALENTITY is not defined. Call %~nx0 from USER-adminConsole.bat & set RETURNVALUE=1009 & goto end)
if not exist %WIGII_ADMIN_HOME%\configs\%WIGII_LEGALENTITY%-client-host.bat (echo Wigii ERROR: %WIGII_LEGALENTITY%-client-host.bat has not been found & set RETURNVALUE=404 & goto end)
rem extracts WIGII_HOST from mapping
for /F "tokens=2 delims==" %%a in ('findstr /C:"%WIGII_LEGALENTITY%_%WIGII_CLIENT%_host" %WIGII_ADMIN_HOME%\configs\%WIGII_LEGALENTITY%-client-host.bat') do (set WIGII_HOST=%%a)
if "%WIGII_HOST%"=="" (echo Wigii ERROR: Wigii host has not been found for client %WIGII_CLIENT% in %WIGII_LEGALENTITY%-client-host.bat & set RETURNVALUE=1009 & goto end)
rem call wigii-refreshDevEnv for selected host
call %WIGII_ADMIN_HOME%\servers\%WIGII_HOST%-refreshDevEnv.bat %WIGII_CLIENT%
SET RETURNVALUE=%ERRORLEVEL%
goto end

:end
REM clears all variables and exits with return value
set WIGII_HOST=
exit /b %RETURNVALUE%