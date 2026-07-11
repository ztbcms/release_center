<?php

return [
    [
        'parentid' => 0,
        'route'    => 'release_center/admin.application/index',
        'type'     => 0,
        'status'   => 1,
        'name'     => 'Release Center',
        'remark'   => '',
        'icon'     => 'el-icon-upload2',
        'child'    => [
            [
                'route'  => 'release_center/admin.application/index',
                'type'   => 1,
                'status' => 1,
                'name'   => '应用管理',
                'remark' => '',
                'child'  => [
                    ['route' => 'release_center/admin.application/getList', 'type' => 1, 'status' => 0, 'name' => '应用列表', 'remark' => '', 'child' => []],
                    ['route' => 'release_center/admin.application/add', 'type' => 1, 'status' => 0, 'name' => '新增应用', 'remark' => '', 'child' => []],
                    ['route' => 'release_center/admin.application/edit', 'type' => 1, 'status' => 0, 'name' => '编辑应用', 'remark' => '', 'child' => []],
                    ['route' => 'release_center/admin.application/changeStatus', 'type' => 1, 'status' => 0, 'name' => '应用状态管控', 'remark' => '', 'child' => []],
                ],
            ],
            [
                'route'  => 'release_center/admin.version/index',
                'type'   => 1,
                'status' => 1,
                'name'   => '版本管理',
                'remark' => '',
                'icon'   => 'el-icon-s-release',
                'child'  => [
                    ['route' => 'release_center/admin.version/getList', 'type' => 1, 'status' => 0, 'name' => '版本列表', 'remark' => '', 'child' => []],
                    ['route' => 'release_center/admin.version/getAppOptions', 'type' => 1, 'status' => 0, 'name' => '应用选项', 'remark' => '', 'child' => []],
                    ['route' => 'release_center/admin.version/add', 'type' => 1, 'status' => 0, 'name' => '发布版本', 'remark' => '', 'child' => []],
                    ['route' => 'release_center/admin.version/edit', 'type' => 1, 'status' => 0, 'name' => '编辑版本', 'remark' => '', 'child' => []],
                    ['route' => 'release_center/admin.version/changeStatus', 'type' => 1, 'status' => 0, 'name' => '版本状态管控', 'remark' => '', 'child' => []],
                    ['route' => 'release_center/admin.version/clearCache', 'type' => 1, 'status' => 0, 'name' => '清理版本缓存', 'remark' => '', 'child' => []],
                ],
            ],
        ],
    ],
];
