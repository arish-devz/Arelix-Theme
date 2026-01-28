$root = "C:\Users\SINEKITHA V\Desktop\Hyper Theme\temp_rebrand"

$replacements = @(
    [PSCustomObject]@{ Find = "HyperV1"; Replace = "Arelix" },
    [PSCustomObject]@{ Find = "hyperv1"; Replace = "arelix" },
    [PSCustomObject]@{ Find = "Hyper"; Replace = "Arelix" },
    [PSCustomObject]@{ Find = "hyper"; Replace = "arelix" }
)

Get-ChildItem -Path $root -Recurse | Sort-Object -Property FullName -Descending | ForEach-Object {
    $item = $_
    $newName = $item.Name
    
    foreach ($entry in $replacements) {
        # Escape regex special chars just in case, though alphanumeric is fine
        $pattern = [Regex]::Escape($entry.Find)
        $newName = $newName -replace $pattern, $entry.Replace
    }
    
    if ($newName -ne $item.Name) {
        Write-Host "Renaming: $($item.FullName) -> $newName"
        try {
            Rename-Item -Path $item.FullName -NewName $newName -ErrorAction Stop
        }
        catch {
            Write-Error "Failed to rename $($item.FullName): $_"
        }
    }
}
