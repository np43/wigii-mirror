::update the directories bellow and remove the exit command
::remove this line when you have corrected the directories
exit 

::request for revision number
SET /p V=Revision number:

::change the directory here
cd C:\Users\...\Documents\Workspace\Wigii\trunk\www\assets\css\src

del ..\wigii_%v%.css
copy NUL ..\wigii_%v%.css

::for each css files in directory concatenate and minify
for %%i in (*.css) do type "%%i" >> ..\wigii_%v%.css

java -jar yuicompressor-2.4.8.jar -o ..\wigii_%v%.css ..\wigii_%v%.css

pause