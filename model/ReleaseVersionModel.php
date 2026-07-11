<?php

namespace app\release_center\model;

use think\Model;

/**
 * Release Center版本模型
 */
class ReleaseVersionModel extends Model
{
    protected $name = 'release_center_version';
    protected $autoWriteTimestamp = false;

    const STATUS_ENABLE = 1;
    const STATUS_DISABLE = 2;
}
