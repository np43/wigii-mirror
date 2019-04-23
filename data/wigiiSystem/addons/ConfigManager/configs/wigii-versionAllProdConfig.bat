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
REM This script downloads Wigii clients config folder from all wigii servers and commits the files to svn.
REM Created by CWE on 16.04.2019
REM 

set RETURNVALUE=0
SET PREVIOUS_PATH=%CD%
cd %~dp0
echo Changes code page to UTF-8
chcp 65001

if "%WIGII_LEGALENTITY%"=="" (echo Wigii ERROR: WIGII_LEGALENTITY is not defined. Call %~nx0 from USER-adminConsole.bat & set RETURNVALUE=1009 & goto end)
if not exist %WIGII_LEGALENTITY%-client-host.bat (echo Wigii ERROR: %WIGII_LEGALENTITY%-client-host.bat has not been found & set RETURNVALUE=404 & goto end)
rem loops through each pair WIGII_CLIENT = WIGII_HOST
Setlocal enableDelayedExpansion
for /F "tokens=3,5 delims=_= " %%a in ('findstr /C:"%WIGII_LEGALENTITY%_" %WIGII_LEGALENTITY%-client-host.bat') do (
	set WIGII_CLIENT=%%a
	set WIGII_HOST=%%b
	echo Versioning production configs for !WIGII_HOST! client !WIGII_CLIENT!
	call !WIGII_HOST!-versionProdConfig.bat !WIGII_CLIENT!
)
endlocal

echo Done. All production configs downloaded and stored into SVN.
goto end

:end
REM clears all variables and exits with return value
cd %PREVIOUS_PATH%
exit /b %RETURNVALUE%