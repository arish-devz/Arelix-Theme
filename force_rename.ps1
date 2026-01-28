$root = "C:\Users\SINEKITHA V\Desktop\Hyper Theme\temp_rebrand"
Write-Host "Starting rename in $root"

# Define replacements
$replacements = @(
    @{ Find = "HyperV1"; Replace = "Arelix" },
    @{ Find = "Hyper"; Replace = "Arelix" },
    @{ Find = "hyperv1"; Replace = "arelix" },
    @{ Find = "hyper"; Replace = "arelix" }
)

# Get all items, sort by length descending to rename deepest/longest paths first
$items = Get-ChildItem -Path $root -Recurse | Sort-Object -Property FullName -Descending

foreach ($item in $items) {
    $newName = $item.Name
    
    foreach ($entry in $replacements) {
        if ($newName -match $entry.Find) {
            $newName = $newName -replace $entry.Find, $entry.Replace
        }
    }
    
    if ($newName -ne $item.Name) {
        Write-Host "Renaming: $($item.FullName) -> $newName"
        try {
            Rename-Item -Path $item.FullName -NewName $newName -ErrorAction Stop
        } catch {
            Write-Warning "Failed to rename $($item.Name): $_"
        }
    }
}
Write-Host "Rename process completed."
