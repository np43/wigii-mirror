SET /p V=Revision number: 
cd C:\Users\Developer\Documents\Dev\Wigii-trunk\www\assets\css\src

del ..\wigii_%v%.css
copy NUL ..\wigii_%v%.css

for %%i in (*.css) do type "%%i" >> ..\wigii_%v%.css


java -jar yuicompressor-2.4.8.jar -o ..\wigii_%v%.css ..\wigii_%v%.css

pause