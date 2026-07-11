<?php

namespace app\release_center\controller\admin;

use app\common\controller\AdminController;
use app\release_center\model\ReleaseAppModel;
use app\release_center\service\ReleaseCenterService;
use think\facade\View;

/**
 * Release Center应用管理
 */
class Application extends AdminController
{
    /**
     * 渲染应用管理页面
     *
     * @return string
     */
    public function index()
    {
        return View::fetch('index');
    }

    /**
     * 获取应用列表
     *
     * @return \think\response\Json
     */
    public function getList()
    {
        $page = max(1, intval($this->request->param('page', 1)));
        $limit = max(1, min(100, intval($this->request->param('limit', 20))));
        $query = new ReleaseAppModel();
        $name = trim((string)$this->request->param('name', ''));
        $appKey = trim((string)$this->request->param('app_key', ''));
        $status = intval($this->request->param('status', 0));
        if ($name !== '') {
            $query->whereLike('name', '%' . $name . '%');
        }
        if ($appKey !== '') {
            $query->whereLike('app_key', '%' . $appKey . '%');
        }
        if (in_array($status, [ReleaseAppModel::STATUS_ENABLE, ReleaseAppModel::STATUS_DISABLE], true)) {
            $query->where('status', $status);
        }
        $total = (clone $query)->count();
        $items = $query->order('id desc')->page($page, $limit)->select()->toArray();
        foreach ($items as &$item) {
            $item = $this->formatItem($item);
        }
        return json(self::createReturn(true, ['items' => $items, 'total' => $total]));
    }

    /**
     * 新增应用
     *
     * @return \think\response\Json
     */
    public function add()
    {
        if (!$this->request->isPost()) {
            return json(self::createReturn(false, [], '请求方式错误'));
        }
        return json((new ReleaseCenterService())->createApplication($this->request->post()));
    }

    /**
     * 编辑应用
     *
     * @return \think\response\Json
     */
    public function edit()
    {
        if (!$this->request->isPost()) {
            return json(self::createReturn(false, [], '请求方式错误'));
        }
        $data = $this->request->post();
        return json((new ReleaseCenterService())->updateApplication(intval($data['id'] ?? 0), $data));
    }

    /**
     * 修改应用状态
     *
     * @return \think\response\Json
     */
    public function changeStatus()
    {
        if (!$this->request->isPost()) {
            return json(self::createReturn(false, [], '请求方式错误'));
        }
        return json((new ReleaseCenterService())->changeApplicationStatus(
            intval($this->request->post('id', 0)),
            intval($this->request->post('status', 0))
        ));
    }

    /**
     * 格式化应用列表记录
     *
     * @param array $item 应用记录
     * @return array
     */
    private function formatItem(array $item): array
    {
        $item['id'] = intval($item['id'] ?? 0);
        $item['status'] = intval($item['status'] ?? 0);
        $item['status_text'] = $item['status'] === ReleaseAppModel::STATUS_ENABLE ? '启用' : '禁用';
        $item['create_time_text'] = !empty($item['create_time']) ? date('Y-m-d H:i', intval($item['create_time'])) : '';
        $item['update_time_text'] = !empty($item['update_time']) ? date('Y-m-d H:i', intval($item['update_time'])) : '';
        return $item;
    }
}
