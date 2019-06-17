::update the directories bellow and remove the exit command
::remove this line when you have corrected the directories
exit 

SET WIGII_PATH=C:\Users\weber\Documents\dev\php-src\Wigii_git
SET JAVA_PATH="C:\Program Files\jdk-11.0.2\bin"

::request for revision number
SET /p V=Revision number:

::change the directory here
cd %WIGII_PATH%\www\assets\css\src

del ..\wigii_%v%.css
copy NUL ..\wigii_%v%.css

::for each css files in directory concatenate and minify
for %%i in (*.css) do type "%%i" >> ..\wigii_%v%.css

%JAVA_PATH%\java -jar yuicompressor-2.4.8.jar -o ..\wigii_%v%.css ..\wigii_%v%.css

copy ..\wigii_%v%.css ..\wigii-core.css

pause