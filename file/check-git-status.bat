@echo off
REM Git Status Checker for School Management Plugin
REM Run this in your plugin directory

echo ============================================
echo    SCHOOL MANAGEMENT PLUGIN - GIT STATUS
echo ============================================
echo.

REM Change to plugin directory
cd "C:\Users\ahmed\Local Sites\ctadz-school\app\public\wp-content\plugins\school-management"

echo Current Directory:
cd
echo.

echo ============================================
echo    CURRENT BRANCH
echo ============================================
git branch
echo.

echo ============================================
echo    GIT STATUS
echo ============================================
git status
echo.

echo ============================================
echo    RECENT COMMITS (Last 10)
echo ============================================
git log --oneline -10
echo.

echo ============================================
echo    FILES TO COMMIT (v0.4.1)
echo ============================================
echo Expected files from previous session:
echo   - school-management.php
echo   - includes/class-sm-payment-sync.php
echo   - includes/class-sm-courses-page.php
echo   - includes/class-sm-enrollments-page.php
echo   - includes/sm-helpers.php
echo   - includes/sm-enqueue.php
echo   - includes/sm-loader.php
echo.

echo ============================================
echo    FILES TO COMMIT (v0.4.2)
echo ============================================
echo New files from this session:
echo   - includes/class-sm-courses-page.php (UPDATED)
echo.

echo ============================================
echo    NEXT STEPS
echo ============================================
echo 1. Review the status above
echo 2. Check what files are "modified" or "untracked"
echo 3. Follow GIT_COMMIT_GUIDE.md for commit instructions
echo.
echo ============================================

pause
