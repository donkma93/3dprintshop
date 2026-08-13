# Full API smoke test for /api/v1
# Requires: php artisan serve on 127.0.0.1:8000
$ErrorActionPreference = 'Stop'
$Base = 'http://127.0.0.1:8000/api/v1'
$Pass = 0
$Fail = 0
$Results = New-Object System.Collections.Generic.List[object]

function Assert-Result {
    param(
        [string]$Name,
        [bool]$Ok,
        [string]$Detail = ''
    )
    if ($Ok) {
        $script:Pass++
        $status = 'PASS'
    } else {
        $script:Fail++
        $status = 'FAIL'
    }
    $script:Results.Add([pscustomobject]@{ Status = $status; Name = $Name; Detail = $Detail })
    Write-Host ("[{0}] {1} {2}" -f $status, $Name, $(if ($Detail) { $Detail } else { '' }))
}

function Invoke-Api {
    param(
        [string]$Method,
        [string]$Path,
        [hashtable]$Headers = @{},
        $Body = $null,
        [string]$ContentType = 'application/json; charset=utf-8'
    )
    $uri = "$Base$Path"
    $hdr = @{ Accept = 'application/json' }
    foreach ($k in $Headers.Keys) { $hdr[$k] = $Headers[$k] }

    try {
        if ($null -ne $Body) {
            if ($Body -is [string]) {
                $raw = $Body
            } else {
                # ConvertTo-Json may emit null; strip keys with $null for cleaner payloads
                $clean = @{}
                foreach ($k in $Body.Keys) {
                    if ($null -ne $Body[$k]) { $clean[$k] = $Body[$k] }
                }
                $raw = ($clean | ConvertTo-Json -Depth 10 -Compress)
            }
            $bytes = [System.Text.Encoding]::UTF8.GetBytes($raw)
            $resp = Invoke-WebRequest -Uri $uri -Method $Method -Headers $hdr -Body $bytes -ContentType $ContentType -UseBasicParsing
        } else {
            $resp = Invoke-WebRequest -Uri $uri -Method $Method -Headers $hdr -UseBasicParsing
        }
        $json = $null
        try { $json = $resp.Content | ConvertFrom-Json } catch {}
        return @{ Status = [int]$resp.StatusCode; Json = $json; Ok = $true; Error = $null; Body = $resp.Content }
    } catch {
        $ex = $_.Exception
        $status = 0
        $json = $null
        $text = ''
        if ($ex.Response) {
            $status = [int]$ex.Response.StatusCode
            try {
                $reader = New-Object System.IO.StreamReader($ex.Response.GetResponseStream())
                $text = $reader.ReadToEnd()
                $reader.Close()
                $json = $text | ConvertFrom-Json
            } catch {}
        }
        return @{ Status = $status; Json = $json; Ok = $false; Error = $ex.Message; Body = $text }
    }
}

Write-Host "=== API FULL SMOKE @ $Base ===`n"

# ---------------------------------------------------------------------------
# 1) Unauthenticated access to ALL admin feature endpoints must be 401
# ---------------------------------------------------------------------------
$adminProtected = @(
    @{ M = 'GET'; P = '/admin/me' },
    @{ M = 'GET'; P = '/admin/dashboard' },
    @{ M = 'GET'; P = '/admin/categories' },
    @{ M = 'POST'; P = '/admin/categories' },
    @{ M = 'GET'; P = '/admin/products' },
    @{ M = 'POST'; P = '/admin/products' },
    @{ M = 'GET'; P = '/admin/materials' },
    @{ M = 'POST'; P = '/admin/materials' },
    @{ M = 'GET'; P = '/admin/material-inputs' },
    @{ M = 'POST'; P = '/admin/material-inputs' },
    @{ M = 'GET'; P = '/admin/equipment' },
    @{ M = 'POST'; P = '/admin/equipment' },
    @{ M = 'GET'; P = '/admin/banners' },
    @{ M = 'POST'; P = '/admin/banners' },
    @{ M = 'GET'; P = '/admin/posts' },
    @{ M = 'POST'; P = '/admin/posts' },
    @{ M = 'GET'; P = '/admin/pages' },
    @{ M = 'POST'; P = '/admin/pages' },
    @{ M = 'GET'; P = '/admin/settings' },
    @{ M = 'PUT'; P = '/admin/settings' },
    @{ M = 'GET'; P = '/admin/chat' },
    @{ M = 'GET'; P = '/admin/chat/notifications' },
    @{ M = 'POST'; P = '/admin/chat/notifications/read' },
    @{ M = 'GET'; P = '/admin/trash' },
    @{ M = 'DELETE'; P = '/admin/trash' },
    @{ M = 'POST'; P = '/admin/logout' },
    @{ M = 'POST'; P = '/admin/logout-all' }
)

Write-Host "--- Auth guard (no token) ---"
foreach ($ep in $adminProtected) {
    $r = Invoke-Api -Method $ep.M -Path $ep.P -Body $(if ($ep.M -in @('POST','PUT','PATCH')) { '{}' } else { $null })
    Assert-Result -Name "NO_AUTH $($ep.M) $($ep.P)" -Ok ($r.Status -eq 401) -Detail "status=$($r.Status)"
}

# Bad password
$badLogin = Invoke-Api -Method POST -Path '/admin/login' -Body @{ email = 'admin@3dshop.local'; password = 'wrong-password' }
Assert-Result -Name 'LOGIN bad password' -Ok ($badLogin.Status -in 401, 422) -Detail "status=$($badLogin.Status)"

# ---------------------------------------------------------------------------
# 2) Login
# ---------------------------------------------------------------------------
Write-Host "`n--- Login ---"
$login = Invoke-Api -Method POST -Path '/admin/login' -Body @{
    email       = 'admin@3dshop.local'
    password    = 'admin@123'
    device_name = 'api-full-smoke'
}
Assert-Result -Name 'LOGIN success' -Ok ($login.Status -eq 200 -and $login.Json.success -eq $true -and $login.Json.data.token) -Detail "status=$($login.Status)"

if (-not $login.Json.data.token) {
    Write-Host "ABORT: cannot login"
    exit 1
}

$token = [string]$login.Json.data.token
$Auth = @{ Authorization = "Bearer $token" }

# ---------------------------------------------------------------------------
# 3) Auth session endpoints
# ---------------------------------------------------------------------------
Write-Host "`n--- Session ---"
$me = Invoke-Api -Method GET -Path '/admin/me' -Headers $Auth
Assert-Result -Name 'ME' -Ok ($me.Status -eq 200 -and $me.Json.data.is_admin -eq $true) -Detail "email=$($me.Json.data.email)"

$dash = Invoke-Api -Method GET -Path '/admin/dashboard' -Headers $Auth
Assert-Result -Name 'DASHBOARD' -Ok ($dash.Status -eq 200 -and $null -ne $dash.Json.data.stats) -Detail "products=$($dash.Json.data.stats.products)"

# ---------------------------------------------------------------------------
# 4) Categories CRUD
# ---------------------------------------------------------------------------
Write-Host "`n--- Categories ---"
$catList = Invoke-Api -Method GET -Path '/admin/categories' -Headers $Auth
Assert-Result -Name 'CAT list' -Ok ($catList.Status -eq 200 -and $catList.Json.success) -Detail "status=$($catList.Status)"

$catCreate = Invoke-Api -Method POST -Path '/admin/categories' -Headers $Auth -Body @{
    name = 'API Test Category'
    description = 'smoke'
    is_active = $true
    sort_order = 99
}
Assert-Result -Name 'CAT create' -Ok ($catCreate.Status -eq 201 -and $catCreate.Json.data.id) -Detail "id=$($catCreate.Json.data.id)"
$catId = $catCreate.Json.data.id

$catShow = Invoke-Api -Method GET -Path "/admin/categories/$catId" -Headers $Auth
Assert-Result -Name 'CAT show' -Ok ($catShow.Status -eq 200 -and $catShow.Json.data.id -eq $catId)

$catUpd = Invoke-Api -Method PUT -Path "/admin/categories/$catId" -Headers $Auth -Body @{
    name = 'API Test Category Updated'
    is_active = $true
    sort_order = 98
}
Assert-Result -Name 'CAT update' -Ok ($catUpd.Status -eq 200 -and $catUpd.Json.data.name -like '*Updated*')

# ---------------------------------------------------------------------------
# 5) Products CRUD
# ---------------------------------------------------------------------------
Write-Host "`n--- Products ---"
$prodList = Invoke-Api -Method GET -Path '/admin/products?per_page=5' -Headers $Auth
Assert-Result -Name 'PROD list' -Ok ($prodList.Status -eq 200 -and $prodList.Json.success)

$prodCreate = Invoke-Api -Method POST -Path '/admin/products' -Headers $Auth -Body @{
    name = 'San pham API Smoke'
    category_id = $catId
    price = 123000
    cost_price = 50000
    stock = 3
    short_description = 'test'
    is_active = $true
    is_featured = $false
}
Assert-Result -Name 'PROD create' -Ok ($prodCreate.Status -eq 201 -and $prodCreate.Json.data.id) -Detail "id=$($prodCreate.Json.data.id)"
$prodId = $prodCreate.Json.data.id
$prodSlug = $prodCreate.Json.data.slug

$prodShow = Invoke-Api -Method GET -Path "/admin/products/$prodId" -Headers $Auth
Assert-Result -Name 'PROD show' -Ok ($prodShow.Status -eq 200 -and $prodShow.Json.data.price -eq 123000)

$prodUpd = Invoke-Api -Method PUT -Path "/admin/products/$prodId" -Headers $Auth -Body @{
    name = 'San pham API Smoke Updated'
    category_id = $catId
    price = 150000
    stock = 5
    is_active = $true
    is_featured = $true
}
Assert-Result -Name 'PROD update' -Ok ($prodUpd.Status -eq 200 -and $prodUpd.Json.data.price -eq 150000)

# ---------------------------------------------------------------------------
# 6) Materials + Material Inputs
# ---------------------------------------------------------------------------
Write-Host "`n--- Materials / Inputs ---"
$matCreate = Invoke-Api -Method POST -Path '/admin/materials' -Headers $Auth -Body @{
    name = 'Resin Smoke API'
    type = 'Resin'
    unit = 'kg'
    stock_quantity = 5
    unit_price = 400000
    min_stock = 1
    is_active = $true
}
Assert-Result -Name 'MAT create' -Ok ($matCreate.Status -eq 201 -and $matCreate.Json.data.id) -Detail "id=$($matCreate.Json.data.id)"
$matId = $matCreate.Json.data.id

$matShow = Invoke-Api -Method GET -Path "/admin/materials/$matId" -Headers $Auth
Assert-Result -Name 'MAT show' -Ok ($matShow.Status -eq 200)

$matUpd = Invoke-Api -Method PUT -Path "/admin/materials/$matId" -Headers $Auth -Body @{
    name = 'Resin Smoke API'
    type = 'Resin'
    unit = 'kg'
    stock_quantity = 5
    unit_price = 420000
    min_stock = 1
    is_active = $true
}
Assert-Result -Name 'MAT update' -Ok ($matUpd.Status -eq 200 -and $matUpd.Json.data.unit_price -eq 420000)

$matList = Invoke-Api -Method GET -Path '/admin/materials' -Headers $Auth
Assert-Result -Name 'MAT list' -Ok ($matList.Status -eq 200)

$inputCreate = Invoke-Api -Method POST -Path '/admin/material-inputs' -Headers $Auth -Body @{
    material_id = $matId
    input_date = (Get-Date -Format 'yyyy-MM-dd')
    quantity = 2
    unit_price = 410000
    supplier = 'API Smoke Supplier'
}
Assert-Result -Name 'INPUT create' -Ok ($inputCreate.Status -eq 201 -and $inputCreate.Json.data.id) -Detail "id=$($inputCreate.Json.data.id)"
$inputId = $inputCreate.Json.data.id

$matAfter = Invoke-Api -Method GET -Path "/admin/materials/$matId" -Headers $Auth
$stockOk = [double]$matAfter.Json.data.stock_quantity -ge 6.9  # 5+2
Assert-Result -Name 'INPUT stock increased' -Ok $stockOk -Detail "stock=$($matAfter.Json.data.stock_quantity)"

$inputShow = Invoke-Api -Method GET -Path "/admin/material-inputs/$inputId" -Headers $Auth
Assert-Result -Name 'INPUT show' -Ok ($inputShow.Status -eq 200)

$inputUpd = Invoke-Api -Method PUT -Path "/admin/material-inputs/$inputId" -Headers $Auth -Body @{
    material_id = $matId
    input_date = (Get-Date -Format 'yyyy-MM-dd')
    quantity = 3
    unit_price = 400000
    supplier = 'API Smoke Supplier 2'
}
Assert-Result -Name 'INPUT update' -Ok ($inputUpd.Status -eq 201 -or $inputUpd.Status -eq 200)

$inputList = Invoke-Api -Method GET -Path '/admin/material-inputs' -Headers $Auth
Assert-Result -Name 'INPUT list' -Ok ($inputList.Status -eq 200)

# ---------------------------------------------------------------------------
# 7) Equipment
# ---------------------------------------------------------------------------
Write-Host "`n--- Equipment ---"
$eqList = Invoke-Api -Method GET -Path '/admin/equipment' -Headers $Auth
Assert-Result -Name 'EQ list' -Ok ($eqList.Status -eq 200)

$eqCreate = Invoke-Api -Method POST -Path '/admin/equipment' -Headers $Auth -Body @{
    name = 'Printer Smoke API'
    type = 'FDM'
    brand = 'TestBrand'
    model = 'X1'
    status = 'active'
    purchase_price = 15000000
}
# status must match Equipment::statusOptions keys
if ($eqCreate.Status -notin 200, 201) {
    $eqCreate = Invoke-Api -Method POST -Path '/admin/equipment' -Headers $Auth -Body @{
        name = 'Printer Smoke API'
        type = 'FDM'
        brand = 'TestBrand'
        model = 'X1'
        status = 'operating'
        purchase_price = 15000000
    }
}
if ($eqCreate.Status -notin 200, 201) {
    # probe valid status from existing
    $existingStatus = $null
    if ($eqList.Json.data -and $eqList.Json.data.Count -gt 0) {
        $existingStatus = $eqList.Json.data[0].status
    }
    if (-not $existingStatus) { $existingStatus = 'ok' }
    $eqCreate = Invoke-Api -Method POST -Path '/admin/equipment' -Headers $Auth -Body @{
        name = 'Printer Smoke API'
        type = 'FDM'
        brand = 'TestBrand'
        model = 'X1'
        status = $existingStatus
        purchase_price = 15000000
    }
}
Assert-Result -Name 'EQ create' -Ok ($eqCreate.Status -in 200, 201 -and $eqCreate.Json.data.id) -Detail "status=$($eqCreate.Status) id=$($eqCreate.Json.data.id) msg=$($eqCreate.Json.message)"
$eqId = $eqCreate.Json.data.id

if ($eqId) {
    $eqShow = Invoke-Api -Method GET -Path "/admin/equipment/$eqId" -Headers $Auth
    Assert-Result -Name 'EQ show' -Ok ($eqShow.Status -eq 200)

    $eqUpd = Invoke-Api -Method PUT -Path "/admin/equipment/$eqId" -Headers $Auth -Body @{
        name = 'Printer Smoke API Updated'
        type = 'FDM'
        brand = 'TestBrand'
        model = 'X2'
        status = $eqCreate.Json.data.status
        purchase_price = 16000000
    }
    Assert-Result -Name 'EQ update' -Ok ($eqUpd.Status -eq 200)
}

# ---------------------------------------------------------------------------
# 8) Banners
# ---------------------------------------------------------------------------
Write-Host "`n--- Banners ---"
$banList = Invoke-Api -Method GET -Path '/admin/banners' -Headers $Auth
Assert-Result -Name 'BAN list' -Ok ($banList.Status -eq 200)

$pos = 'home_slider'
if ($banList.Json.data -and $banList.Json.data.Count -gt 0) {
    $pos = $banList.Json.data[0].position
}
$banCreate = Invoke-Api -Method POST -Path '/admin/banners' -Headers $Auth -Body @{
    title = 'Banner Smoke API'
    subtitle = 'test'
    position = $pos
    sort_order = 50
    is_active = $true
    link = 'https://example.com'
    button_text = 'Xem'
}
Assert-Result -Name 'BAN create' -Ok ($banCreate.Status -in 200, 201 -and $banCreate.Json.data.id) -Detail "status=$($banCreate.Status) msg=$($banCreate.Json.message)"
$banId = $banCreate.Json.data.id

if ($banId) {
    $banShow = Invoke-Api -Method GET -Path "/admin/banners/$banId" -Headers $Auth
    Assert-Result -Name 'BAN show' -Ok ($banShow.Status -eq 200)
    $banUpd = Invoke-Api -Method PUT -Path "/admin/banners/$banId" -Headers $Auth -Body @{
        title = 'Banner Smoke API Updated'
        position = $pos
        sort_order = 51
        is_active = $true
    }
    Assert-Result -Name 'BAN update' -Ok ($banUpd.Status -eq 200)
}

# ---------------------------------------------------------------------------
# 9) Posts
# ---------------------------------------------------------------------------
Write-Host "`n--- Posts ---"
$postList = Invoke-Api -Method GET -Path '/admin/posts' -Headers $Auth
Assert-Result -Name 'POST list' -Ok ($postList.Status -eq 200)

$postCreate = Invoke-Api -Method POST -Path '/admin/posts' -Headers $Auth -Body @{
    title = 'Bai viet Smoke API'
    excerpt = 'excerpt'
    content = 'Noi dung test'
    is_published = $true
}
Assert-Result -Name 'POST create' -Ok ($postCreate.Status -eq 201 -and $postCreate.Json.data.id) -Detail "id=$($postCreate.Json.data.id)"
$postId = $postCreate.Json.data.id
$postSlug = $postCreate.Json.data.slug

$postShow = Invoke-Api -Method GET -Path "/admin/posts/$postId" -Headers $Auth
Assert-Result -Name 'POST show' -Ok ($postShow.Status -eq 200)

$postUpd = Invoke-Api -Method PUT -Path "/admin/posts/$postId" -Headers $Auth -Body @{
    title = 'Bai viet Smoke API Updated'
    excerpt = 'excerpt 2'
    content = 'Noi dung test 2'
    is_published = $true
}
Assert-Result -Name 'POST update' -Ok ($postUpd.Status -eq 200)

# ---------------------------------------------------------------------------
# 10) Pages
# ---------------------------------------------------------------------------
Write-Host "`n--- Pages ---"
$pageList = Invoke-Api -Method GET -Path '/admin/pages' -Headers $Auth
Assert-Result -Name 'PAGE list' -Ok ($pageList.Status -eq 200)

$pageCreate = Invoke-Api -Method POST -Path '/admin/pages' -Headers $Auth -Body @{
    title = 'Trang Smoke API'
    content = 'Noi dung trang'
    is_published = $true
    show_in_menu = $true
    sort_order = 20
}
Assert-Result -Name 'PAGE create' -Ok ($pageCreate.Status -eq 201 -and $pageCreate.Json.data.id) -Detail "id=$($pageCreate.Json.data.id)"
$pageId = $pageCreate.Json.data.id
$pageSlug = $pageCreate.Json.data.slug

$pageShow = Invoke-Api -Method GET -Path "/admin/pages/$pageId" -Headers $Auth
Assert-Result -Name 'PAGE show' -Ok ($pageShow.Status -eq 200)

$pageUpd = Invoke-Api -Method PUT -Path "/admin/pages/$pageId" -Headers $Auth -Body @{
    title = 'Trang Smoke API Updated'
    content = 'Noi dung trang 2'
    is_published = $true
    show_in_menu = $false
    sort_order = 21
}
Assert-Result -Name 'PAGE update' -Ok ($pageUpd.Status -eq 200)

# ---------------------------------------------------------------------------
# 11) Settings
# ---------------------------------------------------------------------------
Write-Host "`n--- Settings ---"
$setGet = Invoke-Api -Method GET -Path '/admin/settings' -Headers $Auth
Assert-Result -Name 'SETTINGS get' -Ok ($setGet.Status -eq 200 -and $setGet.Json.data.site_name)

$siteName = [string]$setGet.Json.data.site_name
$setPayload = @{
    site_name = $siteName
    site_tagline = [string]$setGet.Json.data.site_tagline
    phone = [string]$setGet.Json.data.phone
    email = if ($setGet.Json.data.email) { [string]$setGet.Json.data.email } else { 'admin@3dshop.local' }
    hotline = [string]$setGet.Json.data.hotline
    address = [string]$setGet.Json.data.address
    working_hours = [string]$setGet.Json.data.working_hours
    facebook = [string]$setGet.Json.data.facebook
    zalo = [string]$setGet.Json.data.zalo
    youtube = [string]$setGet.Json.data.youtube
    footer_about = [string]$setGet.Json.data.footer_about
    footer_copyright = [string]$setGet.Json.data.footer_copyright
    home_about_title = [string]$setGet.Json.data.home_about_title
    home_about_content = [string]$setGet.Json.data.home_about_content
    home_why_title = [string]$setGet.Json.data.home_why_title
    home_why_content = [string]$setGet.Json.data.home_why_content
    meta_title = [string]$setGet.Json.data.meta_title
    meta_description = [string]$setGet.Json.data.meta_description
    meta_keywords = [string]$setGet.Json.data.meta_keywords
}
$setPut = Invoke-Api -Method PUT -Path '/admin/settings' -Headers $Auth -Body $setPayload
Assert-Result -Name 'SETTINGS put' -Ok ($setPut.Status -eq 200 -and $setPut.Json.success) -Detail "status=$($setPut.Status) body=$($setPut.Body)"

# ---------------------------------------------------------------------------
# 12) Chat (admin) — need guest conversation first via public chat OR create via guest API
#     Public guest chat is NOT admin feature; admin chat requires auth.
# ---------------------------------------------------------------------------
Write-Host "`n--- Admin Chat ---"
# Start guest chat without auth (storefront). Then manage via admin token.
$guestStart = Invoke-Api -Method POST -Path '/chat/start' -Body @{
    guest_name = 'Smoke Guest'
    guest_phone = '0911222333'
    message = 'Can bao gia in 3D'
}
Assert-Result -Name 'GUEST chat start (public channel)' -Ok ($guestStart.Status -in 200, 201 -and $guestStart.Json.data.token) -Detail "status=$($guestStart.Status)"
$guestToken = $guestStart.Json.data.token
$convId = $guestStart.Json.data.conversation.id

$chatList = Invoke-Api -Method GET -Path '/admin/chat?status=open' -Headers $Auth
Assert-Result -Name 'CHAT list' -Ok ($chatList.Status -eq 200) -Detail "status=$($chatList.Status)"

$chatShow = Invoke-Api -Method GET -Path "/admin/chat/$convId" -Headers $Auth
Assert-Result -Name 'CHAT show' -Ok ($chatShow.Status -eq 200 -and $chatShow.Json.data.id -eq $convId)

$chatReply = Invoke-Api -Method POST -Path "/admin/chat/$convId/reply" -Headers $Auth -Body @{
    message = 'Shop da nhan yeu cau, se bao gia som.'
}
Assert-Result -Name 'CHAT reply' -Ok ($chatReply.Status -eq 200 -and $chatReply.Json.success)

$chatTyping = Invoke-Api -Method POST -Path "/admin/chat/$convId/typing" -Headers $Auth -Body @{ typing = $true }
Assert-Result -Name 'CHAT typing' -Ok ($chatTyping.Status -eq 200)

$chatPoll = Invoke-Api -Method GET -Path "/admin/chat/$convId/poll?after_id=0" -Headers $Auth
Assert-Result -Name 'CHAT poll' -Ok ($chatPoll.Status -eq 200)

$notif = Invoke-Api -Method GET -Path '/admin/chat/notifications?with_list=1' -Headers $Auth
Assert-Result -Name 'CHAT notifications' -Ok ($notif.Status -eq 200 -and $null -ne $notif.Json.data.unread_count)

$notifRead = Invoke-Api -Method POST -Path '/admin/chat/notifications/read' -Headers $Auth -Body @{
    conversation_id = $convId
}
Assert-Result -Name 'CHAT mark read' -Ok ($notifRead.Status -eq 200)

$chatClose = Invoke-Api -Method POST -Path "/admin/chat/$convId/close" -Headers $Auth
Assert-Result -Name 'CHAT close' -Ok ($chatClose.Status -eq 200 -and $chatClose.Json.data.status -eq 'closed')

$chatReopen = Invoke-Api -Method POST -Path "/admin/chat/$convId/reopen" -Headers $Auth
Assert-Result -Name 'CHAT reopen' -Ok ($chatReopen.Status -eq 200 -and $chatReopen.Json.data.status -eq 'open')

# Guest send/typing after reopen
$guestSend = Invoke-Api -Method POST -Path '/chat/send' -Body @{
    token = $guestToken
    message = 'Them cau hoi ve PLA'
}
Assert-Result -Name 'GUEST chat send' -Ok ($guestSend.Status -eq 200)

$guestShow = Invoke-Api -Method GET -Path "/chat?token=$guestToken" 
Assert-Result -Name 'GUEST chat poll' -Ok ($guestShow.Status -eq 200)

$guestTyping = Invoke-Api -Method POST -Path '/chat/typing' -Body @{
    token = $guestToken
    typing = $true
}
Assert-Result -Name 'GUEST chat typing' -Ok ($guestTyping.Status -eq 200)

# ---------------------------------------------------------------------------
# 13) Soft delete + trash + restore + force delete
# ---------------------------------------------------------------------------
Write-Host "`n--- Trash ---"
$prodDel = Invoke-Api -Method DELETE -Path "/admin/products/$prodId" -Headers $Auth
Assert-Result -Name 'PROD soft-delete' -Ok ($prodDel.Status -eq 200)

$trash = Invoke-Api -Method GET -Path '/admin/trash?type=products' -Headers $Auth
Assert-Result -Name 'TRASH list products' -Ok ($trash.Status -eq 200 -and $trash.Json.data.total -ge 1)

$restore = Invoke-Api -Method POST -Path "/admin/trash/products/$prodId/restore" -Headers $Auth
Assert-Result -Name 'TRASH restore product' -Ok ($restore.Status -eq 200)

$prodDel2 = Invoke-Api -Method DELETE -Path "/admin/products/$prodId" -Headers $Auth
$force = Invoke-Api -Method DELETE -Path "/admin/trash/products/$prodId" -Headers $Auth
Assert-Result -Name 'TRASH force-delete product' -Ok ($force.Status -eq 200)

# Soft-delete other created resources
if ($banId) {
    $r = Invoke-Api -Method DELETE -Path "/admin/banners/$banId" -Headers $Auth
    Assert-Result -Name 'BAN soft-delete' -Ok ($r.Status -eq 200)
    $r2 = Invoke-Api -Method DELETE -Path "/admin/trash/banners/$banId" -Headers $Auth
    Assert-Result -Name 'BAN force-delete' -Ok ($r2.Status -eq 200)
}
if ($postId) {
    $r = Invoke-Api -Method DELETE -Path "/admin/posts/$postId" -Headers $Auth
    Assert-Result -Name 'POST soft-delete' -Ok ($r.Status -eq 200)
    $r2 = Invoke-Api -Method DELETE -Path "/admin/trash/posts/$postId" -Headers $Auth
    Assert-Result -Name 'POST force-delete' -Ok ($r2.Status -eq 200)
}
if ($pageId) {
    $r = Invoke-Api -Method DELETE -Path "/admin/pages/$pageId" -Headers $Auth
    Assert-Result -Name 'PAGE soft-delete' -Ok ($r.Status -eq 200)
    $r2 = Invoke-Api -Method DELETE -Path "/admin/trash/pages/$pageId" -Headers $Auth
    Assert-Result -Name 'PAGE force-delete' -Ok ($r2.Status -eq 200)
}
if ($eqId) {
    $r = Invoke-Api -Method DELETE -Path "/admin/equipment/$eqId" -Headers $Auth
    Assert-Result -Name 'EQ soft-delete' -Ok ($r.Status -eq 200)
    $r2 = Invoke-Api -Method DELETE -Path "/admin/trash/equipment/$eqId" -Headers $Auth
    Assert-Result -Name 'EQ force-delete' -Ok ($r2.Status -eq 200)
}
if ($inputId) {
    $r = Invoke-Api -Method DELETE -Path "/admin/material-inputs/$inputId" -Headers $Auth
    Assert-Result -Name 'INPUT soft-delete' -Ok ($r.Status -eq 200) -Detail "status=$($r.Status) body=$($r.Body)"
    $r2 = Invoke-Api -Method DELETE -Path "/admin/trash/material_inputs/$inputId" -Headers $Auth
    Assert-Result -Name 'INPUT force-delete' -Ok ($r2.Status -eq 200) -Detail "status=$($r2.Status) body=$($r2.Body)"
}
if ($matId) {
    $r = Invoke-Api -Method DELETE -Path "/admin/materials/$matId" -Headers $Auth
    Assert-Result -Name 'MAT soft-delete' -Ok ($r.Status -eq 200) -Detail "status=$($r.Status) body=$($r.Body)"
    $r2 = Invoke-Api -Method DELETE -Path "/admin/trash/materials/$matId" -Headers $Auth
    Assert-Result -Name 'MAT force-delete' -Ok ($r2.Status -eq 200) -Detail "status=$($r2.Status) body=$($r2.Body)"
}
if ($catId) {
    $r = Invoke-Api -Method DELETE -Path "/admin/categories/$catId" -Headers $Auth
    Assert-Result -Name 'CAT soft-delete' -Ok ($r.Status -eq 200) -Detail "status=$($r.Status) body=$($r.Body)"
    $r2 = Invoke-Api -Method DELETE -Path "/admin/trash/categories/$catId" -Headers $Auth
    Assert-Result -Name 'CAT force-delete' -Ok ($r2.Status -eq 200) -Detail "status=$($r2.Status) body=$($r2.Body)"
}

$trashEmpty = Invoke-Api -Method DELETE -Path '/admin/trash' -Headers $Auth
Assert-Result -Name 'TRASH empty (cleanup call)' -Ok ($trashEmpty.Status -eq 200) -Detail "status=$($trashEmpty.Status) body=$($trashEmpty.Body)"

# ---------------------------------------------------------------------------
# 14) Public catalog (currently open — report status for awareness)
# ---------------------------------------------------------------------------
Write-Host "`n--- Public catalog (no login, storefront) ---"
foreach ($path in @('/home','/settings','/categories','/products','/posts','/pages')) {
    $r = Invoke-Api -Method GET -Path $path
    Assert-Result -Name "PUBLIC GET $path" -Ok ($r.Status -eq 200 -and $r.Json.success) -Detail "status=$($r.Status)"
}

if ($prodSlug) {
    # product was force-deleted; expect 404 or use existing slug from list
}
$existingProd = Invoke-Api -Method GET -Path '/products?per_page=1'
if ($existingProd.Json.data -and $existingProd.Json.data.Count -gt 0) {
    $slug = $existingProd.Json.data[0].slug
    $r = Invoke-Api -Method GET -Path "/products/$slug"
    Assert-Result -Name 'PUBLIC product show' -Ok ($r.Status -eq 200)
}

# ---------------------------------------------------------------------------
# 15) Logout — token must stop working
# ---------------------------------------------------------------------------
Write-Host "`n--- Logout ---"
$logout = Invoke-Api -Method POST -Path '/admin/logout' -Headers $Auth
Assert-Result -Name 'LOGOUT' -Ok ($logout.Status -eq 200) -Detail "status=$($logout.Status) body=$($logout.Body)"

$afterLogout = Invoke-Api -Method GET -Path '/admin/me' -Headers $Auth
Assert-Result -Name 'ME after logout = 401' -Ok ($afterLogout.Status -eq 401) -Detail "status=$($afterLogout.Status) body=$($afterLogout.Body)"

# ---------------------------------------------------------------------------
# Summary
# ---------------------------------------------------------------------------
Write-Host "`n=============================="
Write-Host ("TOTAL: {0}  PASS: {1}  FAIL: {2}" -f ($Pass + $Fail), $Pass, $Fail)
Write-Host "=============================="
if ($Fail -gt 0) {
    Write-Host "`nFailed cases:"
    $Results | Where-Object { $_.Status -eq 'FAIL' } | ForEach-Object {
        Write-Host (" - {0} :: {1}" -f $_.Name, $_.Detail)
    }
    exit 1
}
exit 0
