@echo off
chcp 65001 > nul
echo ========================================================
echo   جاري فتح منصة الرفع السحابي الفوري (Netlify Drop)...
echo ========================================================
echo.
echo 1. سيتم الآن فتح صفحة الرفع السحابي المجاني في متصفحك: https://app.netlify.com/drop
echo 2. سيتم فتح مجلد [dist] المجهز.
echo 3. اسحب مجلد [dist] وأفلته داخل المربع في المتصفح!
echo 4. ستحصل فوراً على رابط ويب حي ودائم مجاناً (https://xxxx.netlify.app)
echo.
pause
start https://app.netlify.com/drop
explorer "%~dp0dist"
exit
