# hyperf-akm

蓝瀚媒体代理业务 OpenAPI（AKMS），命名空间 `Goletter\Akm`。

默认地址 `https://akms-openapi.bluemediagroup.cn`。覆盖数据概览、开户管理（Facebook / Google / TikTok）、账户查询与需求申请。

Token 轮询和公共异常复用 [`goletter/hyperf-adv`](https://github.com/goletter/hyperf-adv)。业务入口是 `AkmClient`、`DashboardApi`、`OpenAccountApi`、`ApplicationApi`。

## 目录

- [安装](#安装)
- [配置](#配置)
- [快速开始](#快速开始)
- [数据概览](#数据概览)
- [开户](#开户)
- [账户与需求申请](#账户与需求申请)
- [Client](#client)
- [多 Token 轮询](#多-token-轮询)
- [调用日志](#调用日志)
- [异常](#异常)

## 安装

```bash
composer require goletter/hyperf-akm
```

会安装依赖 `goletter/hyperf-adv`。Hyperf 项目发布本包配置：

```bash
php bin/hyperf.php vendor:publish goletter/hyperf-akm
```

生成 `config/autoload/akm.php`。

## 配置

```php
return [
    'base_uri' => env('AKM_BASE_URL', env('CKM_BASE_URL', 'https://akms-openapi.bluemediagroup.cn')),
    'timeout' => (float) env('AKM_TIMEOUT', 10),
    // 请求耗时日志，默认关闭；开启后成功/失败都会写 call_logs（需 hyperf-utils）
    'log_request' => (bool) env('AKM_LOG_REQUEST', false),
    // 'default_headers' => [],
];
```

| 配置 | 说明 |
|------|------|
| `base_uri` | OpenAPI 根地址。`AKM_BASE_URL` 为空时回退 `CKM_BASE_URL` |
| `timeout` | HTTP 超时，秒，默认 `10` |
| `log_request` | 是否记录每次请求耗时（`duration_ms`），默认 `false` |
| `default_headers` | 每个请求附带的头。`Authorization` 由 Client 按当前 token 写入 |

不传 `base_uri`、`timeout`、`log_request` 时，`new AkmClient($token)` 会读取这份配置。构造参数覆盖配置文件。

## 快速开始

```php
use Goletter\Akm\AkmClient;
use Goletter\Akm\ApplicationApi;
use Goletter\Akm\DashboardApi;
use Goletter\Akm\OpenAccountApi;

$client = new AkmClient($accessToken);
$dashboard = new DashboardApi($client);
$openApi = new OpenAccountApi($client);
$appApi = new ApplicationApi($client);

$overview = $dashboard->getBaseSpend();
$fbList = $openApi->facebookList([
    'current_page' => 1,
    'page_size' => 100,
]);
$fbAccounts = $appApi->facebookAccounts([
    'current_page' => 1,
    'page_size' => 100,
]);
```

成功时方法返回完整 JSON（含 `code`、`info`、`data`），`code` 为 `0`。业务数据在 `data`。

## 数据概览

`DashboardApi`。都是 `GET /open_api/v1.0/dashboard/brand_dash`，用 `module` 区分。

| 方法 | module | 说明 |
|------|--------|------|
| `getBaseSpend(array $params = [])` | `base_spend` | 昨天、前天、近 7 天、近 28 天、近 3 个月消耗 |
| `getSpendTrend(array $params)` | `spend_trend` | 媒体消耗趋势。需要 `dash_time`（起止日期），可选 `medium`、`accounts` |
| `getMediumDetail(array $params)` | `medium_detail` | 媒体详情：总消耗、CPC、CPM 等 |
| `getExportDetail(array $params)` | `export_detail` | 广告账户天级消耗导出 |

```php
$trend = $dashboard->getSpendTrend([
    'dash_time' => ['2026-10-01', '2026-10-07'],
]);
```

## 开户

`OpenAccountApi`。路径前缀 `/open_api/v1.0/user_open`。

### Facebook

| 方法 | 请求 | 路径 |
|------|------|------|
| `facebookApply(array $body)` | POST | `/fb_list` |
| `facebookList(array $query = [])` | GET | `/fb_list` |
| `facebookDetail(string $requestId)` | GET | `/fb_list/{request_id}` |

`facebookApply` 通过 `request_id` 提交。

### Google

| 方法 | 请求 | 路径 |
|------|------|------|
| `googleApply(array $body)` | POST | `/gg_accounts` |
| `googleList(array $query = [])` | GET | `/gg_accounts` |
| `googleDetail(int $id)` | GET | `/gg_accounts/{id}` |
| `googleWithdraw(array $body)` | POST | `/gg_accounts_back` |
| `googleTimeZones()` | GET | `/get_time_zone` |

### TikTok

| 方法 | 请求 | 路径 |
|------|------|------|
| `tiktokUploadBusinessRegistration(string $filePath)` | POST multipart | `/tt_upload_br` |
| `tiktokApply(array $body)` | POST | `/tt_oa` |
| `tiktokList(array $query = [])` | GET | `/tt_list` |
| `tiktokDetail(int $id)` | GET | `/tt_list/{id}` |
| `tiktokWithdraw(array $body)` | POST | `/tt_oa_revoke` |
| `tiktokTimeZones()` | GET | `/get_tt_timezone` |
| `tiktokRegisteredAreas()` | GET | `/get_tt_registered_area` |
| `tiktokIndustries()` | GET | `/get_tt_vertical` |

营业执照上传的表单字段是 `br_file`。`$filePath` 必须是本地已存在的文件，否则抛 `InvalidArgumentException`。

```php
$uploaded = $openApi->tiktokUploadBusinessRegistration('/path/to/license.jpg');
$areas = $openApi->tiktokRegisteredAreas();
$industries = $openApi->tiktokIndustries();
```

## 账户与需求申请

`ApplicationApi`。路径前缀 `/open_api/v1.0/application`。

### 查询

| 方法 | 请求 | 路径 | 说明 |
|------|------|------|------|
| `facebookAccounts(array $query = [])` | GET | `/facebook/list` | Facebook 账户列表 |
| `facebookAccountClearRecords(array $body)` | POST | `/facebook/get_account_clear_record` | Facebook 账户清零记录 |
| `googleAccounts(array $query = [])` | GET | `/google/list` | Google 账户列表 |
| `tiktokAccounts(array $query = [])` | GET | `/tiktok/list` | TikTok 账户列表 |
| `smallMediumList(array $query = [])` | GET | `/small_medium/list` | 新兴媒体账户需求列表 |
| `smallMediumDetail(int $id)` | GET | `/small_medium/list/{id}` | 新兴媒体账户需求详情 |
| `operateRecords(array $query = [])` | GET | `/operate_record` | 大媒体账户管理需求申请记录 |

### 需求申请

| 方法 | 路径 | 说明 |
|------|------|------|
| `facebookAmountSpend(array $body)` | POST `/facebook/amount_spend` | Facebook 额度调整 |
| `facebookBindBm(array $body)` | POST `/facebook/bind_bm` | Facebook 绑定 / 解绑 BM |
| `facebookBindPixel(array $body)` | POST `/facebook/bind_pixel` | Facebook 绑定 / 解绑 Pixel |
| `facebookRename(array $body)` | POST `/facebook/rename` | Facebook 更名 |
| `googleAmountSpend(array $body)` | POST `/google/amount_spend` | Google 额度变更 |
| `googleUserRole(array $body)` | POST `/google/user_role` | Google 授权个人邮箱（绑定 / 解绑） |
| `googleLinkMcc(array $body)` | POST `/google/link_mcc` | Google 接受 / 拒绝 / 移除 MCC 邀请 |
| `googleRename(array $body)` | POST `/google/rename` | Google 更名 |
| `tiktokAmountSpend(array $body)` | POST `/tiktok/amount_spend` | TikTok 额度变更 |
| `tiktokBindBc(array $body)` | POST `/tiktok/bind_bc` | TikTok 绑定 / 解绑 BC |
| `tiktokBindEmail(array $body)` | POST `/tiktok/bind_email` | TikTok 绑定 / 解绑邮箱 |
| `tiktokRename(array $body)` | POST `/tiktok/rename` | TikTok 更名 |
| `smallMediumAmountSpend(array $body)` | POST `/small_medium/amount_spend` | 新兴媒体额度变更 |
| `smallMediumBind(array $body)` | POST `/small_medium/bind` | 新兴媒体绑定 |
| `smallMediumRename(array $body)` | POST `/small_medium/rename` | 新兴媒体更名 |
| `smallMediumOther(array $body)` | POST `/small_medium/other` | 新兴媒体其他申请 |

列表类接口的分页参数由蓝瀚接口定义，常见为 `current_page`、`page_size`。

## Client

```php
use Goletter\Akm\AkmClient;
use Goletter\Akm\ApplicationApi;
use Goletter\Akm\DashboardApi;
use Goletter\Akm\OpenAccountApi;

$client = new AkmClient($accessToken, 'https://akms-openapi.bluemediagroup.cn', 10);
$client->setDefaultHeaders([
    'X-Request-Source' => 'akm',
]);

$dashboard = new DashboardApi($client);
$openApi = new OpenAccountApi($client);
$appApi = new ApplicationApi($client);
```

| 方法 | 说明 |
|------|------|
| `get($path, $query = [], $apiInterface = '')` | GET |
| `postJson($path, $body = [], $query = [], $apiInterface = '')` | POST JSON |
| `postMultipart($path, $multipart, $query = [], $apiInterface = '')` | POST `multipart/form-data` |

请求自动带 `Authorization: Bearer {当前 token}`。`$apiInterface` 写入调用日志。业务方法已经传入稳定名字，例如 `GET:/user_open/fb_list`、`GET:/dashboard/brand_dash/base_spend`。直接调 Client 且不传时，按 `METHOD` 加去掉 `/open_api/v1.0` 后的路径生成，纯数字 ID 记成 `{id}`。

## 多 Token 轮询

传入 token 数组后，单次请求从当前 token 开始。遇到 API 异常或 Token 失效会换下一个，直到全部失败才抛出最后一次异常。成功后粘滞在可用 token。

```php
$client = new AkmClient([$tokenA, $tokenB]);
$client->getAccessTokens();
$client->getAccessToken();
$client->setAccessTokens([$tokenB, $tokenC]);
```

## 调用日志 / 耗时

每次请求都会统计耗时（毫秒）。`setCallLogHandler` 的回调里带 `duration_ms`：

```php
AkmClient::setCallLogHandler(function (array $log): void {
    // $log['token']
    // $log['api_interface']
    // $log['is_success']   1 成功，0 失败
    // $log['error']
    // $log['duration_ms']  请求耗时（毫秒）
    // $log['call_hour']    Y-m-d H:00:00
    // $log['created_at']
});
```

| 开关 | 行为 |
|------|------|
| `log_request=false`（默认） | 失败时若有 `hyperf-utils` 的 `logging()`，写 `call_logs`（含 `duration_ms`） |
| `log_request=true` | 成功/失败都会写 `call_logs`（含 `duration_ms`） |
| `setCallLogHandler` | 有 handler 时每次请求回调（含 `duration_ms`），与上面开关独立 |

```php
// 构造时强制开启
$client = new AkmClient($accessToken, logRequest: true);
```

未设置 handler 且未开 `log_request`、请求又成功时，不写额外日志。

## 异常

`code` 不是 `0`、响应不是 JSON，或 HTTP 请求失败时抛出 `AkmsApiException`。HTTP 401 抛出 `AkmTokenExpiredException`。它继承 `AkmsApiException`，并实现 `Goletter\Adv\Exceptions\TokenExpiredExceptionInterface`，因此也能和 Facebook / TikTok / Google 的 Token 失效一起捕获。

多 Token 时，上述 API 异常会先换下一个 token，全部失败才抛出最后一次。

```php
use Goletter\Adv\Exceptions\TokenExpiredExceptionInterface;
use Goletter\Akm\Exceptions\AkmTokenExpiredException;
use Goletter\Akm\Exceptions\AkmsApiException;

try {
    $overview = $dashboard->getBaseSpend();
} catch (AkmTokenExpiredException $e) {
    // HTTP 401，Bearer Token 失效
} catch (AkmsApiException $e) {
    $errorData = $e->getResponse(); // 蓝瀚返回体，或 ['raw' => ...]
}

// 或只区分 Token 是否失效：
try {
    $overview = $dashboard->getBaseSpend();
} catch (TokenExpiredExceptionInterface $e) {
    // 含本包的 AkmTokenExpiredException
} catch (AkmsApiException $e) {
    $errorData = $e->getResponse();
}
```

`getMessage()` 优先取响应里的 `info`。`getCode()` 为业务 `code`；401 时为 HTTP 状态码。
