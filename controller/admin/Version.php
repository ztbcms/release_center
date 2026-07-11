<?php

namespace app\release_center\controller\admin;

use app\common\controller\AdminController;
use app\release_center\model\ReleaseAppModel;
use app\release_center\model\ReleaseVersionModel;
use app\release_center\service\ReleaseCacheService;
use app\release_center\service\ReleaseCenterService;
use think\facade\View;

/**
 * Release Center版本管理
 */
class Version extends AdminController
{
    /**
     * 渲染版本管理页面
     *
     * @return string
     */
    public function index()
    {
        return View::fetch('index');
    }

    /**
     * 获取版本列表
     *
     * @return \think\response\Json
     */
    public function getList()
    {
        $page = max(1, intval($this->request->param('page', 1)));
        $limit = max(1, min(100, intval($this->request->param('limit', 20))));
        $query = new ReleaseVersionModel();
        $appId = intval($this->request->param('app_id', 0));
        $status = intval($this->request->param('status', 0));
        $version = trim((string)$this->request->param('version', ''));
        $build = trim((string)$this->request->param('build', ''));
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        if (in_array($status, [ReleaseVersionModel::STATUS_ENABLE, ReleaseVersionModel::STATUS_DISABLE], true)) {
            $query->where('status', $status);
        }
        if ($version !== '') {
            $query->whereLike('version', '%' . $version . '%');
        }
        if ($build !== '' && ctype_digit($build)) {
            $query->where('build', intval($build));
        }
        $total = (clone $query)->count();
        $items = $query->order('publish_time desc')->order('id desc')->page($page, $limit)->select()->toArray();
        $appMap = $this->getAppMap();
        foreach ($items as &$item) {
            $item = $this->formatItem($item, $appMap[intval($item['app_id'] ?? 0)] ?? null);
        }
        return json(self::createReturn(true, ['items' => $items, 'total' => $total]));
    }

    /**
     * 获取应用选择项
     *
     * @return \think\response\Json
     */
    public function getAppOptions()
    {
        $items = (new ReleaseAppModel())->order('id desc')->select()->toArray();
        $service = new ReleaseCenterService();
        foreach ($items as &$item) {
            $item = [
                'id' => intval($item['id']),
                'name' => (string)$item['name'],
                'app_key' => (string)$item['app_key'],
                'status' => intval($item['status']),
                'max_build' => $service->getMaxBuild(intval($item['id'])),
            ];
        }
        return json(self::createReturn(true, ['items' => $items]));
    }

    /**
     * 发布版本
     *
     * @return \think\response\Json
     */
    public function add()
    {
        if (!$this->request->isPost()) {
            return json(self::createReturn(false, [], '请求方式错误'));
        }
        return json((new ReleaseCenterService())->publishVersion($this->request->post()));
    }

    /**
     * 编辑版本
     *
     * @return \think\response\Json
     */
    public function edit()
    {
        if (!$this->request->isPost()) {
            return json(self::createReturn(false, [], '请求方式错误'));
        }
        $data = $this->request->post();
        return json((new ReleaseCenterService())->updateVersion(intval($data['id'] ?? 0), $data));
    }

    /**
     * 撤回或恢复版本
     *
     * @return \think\response\Json
     */
    public function changeStatus()
    {
        if (!$this->request->isPost()) {
            return json(self::createReturn(false, [], '请求方式错误'));
        }
        return json((new ReleaseCenterService())->changeVersionStatus(
            intval($this->request->post('id', 0)),
            intval($this->request->post('status', 0))
        ));
    }

    /**
     * 清理全部版本缓存
     *
     * @return \think\response\Json
     */
    public function clearCache()
    {
        if (!$this->request->isPost()) {
            return json(self::createReturn(false, [], '请求方式错误'));
        }
        $count = (new ReleaseCacheService())->clearAll();
        return json(self::createReturn(true, ['app_count' => $count], '已清理 ' . $count . ' 个应用的版本缓存'));
    }

    /**
     * 获取应用映射
     *
     * @return array
     */
    private function getAppMap(): array
    {
        $rows = (new ReleaseAppModel())->select()->toArray();
        $map = [];
        foreach ($rows as $row) {
            $map[intval($row['id'])] = $row;
        }
        return $map;
    }

    /**
     * 格式化版本列表记录
     *
     * @param array $item 版本记录
     * @param array|null $app 应用记录
     * @return array
     */
    private function formatItem(array $item, $app): array
    {
        $item['id'] = intval($item['id'] ?? 0);
        $item['app_id'] = intval($item['app_id'] ?? 0);
        $item['build'] = intval($item['build'] ?? 0);
        $item['status'] = intval($item['status'] ?? 0);
        $item['app_name'] = (string)($app['name'] ?? '应用已不存在');
        $item['app_key'] = (string)($app['app_key'] ?? '');
        $item['status_text'] = $item['status'] === ReleaseVersionModel::STATUS_ENABLE ? '启用' : '已撤回';
        $item['publish_time_text'] = !empty($item['publish_time']) ? date('Y-m-d H:i', intval($item['publish_time'])) : '';
        return $item;
    }
}
