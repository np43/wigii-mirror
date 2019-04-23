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
REM This script prepares a go live package based on the content of SVN exports
REM Created by CWE on 17.04.2019
REM 

set RETURNVALUE=0
SET PREVIOUS_PATH=%CD%
cd %~dp0
echo Changes code page to UTF-8
chcp 65001

if "%WIGII_HOST%"=="" (echo Wigii ERROR: WIGII_HOST is not defined. & set RETURNVALUE=1009 & goto end)
if not exist svn-export (echo Wigii ERROR: folder %CD%\svn-export does not exist. & set RETURNVALUE=1009 & goto end)

rem checks installation of 7-Zip
if exist "%ProgramFiles%\7-Zip\7z.exe" (set P7ZIP="%ProgramFiles%\7-Zip\7z.exe") else (
if exist "%ProgramFiles(x86)%\7-Zip\7z.exe" (set P7ZIP="%ProgramFiles(x86)%\7-Zip\7z.exe") else (
	echo Wigii ERROR: 7-Zip is not installed & set RETURNVALUE=404 & goto end
))

for /f "tokens=1-3 delims=. " %%a in ('date /T') do (set TIMESTAMP=%%c%%b%%a)

:prepareProdPackage
echo Setups %WIGII_HOST%-prod folder structure
set WIGII_TARGET=%~dp0%WIGII_HOST%-prod
rmdir %WIGII_TARGET% /s /q
mkdir %WIGII_TARGET%
call %~dp0%WIGII_HOST%-setup.bat %WIGII_TARGET%
if "%WIGII_TARGET_WEB%"=="" (echo Wigii ERROR: WIGII_TARGET_WEB is not defined. & set RETURNVALUE=1009 & goto end)
if "%WIGII_TARGET_ENV%"=="" (echo Wigii ERROR: WIGII_TARGET_ENV is not defined. & set RETURNVALUE=1009 & goto end)
mkdir %WIGII_TARGET_WEB%
mkdir %WIGII_TARGET_ENV%

:prepareWigiiPackage
rem prepares a wigii package if svn export contains a trunk folder
if not exist svn-export\trunk goto endPrepareWigiiPackage
echo Prepares wigii package based on trunk svn export
rem copies all folders to wigii env target
xcopy svn-export\trunk\* %WIGII_TARGET_ENV% /e /s
rem moves www folder content to wigii web target
if exist %WIGII_TARGET_ENV%\www (
	rem except if web target and env target points to same folder.
	if "%WIGII_TARGET_ENV%"=="%WIGII_TARGET_WEB%" goto endPrepareWigiiPackage
	xcopy %WIGII_TARGET_ENV%\www\* %WIGII_TARGET_WEB% /e /s
	rmdir %WIGII_TARGET_ENV%\www /s /q
)
:endPrepareWigiiPackage

:zipSVNExport
echo Backups svn export into wigii_%TIMESTAMP%-svn.zip
%P7ZIP% a -tzip wigii_%TIMESTAMP%-svn.zip .\svn-export\*
del /q svn-export\* 
FOR /D %%p IN ("svn-export\*") DO rmdir "%%p" /s /q

echo _________________________________________________
echo Done.
echo Package %WIGII_TARGET% is ready for deployement.
echo Please check before go live.
goto end
:end
REM clears all variables and exits with return value
cd %PREVIOUS_PATH%
exit /b %RETURNVALUE%