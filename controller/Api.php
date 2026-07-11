<?php

namespace app\release_center\controller;

use app\api\controller\BaseApi;
use app\release_center\service\ReleaseCenterService;

/**
 * Release Center公开接口
 */
class Api extends BaseApi
{
    protected $skipAuthActions = ['check'];

    protected $apiRateLimit = [
        'enabled' => true,
        'actions' => ['check'],
        'except' => [],
        'max_requests' => 120,
        'decay_seconds' => 60,
        'rules' => [],
    ];

    /**
     * 检查客户端版本更新
     *
     * @return \think\response\Json
     */
    public function check()
    {
        if (!$this->request->isGet()) {
            return self::returnErrorJson('请求方式错误', [], 400);
        }
        $appKey = trim((string)$this->request->get('app_key', ''));
        $currentBuild = (new ReleaseCenterService())->normalizeBuild($this->request->get('current_build', null), true);
        $service = new ReleaseCenterService();
        if (!$service->isValidAppKey($appKey)) {
            return self::returnErrorJson('app_key 参数不正确', [], 400);
        }
        if (!$currentBuild['status']) {
            return self::returnErrorJson($currentBuild['msg'], [], 400);
        }
        $result = $service->checkUpdate($appKey, intval($currentBuild['value']));
        if (!$result['status']) {
            return self::returnErrorJson($result['msg'], [], $result['code']);
        }
        return self::returnSuccessJson($result['data']);
    }
}
