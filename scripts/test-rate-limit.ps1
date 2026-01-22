param(
    [string]$Url = "http://localhost:8080/github",
    [int]$Max = 200,
    [int]$DelayMs = 100
)

$i = 1
while ($i -le $Max) {
    try {
        $statusCode = $null
        $response = Invoke-WebRequest -Uri $Url -Method Get -MaximumRedirection 0 -UseBasicParsing -StatusCodeVariable statusCode -ErrorAction Stop
        Write-Host "$i) Status $statusCode"
        if ($statusCode -eq 429) { break }
    } catch {
        $status = $null
        if ($_.Exception.Response) {
            $status = $_.Exception.Response.StatusCode.value__
        }
        Write-Host "$i) Status $status"
        if ($status -eq 429) { break }
    }

    Start-Sleep -Milliseconds $DelayMs
    $i++
}
