SET /p V=Revision number: 
cd D:\lwr\Medair\Wigii\4 - Development\wigii.ch\3-Construction\trunk\www\assets\css\src

del ..\wigii_%v%.css
copy NUL ..\wigii_%v%.css

for %%i in (*.css) do type "%%i" >> ..\wigii_%v%.css


java -jar yuicompressor-2.4.6.jar -o ..\wigii_%v%.css ..\wigii_%v%.css

pause