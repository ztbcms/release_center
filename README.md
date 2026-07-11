# Release Center

Release Center 是用于管理客户端应用版本和提供版本检测接口的 ZTBCMS 模块。

## 安装

将本仓库克隆到 ZTBCMS 项目的 `tp6/app/release_center` 目录后，在 `tp6` 目录执行：

```bash
php think module:install release_center
```

为使用公开版本检测地址 `/release-center/api/v1/check`，需要在宿主项目的 `tp6/config/app.php` 中保留以下应用映射：

```php
'app_map' => [
    'release-center' => 'release_center',
    'release_center' => 'release_center',
],
```

第二条映射用于保证后台菜单和权限地址可正常访问。

安装后，后台会出现“Release Center / 应用管理 / 版本管理”菜单。

卸载模块：

```bash
php think module:uninstall release_center --force
```

卸载会删除 Release Center 的应用、版本数据表和对应菜单。

## 使用规则

- 一个 Application 只对应一种可直接下载的客户端产物
- 不同系统、CPU 架构、商店版本或更新轨道应创建不同 Application
- AppKey 只允许小写字母、数字、下划线和中划线，创建后不可修改
- Build 是客户端比较版本的唯一依据，新 Build 必须大于历史最大 Build
- Version 仅用于展示，不参与版本比较
- 下载地址为可选项；如填写，仅支持 HTTP 或 HTTPS 地址
- 版本发布后可编辑下载地址和更新说明，可撤回或恢复，但不可删除

## 版本检查接口

```http
GET /release-center/api/v1/check?app_key=mall_app&current_build=105
```

有更新时返回：

```json
{
  "status": true,
  "code": 200,
  "data": {
    "update": true,
    "app_key": "mall_app",
    "current_build": 105,
    "latest": {
      "version": "1.2.0",
      "build": 110,
      "download_url": "https://example.com/app.apk",
      "release_note": "更新说明",
      "publish_time": 1783699200
    }
  },
  "msg": ""
}
```

接口不需要登录，但同一 IP 每分钟最多请求 120 次。

## 缓存与发布操作

模块缓存每个应用的最新启用版本，缓存有效期为 7 天。

发布、编辑、撤回、恢复版本以及启用、禁用应用后，不会自动清理缓存。需要在“版本管理”页面点击“清理版本缓存”，变更才会立即对客户端生效。

未手动清理时，旧版本信息最长保留 7 天。
