-- 共享库 + tenant_id 最小 Schema
-- 按需在目标库执行

CREATE TABLE IF NOT EXISTS `tenants` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '租户名称',
  `code` varchar(64) NOT NULL DEFAULT '' COMMENT '租户编码（唯一）',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '1启用 0禁用',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tenants_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='租户';

-- 示例业务表：accounts（已有表则 ALTER 增加 tenant_id）
CREATE TABLE IF NOT EXISTS `accounts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL COMMENT '租户ID',
  `business_id` bigint unsigned NOT NULL DEFAULT 0 COMMENT '业务线/BM等，串行队列用',
  `name` varchar(100) NOT NULL DEFAULT '',
  `status` tinyint NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_accounts_tenant` (`tenant_id`),
  KEY `idx_accounts_tenant_business` (`tenant_id`, `business_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='账号示例表';

-- 已有 accounts 表时用：
-- ALTER TABLE `accounts`
--   ADD COLUMN `tenant_id` bigint unsigned NOT NULL DEFAULT 0 COMMENT '租户ID' AFTER `id`,
--   ADD COLUMN `business_id` bigint unsigned NOT NULL DEFAULT 0 COMMENT '业务线' AFTER `tenant_id`,
--   ADD KEY `idx_accounts_tenant` (`tenant_id`),
--   ADD KEY `idx_accounts_tenant_business` (`tenant_id`, `business_id`);
