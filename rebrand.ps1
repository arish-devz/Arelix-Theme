$root = "C:\Users\SINEKITHA V\Desktop\Hyper Theme\temp_rebrand"

# Use an array of objects to strictly define order and case sensitivity settings
$replacements = @(
    [PSCustomObject]@{ Find = "HyperV1"; Replace = "Arelix" },
    [PSCustomObject]@{ Find = "Hyper"; Replace = "Arelix" },
    [PSCustomObject]@{ Find = "hyperv1"; Replace = "arelix" },
    [PSCustomObject]@{ Find = "hyper-primary"; Replace = "arelix-primary" },
    [PSCustomObject]@{ Find = "hyper_version"; Replace = "arelix_version" },
    [PSCustomObject]@{ Find = "hyper"; Replace = "arelix" }
)

# 1. Content Replacement
Get-ChildItem -Path $root -Recurse -File | ForEach-Object {
    $file = $_
    $content = Get-Content -Path $file.FullName -Raw
    $originalContent = $content
    
    foreach ($item in $replacements) {
        # Check casing manually for simple replacements
        # For 'Hyper' we want case sensitive match usually, but here 'Find' matches the case we typed above
        # The -replace operator is case INsensitive by default. -creplace is case sensitive.
        if ($item.Find -eq "Hyper" -or $item.Find -eq "HyperV1") {
            $content = $content -creplace $item.Find, $item.Replace
        }
        else {
            # For lowercase 'hyper', just replace it
            $content = $content -replace $item.Find, $item.Replace
        }
    }
    
    if ($content -ne $originalContent) {
        Set-Content -Path $file.FullName -Value $content -NoNewline
        Write-Host "Updated content: $($file.Name)"
    }
}

# 2. File Renaming
Get-ChildItem -Path $root -Recurse | Sort-Object -Property FullName -Descending | ForEach-Object {
    $item = $_
    $newName = $item.Name
    
    foreach ($entry in $replacements) {
        # Filename replacement - usually case insensitive on Windows
        $newName = $newName -replace $entry.Find, $entry.Replace
    }
    
    if ($newName -ne $item.Name) {
        $newPath = Join-Path -Path $item.DirectoryName -ChildPath $newName
        Rename-Item -Path $item.FullName -NewName $newName
        Write-Host "Renamed: $($item.Name) -> $newName"
    }
}
