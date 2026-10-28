@echo off
chcp 65001 > nul
copy /y "%~dp0index.html" "%USERPROFILE%\Desktop\index.html"
echo ========================================================
echo  [✓] تم نسخ ملف المنصة (index.html) إلى سطح المكتب بنجاح!
echo  ستجده الآن مباشرة على شاشة سطح المكتب (Desktop).
echo ========================================================
pause
