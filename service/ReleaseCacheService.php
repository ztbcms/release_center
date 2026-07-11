<?php

namespace app\release_center\service;

use app\release_center\model\ReleaseAppModel;
use think\facade\Cache;

/**
 * Release Center版本缓存服务
 */
class ReleaseCacheService
{
    const CACHE_PREFIX = 'release_center:latest:';
    const CACHE_TTL = 604800;

    /**
     * 构建应用版本缓存Key
     *
     * @param string $appKey 应用标识
     * @return string
     */
    public function cacheKey(string $appKey): string
    {
        return self::CACHE_PREFIX . trim($appKey);
    }

    /**
     * 获取缓存的最新版本
     *
     * @param string $appKey 应用标识
     * @return array|null
     */
    public function getLatest(string $appKey)
    {
        $value = Cache::get($this->cacheKey($appKey));
        if (!is_array($value) || empty($value['build'])) {
            return null;
        }
        return $value;
    }

    /**
     * 写入最新版本缓存
     *
     * @param string $appKey 应用标识
     * @param array $version 公开版本数据
     * @return void
     */
    public function setLatest(string $appKey, array $version): void
    {
        Cache::set($this->cacheKey($appKey), $version, self::CACHE_TTL);
    }

    /**
     * 删除指定应用版本缓存
     *
     * @param string $appKey 应用标识
     * @return void
     */
    public function deleteLatest(string $appKey): void
    {
        Cache::delete($this->cacheKey($appKey));
    }

    /**
     * 清理全部Release Center版本缓存
     *
     * @return int 已处理应用数量
     */
    public function clearAll(): int
    {
        $appKeys = (new ReleaseAppModel())->column('app_key');
        foreach ($appKeys as $appKey) {
            $this->deleteLatest((string)$appKey);
        }
        return count($appKeys);
    }
}
