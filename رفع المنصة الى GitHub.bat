@echo off
chcp 65001 > nul
title رفع نظام المتابعة إلى GitHub
color 0B
echo ======================================================================
echo   جاري رفع نظام المتابعة إلى مستودعك: elgendy4433-cloud
echo ======================================================================
echo.

cd /d "d:\مشروع ترقيم وتفنين"
set PATH=%PATH%;C:\Program Files\Git\cmd;C:\Program Files\Git\bin;C:\Program Files\GitHub CLI;C:\Users\LapTop 13.2\AppData\Local\Programs\Git\cmd

echo [1/2] تجهيز الملفات وحفظ التعديلات...
git init
git branch -M main
git config user.name "elgendy4433-cloud" 2>nul
git config user.email "tefadoolii@gmail.com" 2>nul
git remote remove origin 2>nul
git remote add origin https://github.com/elgendy4433-cloud/tarqeem-tafneen-system.git
git add .
git commit -m "إعادة تصميم شاملة للواجهة وتطوير منصة سحابية متكاملة لمؤسستي ترقيم وتفنين" 2>nul

echo.
echo [2/2] جاري رفع الملفات إلى المستودع (Push to main)...
git push -u origin main --force
if %ERRORLEVEL% equ 0 goto success

echo.
echo محاولة إضافية عبر الربط المباشر...
git push https://github.com/elgendy4433-cloud/tarqeem-tafneen-system.git main --force
if %ERRORLEVEL% equ 0 goto success

:failed
color 0C
echo.
echo ======================================================================
echo [X] واجهنا مشكلة في الصلاحيات. إذا طلب منك تسجيل الدخول، سجّل بحسابك.
echo ======================================================================
pause
exit /b 1

:success
color 0A
echo.
echo ======================================================================
echo [✓] تم الرفع بنجاح تام إلى مستودعك على GitHub!
echo.
echo رابط المستودع:
echo https://github.com/elgendy4433-cloud/tarqeem-tafneen-system
echo.
echo رابط المنصة المباشر على الإنترنت (GitHub Pages):
echo https://elgendy4433-cloud.github.io/tarqeem-tafneen-system/
echo ======================================================================
pause
exit /b 0
