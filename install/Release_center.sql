CREATE TABLE IF NOT EXISTS `cms_release_center_app` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '应用名称',
  `app_key` varchar(64) NOT NULL DEFAULT '' COMMENT '应用标识',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态：1启用，2禁用',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_app_key` (`app_key`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Release Center应用';

CREATE TABLE IF NOT EXISTS `cms_release_center_version` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `app_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '应用ID',
  `version` varchar(100) NOT NULL DEFAULT '' COMMENT '展示版本号',
  `build` bigint unsigned NOT NULL DEFAULT '0' COMMENT '比较版本号',
  `download_url` varchar(2048) NOT NULL DEFAULT '' COMMENT '下载地址，可选',
  `release_note` text COMMENT '更新说明',
  `publish_time` int unsigned NOT NULL DEFAULT '0' COMMENT '首次发布时间',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态：1启用，2禁用',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_app_build` (`app_id`, `build`),
  KEY `idx_app_status_build` (`app_id`, `status`, `build`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Release Center版本';
