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
REM This script generates a development client module configuration using a given config pack module
REM Created by CWE on 06.05.2019
REM 

set CONFIGPACK_OU=%1
set CONFIGPACK_NAMESPACE=%2
set CONFIGPACK_MODULE=%3
set USAGE=%0 ConfigPack_OrgUnit ConfigPack_Namespace ConfigPack_Module
set RETURNVALUE=0
echo Changes code page to UTF-8
chcp 65001

if "%CONFIGPACK_OU%"=="" (echo Wigii ERROR: Config pack organisational unit is not defined. Usage: %USAGE% & set RETURNVALUE=1009 & goto end)
if "%CONFIGPACK_NAMESPACE%"=="" (echo Wigii ERROR: Config pack namespace is not defined. Usage: %USAGE% & set RETURNVALUE=1009 & goto end)
if "%CONFIGPACK_MODULE%"=="" (echo Wigii ERROR: Config pack module is not defined. Usage: %USAGE% & set RETURNVALUE=1009 & goto end)
if "%LOCAL_CONFIGPACK%"=="" (set LOCAL_CONFIGPACK=%~dp0)
IF %LOCAL_CONFIGPACK:~-1%==\ SET LOCAL_CONFIGPACK=%LOCAL_CONFIGPACK:~0,-1%

if "%WIGII_ENV%"=="" (echo Wigii ERROR: WIGII_ENV is not defined. Call %~nx0 from USER-adminConsole.bat & set RETURNVALUE=1009 & goto end)
IF %WIGII_ENV:~-1%==\ SET WIGII_ENV=%WIGII_ENV:~0,-1%

call %WIGII_CLI%\wigii_cli.bat -shell -c NoClient useConfigPackModule configPack/%CONFIGPACK_OU%/%CONFIGPACK_NAMESPACE%/%CONFIGPACK_NAMESPACE%_%CONFIGPACK_MODULE%_config.xml > %LOCAL_CONFIGPACK%\client_%CONFIGPACK_MODULE%_config.xml
if %ERRORLEVEL% neq 0 (echo Wigii ERROR: code %ERRORLEVEL% & set RETURNVALUE=%ERRORLEVEL% & goto end)
move %LOCAL_CONFIGPACK%\client_%CONFIGPACK_MODULE%_config.xml %WIGII_ENV%\data\wigiiSystem\configPack\%CONFIGPACK_OU%\%CONFIGPACK_NAMESPACE%\newClient\configs\client_%CONFIGPACK_MODULE%_config.xml

:end
REM clears all variables and exits with return value
set CONFIGPACK_OU=
set CONFIGPACK_NAMESPACE=
set CONFIGPACK_MODULE=
set LOCAL_CONFIGPACK=
SET WIGII_FX=
exit /b %RETURNVALUE%