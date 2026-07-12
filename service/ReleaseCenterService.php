<?php

namespace app\release_center\service;

use app\BaseController;
use app\release_center\model\ReleaseAppModel;
use app\release_center\model\ReleaseVersionModel;
use think\facade\Db;

/**
 * Release Center业务服务
 */
class ReleaseCenterService
{
    /**
     * 创建应用
     *
     * @param array $data 应用数据
     * @return array
     */
    public function createApplication(array $data): array
    {
        $name = trim((string)($data['name'] ?? ''));
        $appKey = trim((string)($data['app_key'] ?? ''));
        $remark = trim((string)($data['remark'] ?? ''));
        if ($name === '' || mb_strlen($name) > 100) {
            return BaseController::createReturn(false, [], '应用名称不能为空且最多 100 个字符');
        }
        if (!$this->isValidAppKey($appKey)) {
            return BaseController::createReturn(false, [], 'AppKey 仅支持 2～64 位小写字母、数字、下划线和中划线');
        }
        if (mb_strlen($remark) > 500) {
            return BaseController::createReturn(false, [], '备注最多 500 个字符');
        }
        if ((new ReleaseAppModel())->where('app_key', $appKey)->find()) {
            return BaseController::createReturn(false, [], 'AppKey 已存在');
        }
        $now = time();
        try {
            $id = (new ReleaseAppModel())->insertGetId([
                'name' => $name,
                'app_key' => $appKey,
                'remark' => $remark,
                'status' => ReleaseAppModel::STATUS_ENABLE,
                'create_time' => $now,
                'update_time' => $now,
            ]);
            return BaseController::createReturn(true, ['id' => intval($id)], '创建成功');
        } catch (\Throwable $e) {
            return BaseController::createReturn(false, [], '创建失败，AppKey 可能已存在');
        }
    }

    /**
     * 编辑应用
     *
     * @param int $id 应用ID
     * @param array $data 应用数据
     * @return array
     */
    public function updateApplication(int $id, array $data): array
    {
        $app = (new ReleaseAppModel())->where('id', $id)->find();
        if (!$app) {
            return BaseController::createReturn(false, [], '应用不存在');
        }
        $name = trim((string)($data['name'] ?? ''));
        $remark = trim((string)($data['remark'] ?? ''));
        if ($name === '' || mb_strlen($name) > 100) {
            return BaseController::createReturn(false, [], '应用名称不能为空且最多 100 个字符');
        }
        if (mb_strlen($remark) > 500) {
            return BaseController::createReturn(false, [], '备注最多 500 个字符');
        }
        (new ReleaseAppModel())->where('id', $id)->update([
            'name' => $name,
            'remark' => $remark,
            'update_time' => time(),
        ]);
        (new ReleaseCacheService())->deleteLatest((string)$app['app_key']);
        return BaseController::createReturn(true, [], '保存成功');
    }

    /**
     * 修改应用状态
     *
     * @param int $id 应用ID
     * @param int $status 状态
     * @return array
     */
    public function changeApplicationStatus(int $id, int $status): array
    {
        if (!$this->isValidStatus($status)) {
            return BaseController::createReturn(false, [], '状态参数不正确');
        }
        $app = (new ReleaseAppModel())->where('id', $id)->find();
        if (!$app) {
            return BaseController::createReturn(false, [], '应用不存在');
        }
        $affected = (new ReleaseAppModel())->where('id', $id)->update([
            'status' => $status,
            'update_time' => time(),
        ]);
        if ($affected === false || $affected === 0) {
            return BaseController::createReturn(false, [], '应用不存在或状态未变化');
        }
        (new ReleaseCacheService())->deleteLatest((string)$app['app_key']);
        return BaseController::createReturn(true, [], '操作成功');
    }

    /**
     * 发布版本
     *
     * @param array $data 版本数据
     * @return array
     */
    public function publishVersion(array $data): array
    {
        $appId = intval($data['app_id'] ?? 0);
        $version = trim((string)($data['version'] ?? ''));
        $buildResult = $this->normalizeBuild($data['build'] ?? null, false);
        $downloadUrl = trim((string)($data['download_url'] ?? ''));
        $releaseNote = trim((string)($data['release_note'] ?? ''));
        if ($appId <= 0) {
            return BaseController::createReturn(false, [], '请选择所属应用');
        }
        if ($version === '' || mb_strlen($version) > 100) {
            return BaseController::createReturn(false, [], 'Version 不能为空且最多 100 个字符');
        }
        if (!$buildResult['status']) {
            return BaseController::createReturn(false, [], $buildResult['msg']);
        }
        if ($downloadUrl !== '' && !$this->isValidDownloadUrl($downloadUrl)) {
            return BaseController::createReturn(false, [], '下载地址仅支持 HTTP 或 HTTPS');
        }
        $app = (new ReleaseAppModel())->where('id', $appId)->find();
        if (!$app) {
            return BaseController::createReturn(false, [], '所属应用不存在');
        }
        if (intval($app['status']) !== ReleaseAppModel::STATUS_ENABLE) {
            return BaseController::createReturn(false, [], '所属应用已禁用，无法发布版本');
        }
        $build = $buildResult['value'];
        $maxBuild = (new ReleaseVersionModel())->where('app_id', $appId)->max('build');
        if ($maxBuild !== null && $maxBuild !== false && intval($build) <= intval($maxBuild)) {
            return BaseController::createReturn(false, [], 'Build 必须大于该应用历史最大 Build');
        }
        $now = time();
        try {
            $id = (new ReleaseVersionModel())->insertGetId([
                'app_id' => $appId,
                'version' => $version,
                'build' => $build,
                'download_url' => $downloadUrl,
                'release_note' => $releaseNote,
                'publish_time' => $now,
                'status' => ReleaseVersionModel::STATUS_ENABLE,
                'create_time' => $now,
                'update_time' => $now,
            ]);
            (new ReleaseCacheService())->deleteLatest((string)$app['app_key']);
            return BaseController::createReturn(true, ['id' => intval($id)], '发布成功');
        } catch (\Throwable $e) {
            return BaseController::createReturn(false, [], '发布失败，Build 可能已存在');
        }
    }

    /**
     * 编辑版本可变信息
     *
     * @param int $id 版本ID
     * @param array $data 版本数据
     * @return array
     */
    public function updateVersion(int $id, array $data): array
    {
        $version = (new ReleaseVersionModel())->where('id', $id)->find();
        if (!$version) {
            return BaseController::createReturn(false, [], '版本不存在');
        }
        $downloadUrl = trim((string)($data['download_url'] ?? ''));
        $releaseNote = trim((string)($data['release_note'] ?? ''));
        if ($downloadUrl !== '' && !$this->isValidDownloadUrl($downloadUrl)) {
            return BaseController::createReturn(false, [], '下载地址仅支持 HTTP 或 HTTPS');
        }
        $app = (new ReleaseAppModel())->where('id', intval($version['app_id']))->find();
        if (!$app) {
            return BaseController::createReturn(false, [], '所属应用不存在');
        }
        (new ReleaseVersionModel())->where('id', $id)->update([
            'download_url' => $downloadUrl,
            'release_note' => $releaseNote,
            'update_time' => time(),
        ]);
        (new ReleaseCacheService())->deleteLatest((string)$app['app_key']);
        return BaseController::createReturn(true, [], '保存成功');
    }

    /**
     * 修改版本状态
     *
     * @param int $id 版本ID
     * @param int $status 状态
     * @return array
     */
    public function changeVersionStatus(int $id, int $status): array
    {
        if (!$this->isValidStatus($status)) {
            return BaseController::createReturn(false, [], '状态参数不正确');
        }
        $version = (new ReleaseVersionModel())->where('id', $id)->find();
        if (!$version) {
            return BaseController::createReturn(false, [], '版本不存在');
        }
        $app = (new ReleaseAppModel())->where('id', intval($version['app_id']))->find();
        if (!$app) {
            return BaseController::createReturn(false, [], '所属应用不存在');
        }
        $affected = (new ReleaseVersionModel())->where('id', $id)->update([
            'status' => $status,
            'update_time' => time(),
        ]);
        if ($affected === false || $affected === 0) {
            return BaseController::createReturn(false, [], '版本不存在或状态未变化');
        }
        $message = $status === ReleaseVersionModel::STATUS_DISABLE ? '撤回成功' : '恢复成功';
        (new ReleaseCacheService())->deleteLatest((string)$app['app_key']);
        return BaseController::createReturn(true, [], $message);
    }

    /**
     * 获取应用最新启用版本
     *
     * @param string $appKey 应用标识
     * @return array
     */
    public function getLatestEnabledVersion(string $appKey): array
    {
        $cache = new ReleaseCacheService();
        $cached = $cache->getLatest($appKey);
        if ($cached !== null) {
            return BaseController::createReturn(true, $cached);
        }
        $app = (new ReleaseAppModel())->where('app_key', $appKey)->find();
        if (!$app) {
            return BaseController::createReturn(false, [], '应用不存在', 404);
        }
        if (intval($app['status']) !== ReleaseAppModel::STATUS_ENABLE) {
            return BaseController::createReturn(false, [], '应用已禁用', 403);
        }
        $version = (new ReleaseVersionModel())
            ->where('app_id', intval($app['id']))
            ->where('status', ReleaseVersionModel::STATUS_ENABLE)
            ->order('build desc')
            ->find();
        if (!$version) {
            $cache->setLatest($appKey, []);
            return BaseController::createReturn(true, []);
        }
        $data = $this->formatPublicVersion($version->toArray());
        $cache->setLatest($appKey, $data);
        return BaseController::createReturn(true, $data);
    }

    /**
     * 检查客户端是否有更新
     *
     * @param string $appKey 应用标识
     * @param int $currentBuild 当前Build
     * @return array
     */
    public function checkUpdate(string $appKey, int $currentBuild): array
    {
        $latestResult = $this->getLatestEnabledVersion($appKey);
        if (!$latestResult['status']) {
            return $latestResult;
        }
        $latest = $latestResult['data'];
        if (empty($latest)) {
            return BaseController::createReturn(true, [
                'update' => false,
                'app_key' => $appKey,
                'current_build' => $currentBuild,
                'latest_build' => null,
            ]);
        }
        $latestBuild = intval($latest['build']);
        if ($currentBuild >= $latestBuild) {
            return BaseController::createReturn(true, [
                'update' => false,
                'app_key' => $appKey,
                'current_build' => $currentBuild,
                'latest_build' => $latestBuild,
            ]);
        }
        return BaseController::createReturn(true, [
            'update' => true,
            'app_key' => $appKey,
            'current_build' => $currentBuild,
            'latest' => $latest,
        ]);
    }

    /**
     * 获取指定应用历史最大Build
     *
     * @param int $appId 应用ID
     * @return int
     */
    public function getMaxBuild(int $appId): int
    {
        if ($appId <= 0) {
            return 0;
        }
        return intval((new ReleaseVersionModel())->where('app_id', $appId)->max('build'));
    }

    /**
     * 校验AppKey格式
     *
     * @param string $appKey 应用标识
     * @return bool
     */
    public function isValidAppKey(string $appKey): bool
    {
        return preg_match('/^[a-z0-9][a-z0-9_-]{1,63}$/', $appKey) === 1;
    }

    /**
     * 规范化Build参数
     *
     * @param mixed $value Build参数
     * @param bool $allowZero 是否允许0
     * @return array
     */
    public function normalizeBuild($value, bool $allowZero): array
    {
        $value = trim((string)$value);
        if ($value === '' || !ctype_digit($value)) {
            return ['status' => false, 'value' => 0, 'msg' => 'Build 必须是整数'];
        }
        $value = ltrim($value, '0');
        if ($value === '') {
            $value = '0';
        }
        if (strlen($value) > 18) {
            return ['status' => false, 'value' => 0, 'msg' => 'Build 数值过大'];
        }
        $build = intval($value);
        if ($allowZero ? $build < 0 : $build <= 0) {
            return ['status' => false, 'value' => 0, 'msg' => $allowZero ? 'current_build 参数不正确' : 'Build 必须大于 0'];
        }
        return ['status' => true, 'value' => $build, 'msg' => ''];
    }

    /**
     * 格式化公开版本数据
     *
     * @param array $version 版本记录
     * @return array
     */
    private function formatPublicVersion(array $version): array
    {
        return [
            'version' => (string)($version['version'] ?? ''),
            'build' => intval($version['build'] ?? 0),
            'download_url' => (string)($version['download_url'] ?? ''),
            'release_note' => (string)($version['release_note'] ?? ''),
            'publish_time' => intval($version['publish_time'] ?? 0),
        ];
    }

    /**
     * 校验下载地址
     *
     * @param string $url 下载地址
     * @return bool
     */
    private function isValidDownloadUrl(string $url): bool
    {
        if ($url === '' || mb_strlen($url) > 2048 || !filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
        return in_array($scheme, ['http', 'https'], true);
    }

    /**
     * 校验状态值
     *
     * @param int $status 状态值
     * @return bool
     */
    private function isValidStatus(int $status): bool
    {
        return in_array($status, [ReleaseAppModel::STATUS_ENABLE, ReleaseAppModel::STATUS_DISABLE], true);
    }

}
