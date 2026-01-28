$root = "C:\Users\SINEKITHA V\Desktop\Hyper Theme\temp_rebrand"

$replacements = @(
    [PSCustomObject]@{ Find = "RolexDev"; Replace = "Arelix" },
    [PSCustomObject]@{ Find = "rolexdev"; Replace = "arelix" },
    [PSCustomObject]@{ Find = "HyperV1"; Replace = "Arelix" },
    [PSCustomObject]@{ Find = "Hyper"; Replace = "Arelix" },
    [PSCustomObject]@{ Find = "hyperv1"; Replace = "arelix" },
    [PSCustomObject]@{ Find = "hyper"; Replace = "arelix" }
)

# 1. Content Replacement
Write-Host "Replacing content..."
Get-ChildItem -Path $root -Recurse -File | ForEach-Object {
    $file = $_
    $content = Get-Content -Path $file.FullName -Raw
    $originalContent = $content
    
    foreach ($item in $replacements) {
        if ($item.Find -ceq "RolexDev" -or $item.Find -ceq "HyperV1" -or $item.Find -ceq "Hyper") {
             $content = $content -creplace $item.Find, $item.Replace
        } else {
             $content = $content -replace $item.Find, $item.Replace
        }
    }
    
    if ($content -ne $originalContent) {
        Set-Content -Path $file.FullName -Value $content -NoNewline
    }
}

# 2. File and Directory Renaming
# Sort by length descending to rename deepest folders/files first so paths don't break
Write-Host "Renaming files and folders..."
Get-ChildItem -Path $root -Recurse | Sort-Object -Property FullName -Descending | ForEach-Object {
    $item = $_
    $newName = $item.Name
    
    foreach ($entry in $replacements) {
        $pattern = [Regex]::Escape($entry.Find)
        $newName = $newName -replace $pattern, $entry.Replace
    }
    
    if ($newName -ne $item.Name) {
        Write-Host "Renaming: $($item.FullName) -> $newName"
        try {
            Rename-Item -Path $item.FullName -NewName $newName -ErrorAction Stop
        } catch {
            Write-Warning "Failed to rename $($item.FullName): $_"
        }
    }
}
Write-Host "Done."
