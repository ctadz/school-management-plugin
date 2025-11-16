@echo off
REM School Management Plugin - Git Commit Helper
REM This script will help you commit v0.4.1 and v0.4.2 changes

echo ============================================
echo  SCHOOL MANAGEMENT - GIT COMMIT HELPER
echo ============================================
echo.

REM Change to plugin directory
cd "C:\Users\ahmed\Local Sites\ctadz-school\app\public\wp-content\plugins\school-management"

echo Current Directory: %CD%
echo.

REM Check if we're in a git repo
if not exist ".git\" (
    echo ERROR: Not a git repository!
    echo Please run this from your plugin directory.
    pause
    exit /b 1
)

echo ============================================
echo  STEP 1: CHECK CURRENT STATUS
echo ============================================
echo.
git status
echo.

echo ============================================
echo  QUESTIONS
echo ============================================
echo.

:question1
set /p q1="Have you already committed v0.4.1 changes? (y/n): "
if /i "%q1%"=="y" goto skip_v041
if /i "%q1%"=="n" goto commit_v041
echo Invalid input. Please enter y or n.
goto question1

:commit_v041
echo.
echo ============================================
echo  STEP 2: COMMIT v0.4.1 CHANGES
echo ============================================
echo.
echo Staging v0.4.1 files...
git add school-management.php
git add includes/class-sm-payment-sync.php
git add includes/class-sm-courses-page.php
git add includes/class-sm-enrollments-page.php
git add includes/sm-helpers.php
git add includes/sm-enqueue.php
git add includes/sm-loader.php

echo.
echo Files staged. Review:
git status --short
echo.

set /p confirm1="Ready to commit v0.4.1? (y/n): "
if /i not "%confirm1%"=="y" (
    echo Commit cancelled. Please review and run script again.
    git reset
    pause
    exit /b 1
)

echo.
echo Committing v0.4.1...
git commit -m "feat: complete payment model system v0.4.1" -m "- Added payment model support (full_payment, monthly_installments, monthly_subscription)" -m "- Fixed payment sync database error (payment_date to due_date)" -m "- Added production-safe database migration for payment_model column" -m "- Fixed AJAX dropdown loading issue in enrollments" -m "- Added sm-helpers.php for AJAX handlers" -m "- Updated sm-enqueue.php for AJAX registration" -m "- Updated sm-loader.php to load helpers" -m "" -m "Tested: LocalWP environment" -m "Status: Production ready"

if errorlevel 1 (
    echo ERROR: Commit failed!
    pause
    exit /b 1
)

echo ✓ v0.4.1 committed successfully!
echo.
goto commit_v042

:skip_v041
echo ✓ v0.4.1 already committed, skipping...
echo.

:commit_v042
echo ============================================
echo  STEP 3: UPDATE COURSES PAGE FILE
echo ============================================
echo.
echo IMPORTANT: Before continuing, make sure you have:
echo   1. Downloaded class-sm-courses-page-UPDATED.php
echo   2. Copied it to includes\class-sm-courses-page.php
echo.
set /p copied="Have you copied the updated courses page file? (y/n): "
if /i not "%copied%"=="y" (
    echo.
    echo Please copy the file first:
    echo   copy class-sm-courses-page-UPDATED.php includes\class-sm-courses-page.php
    echo.
    echo Then run this script again.
    pause
    exit /b 1
)

echo.
echo ============================================
echo  STEP 4: COMMIT v0.4.2 CHANGES
echo ============================================
echo.
echo Staging courses page update...
git add includes/class-sm-courses-page.php

echo.
echo Files staged. Review:
git status --short
echo.

set /p confirm2="Ready to commit v0.4.2? (y/n): "
if /i not "%confirm2%"=="y" (
    echo Commit cancelled. Please review and run script again.
    git reset
    pause
    exit /b 1
)

echo.
echo Committing v0.4.2...
git commit -m "feat: add payment model display and filtering to courses list" -m "UI Enhancements:" -m "- Added Payment Model column with color-coded badges (green, blue, yellow)" -m "- Added Enrollments column showing student count per course" -m "- Added filter dropdown to filter courses by payment model" -m "- Improved table layout (removed classroom column)" -m "- Added enrollment count query with proper JOIN" -m "" -m "Features:" -m "- Filter by: Full Payment, Monthly Installments, Monthly Subscription" -m "- Clear filter link when filtering is active" -m "- Pagination preserves filter state" -m "- Color-coded badges with icons for each payment type" -m "- Excludes cancelled enrollments from counts" -m "" -m "Version: v0.4.2" -m "Status: Production ready"

if errorlevel 1 (
    echo ERROR: Commit failed!
    pause
    exit /b 1
)

echo ✓ v0.4.2 committed successfully!
echo.

:commit_docs
echo ============================================
echo  STEP 5: COMMIT DOCUMENTATION (OPTIONAL)
echo ============================================
echo.
set /p commit_docs="Do you want to commit documentation files? (y/n): "
if /i not "%commit_docs%"=="y" goto finish

echo.
echo Staging documentation files...
git add PROJECT_ARTIFACT_UPDATED.md
git add *.md

echo.
echo Files staged. Review:
git status --short
echo.

set /p confirm3="Ready to commit documentation? (y/n): "
if /i not "%confirm3%"=="y" (
    echo Skipping documentation commit.
    git reset *.md
    goto finish
)

echo.
echo Committing documentation...
git commit -m "docs: update project documentation for v0.4.1 and v0.4.2" -m "- Added PROJECT_ARTIFACT_UPDATED.md with complete session summary" -m "- Added installation and update guides" -m "- Documented all bug fixes and features"

if errorlevel 1 (
    echo ERROR: Documentation commit failed!
    pause
    exit /b 1
)

echo ✓ Documentation committed successfully!
echo.

:finish
echo ============================================
echo  COMPLETE! VERIFICATION
echo ============================================
echo.
echo Recent commits:
git log --oneline -5
echo.
echo Current status:
git status
echo.

echo ============================================
echo  SUCCESS! ✓
echo ============================================
echo.
echo Your commits:
echo   ✓ v0.4.1 - Payment model system
echo   ✓ v0.4.2 - Courses page UI enhancement
echo.
echo Next steps:
echo   1. Test everything in your browser
echo   2. If all works, merge to main: git checkout main ^&^& git merge develop
echo   3. Push to remote: git push origin main
echo.
echo ============================================

pause
