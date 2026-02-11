$errors = $null
[void][System.Management.Automation.Language.Parser]::ParseInput((Get-Content '.\crmlite_apply_patch.ps1' -Raw), [ref]$null, [ref]$errors)
if ($errors -and $errors.Count -gt 0) { $errors | ForEach-Object { $_.ToString() }; exit 1 } else { Write-Output 'No syntax errors' }