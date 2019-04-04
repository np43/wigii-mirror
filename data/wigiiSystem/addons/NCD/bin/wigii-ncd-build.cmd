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
REM @copyright  Copyright (c) 2016-2017  Wigii.org
REM @author     <http://www.wigii.org/system/libs>      Wigii.org 
REM @link       <http://www.wigii-system.net>     		<https://github.com/wigii/wigii>   Source Code
REM @license    <http://www.gnu.org/licenses/>     		GNU General Public License
REM
 
REM
REM Wigii Natural Code Development (NCD) libraries build script
REM Created by Camille Weber (camille@wigii.org), 27.10.2017
REM
@echo on

del ..\wigii-ncd-core.js
copy NUL ..\wigii-ncd-core.js

type "..\wigii-ncd.js" >> ..\wigii-ncd-core.js
echo. >> ..\wigii-ncd-core.js
type "..\wigii-etp.js" >> ..\wigii-ncd-core.js
echo. >> ..\wigii-ncd-core.js
type "..\wigii-etp-fx.js" >> ..\wigii-ncd-core.js
echo. >> ..\wigii-ncd-core.js
type "..\wigii-ncd-externals.js" >> ..\wigii-ncd-core.js
echo. >> ..\wigii-ncd-core.js
type "..\wigii-ncd-stdlib.js" >> ..\wigii-ncd-core.js

java -jar yuicompressor-2.4.7.jar -o ..\wigii-ncd-core.min.js ..\wigii-ncd-core.js

del ..\wigii-ncd-core.css
copy NUL ..\wigii-ncd-core.css

type "..\wigii-ncd.css" >> ..\wigii-ncd-core.css
echo. >> ..\wigii-ncd-core.css
type "..\wigii-ncd-externals.css" >> ..\wigii-ncd-core.css
echo. >> ..\wigii-ncd-core.css
type "..\wigii-ncd-stdlib.css" >> ..\wigii-ncd-core.css

java -jar yuicompressor-2.4.7.jar -o ..\wigii-ncd-core.min.css ..\wigii-ncd-core.css

rem copy ..\wigii-ncd-core.js C:\Users\Camille\Documents\dev\php-src\Wigii_git\www\assets\js
rem copy ..\wigii-ncd-stdlib.css C:\Users\Camille\Documents\dev\php-src\Wigii_git\www\assets\css

pause