[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
Write-Host "======================================================================" -ForegroundColor Cyan
Write-Host "      رفع نظام المتابعة إلى GitHub (حساب: elgendy4433)" -ForegroundColor Cyan
Write-Host "======================================================================" -ForegroundColor Cyan
Write-Host ""

$gitInstalled = Get-Command git -ErrorAction SilentlyContinue
if (-not $gitInstalled) {
    Write-Host "[X] Git غير مثبت على جهازك!" -ForegroundColor Red
    Write-Host "يرجى تثبيته من https://git-scm.com/ ثم إعادة المحاولة." -ForegroundColor Yellow
    Pause
    Exit
}

Write-Host "[1/4] تهيئة مستودع Git والربط بحساب elgendy4433..." -ForegroundColor Yellow
git init
git branch -M main
git remote remove origin 2>$null
git remote add origin https://github.com/elgendy4433/tarqeem-tafneen-system.git

Write-Host "[2/4] إضافة الملفات وحفظ التغييرات..." -ForegroundColor Yellow
git add .
git commit -m "تحديث المنصة وإزالة العناصر المحددة وتجهيز النشر النهائي"

Write-Host "[3/4] رفع الملفات إلى GitHub..." -ForegroundColor Yellow
git push -u origin main
if ($LASTEXITCODE -ne 0) {
    Write-Host "محاولة الرفع مع فرض التحديث (Force Push)..." -ForegroundColor Yellow
    git push -u origin main --force
}

if ($LASTEXITCODE -eq 0) {
    Write-Host "======================================================================" -ForegroundColor Green
    Write-Host "[✓] تم الرفع بنجاح تام إلى GitHub!" -ForegroundColor Green
    Write-Host "المستودع: https://github.com/elgendy4433/tarqeem-tafneen-system" -ForegroundColor White
    Write-Host "المنصة المباشرة: https://elgendy4433.github.io/tarqeem-tafneen-system/" -ForegroundColor White
    Write-Host "======================================================================" -ForegroundColor Green
} else {
    Write-Host "======================================================================" -ForegroundColor Yellow
    Write-Host "[!] تأكد من إنشاء المستودع على حسابك بالاسم: tarqeem-tafneen-system" -ForegroundColor Yellow
    Write-Host "الرابط: https://github.com/new" -ForegroundColor White
    Write-Host "======================================================================" -ForegroundColor Yellow
}

Pause
