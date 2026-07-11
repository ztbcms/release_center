<?php

namespace app\release_center\model;

use think\Model;

/**
 * Release Center应用模型
 */
class ReleaseAppModel extends Model
{
    protected $name = 'release_center_app';
    protected $autoWriteTimestamp = false;

    const STATUS_ENABLE = 1;
    const STATUS_DISABLE = 2;
}
