# Sinh thư mục android/ ios/ bằng Flutter (chạy 1 lần sau khi cài Flutter SDK).
# Usage:  pwsh ./tool/bootstrap_platforms.ps1

$ErrorActionPreference = "Stop"
Set-Location $PSScriptRoot\..

if (-not (Get-Command flutter -ErrorAction SilentlyContinue)) {
    $local = Join-Path $env:LOCALAPPDATA "flutter\bin\flutter.bat"
    if (Test-Path $local) {
        $env:Path = "$(Split-Path $local);$env:Path"
    } else {
        Write-Error "Chưa có Flutter trên PATH. Cài: https://docs.flutter.dev/get-started/install/windows"
    }
}

Write-Host ">> flutter create . (platforms android, ios)"
flutter create . --project-name admin_app --org com.dprintshop --platforms=android,ios

# Cleartext HTTP cho dev LAN
$manifest = "android\app\src\main\AndroidManifest.xml"
if (Test-Path $manifest) {
    $xml = Get-Content $manifest -Raw
    if ($xml -notmatch "usesCleartextTraffic") {
        $xml = $xml -replace "<application", '<application android:usesCleartextTraffic="true"'
        Set-Content $manifest $xml -NoNewline
        Write-Host ">> Enabled usesCleartextTraffic on Android"
    }
    if ($xml -notmatch "android.permission.CAMERA") {
        $xml = Get-Content $manifest -Raw
        $xml = $xml -replace "<manifest", "<manifest"
        if ($xml -notmatch "CAMERA") {
            $xml = $xml -replace "(<manifest[^>]*>)", "`$1`n    <uses-permission android:name=`"android.permission.CAMERA`"/>"
            Set-Content $manifest $xml -NoNewline
            Write-Host ">> Added CAMERA permission"
        }
    }
}

Write-Host ">> flutter pub get"
flutter pub get

Write-Host "Done. Chạy: flutter run"
