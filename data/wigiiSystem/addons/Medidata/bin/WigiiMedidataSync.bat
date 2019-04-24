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
REM Wigii interface with Medidata (https://www.medidata.ch/)
REM This script synchronises the Mediport Communicator folders with Wigii Medidata folder on server using WinScp
REM Created by CWE on 05.03.2019
REM 

set MPCDATA=C:\mpcdata
set WIGII_SERVER=ftp.server.com
set WIGII_CERTIFICATE="xx:xx:xx:xx:xx:xx:xx:xx:xx:xx:xx:xx:xx:xx:xx:xx:xx:xx:xx:xx"
set WIGII_USER=xxxx
set WIGII_PWD=xxxx
set WIGII_MEDIDATA=/Medidata

set RETURNVALUE=0
echo Changes code page to UTF-8
chcp 65001
rem checks Mediport Communicator Data folder
if not exist %MPCDATA% (echo Wigii ERREUR: Medidata n'est pas installé dans "%MPCDATA%" & set RETURNVALUE=10201 & goto end)
rem checks installation of WinSCP
if exist "%ProgramFiles%\WinSCP\WinSCP.exe" (set WINSCP="%ProgramFiles%\WinSCP\WinSCP.exe") else (
if exist "%ProgramFiles(x86)%\WinSCP\WinSCP.exe" (set WINSCP="%ProgramFiles(x86)%\WinSCP\WinSCP.exe") else (
	echo Wigii ERREUR: WinScp n'est pas installé & set RETURNVALUE=10202 & goto end
))
rem creates Wigii folder in MPCDATA and copies current script inside if needed
if not exist %MPCDATA%\wigii mkdir %MPCDATA%\wigii
if not exist %MPCDATA%\wigii\sending mkdir %MPCDATA%\wigii\sending
if not exist %MPCDATA%\wigii\sending\control mkdir %MPCDATA%\wigii\sending\control
if not exist %MPCDATA%\wigii\%~nx0 (
	echo Installation du script WigiiMedidataSync dans le dossier Medidata %MPCDATA%\wigii
	copy /y %~f0 %MPCDATA%\wigii
)
rem prepares Wigii server connexion string
set WIGII_CONNEXION=open ftps://%WIGII_USER%:%WIGII_PWD%@%WIGII_SERVER% -explicit -certificate="%WIGII_CERTIFICATE%"
set WINSCP_CMD=%WINSCP% /log=%MPCDATA%\wigii\winscp.log /loglevel=0 /logsize=5*2M /command

rem auto updates script with latest version
%WINSCP_CMD% ^
 "%WIGII_CONNEXION%" ^
 "lcd %MPCDATA%/wigii" ^
 "cd %WIGII_MEDIDATA%/bin" ^
 "get -neweronly -transfer=ascii %~nx0" ^
 "close" ^
 "exit"
 
rem checks if Wigii Medidata/mirror folder exists, if not creates it
%WINSCP_CMD% ^
 "%WIGII_CONNEXION%" ^
 "stat %WIGII_MEDIDATA%/mirror" ^
 "close" ^
 "exit"
if %ERRORLEVEL% equ 0 goto checkReceiveFolder
:createMirror
echo Création du dossier miroir côté Wigii
%WINSCP_CMD% ^
 "%WIGII_CONNEXION%" ^
 "mkdir %WIGII_MEDIDATA%" ^
 "close" ^
 "exit"
%WINSCP_CMD% ^
 "%WIGII_CONNEXION%" ^
 "mkdir %WIGII_MEDIDATA%/mirror" ^
 "close" ^
 "exit"
%WINSCP_CMD% ^
 "%WIGII_CONNEXION%" ^
 "stat %WIGII_MEDIDATA%/mirror" ^
 "close" ^
 "exit"
if %ERRORLEVEL% neq 0 goto winScpError

:checkReceiveFolder
rem checks if Wigii Medidata/receive folder exists, if not creates it
%WINSCP_CMD% ^
 "%WIGII_CONNEXION%" ^
 "stat %WIGII_MEDIDATA%/receive" ^
 "close" ^
 "exit"
if %ERRORLEVEL% equ 0 goto syncMirror
:createReceiveFolder
%WINSCP_CMD% ^
 "%WIGII_CONNEXION%" ^
 "mkdir %WIGII_MEDIDATA%/receive" ^
 "mkdir %WIGII_MEDIDATA%/receive/test" ^
 "close" ^
 "exit"
if %ERRORLEVEL% neq 0 goto winScpError

rem backups Mediport files to Wigii
:syncMirror
echo Sauvegarde Mediport Communicator vers Wigii
%WINSCP_CMD% ^
 "%WIGII_CONNEXION%" ^
 "synchronize remote -delete -transfer=binary -nopermissions %MPCDATA% %WIGII_MEDIDATA%/mirror" ^
 "close" ^
 "exit"
if %ERRORLEVEL% neq 0 goto winScpError

rem moves remotely all xml files from the Wigii/Medidata/send folder to the Wigii/Medidata/sending folder
rem downloads all xml files to be sent to Medidata and temporarely stores them into mpcdata/wigii/sending folder
echo Préparation du lot de fichiers à envoyer à Medidata
%WINSCP_CMD% ^
 "%WIGII_CONNEXION%" ^
 "lcd %MPCDATA%/wigii/sending" ^
 "cd %WIGII_MEDIDATA%/send" ^
 "get -delete -transfer=binary *.xml" ^
  "close" ^
 "exit"
if %ERRORLEVEL% neq 0 goto winScpError
echo Dépose des fichiers à envoyer dans Medidata
move %MPCDATA%\wigii\sending\SendControl*.xml %MPCDATA%\wigii\sending\control
move %MPCDATA%\wigii\sending\*.xml %MPCDATA%\data\send
move %MPCDATA%\wigii\sending\control\*.xml %MPCDATA%\data\send

echo Transfert des fichiers reçus de Medidata vers Wigii
%WINSCP_CMD% ^
 "%WIGII_CONNEXION%" ^
 "lcd %MPCDATA%/data/receive" ^
 "cd %WIGII_MEDIDATA%/receive" ^
 "put -delete -transfer=binary -nopermissions *.*" ^
 "close" ^
 "exit"
if not exist %MPCDATA%\data\receive\test mkdir %MPCDATA%\data\receive\test

goto end
:winScpError
set RETURNVALUE=10203
echo Erreur de communication WinScp
goto end
:end
REM clears all variables and exits with return value
set MPCDATA=
set WIGII_SERVER=
set WIGII_CERTIFICATE=
set WIGII_USER=
set WIGII_PWD=
set WIGII_MEDIDATA=
set WIGII_CONNEXION=
set WINSCP=
set WINSCP_CMD=
if %RETURNVALUE% GTR 0 (echo Wigii ERREUR: code %RETURNVALUE% & echo Pour de l'assistance, contactez help@wigii-system.com & pause)
exit /b %RETURNVALUE%