-- 租户表
tenants
id              bigint PK
biz_id          varchar(64) UNIQUE      -- 对外租户号（雪花ID）100000
code            varchar(64) UNIQUE      -- 内部租户号  （DT100000）
prefix          varchar(64) UNIQUE      -- 租户号前缀(用于公司编号拼接) DT
name            varchar(128)            -- 租户名称
username        varchar(128)            -- 租户登录账户名
username        varchar(128)            -- 租户登录密码
base_uri        varchar(255) NULL       -- 租户服务地址
status          tinyint                 -- 1启用 0停用
created_at, updated_at, deleted_at

--子模块表
modules
id
code            varchar(64) UNIQUE      -- goletter-api / billing-api
name
base_uri        varchar(255) NULL       -- 子模块服务地址
status          tinyint                 -- 上下架
created_at
updated_at

--租户与子模块关系
tenant_modules
id
biz_id
package_id
tenant_id 
module_id
start_at
end_at
created_at
updated_at

--套餐表
packages
id
biz_id
duration_type  --时长类型（按日、按周、按月、按年）
duration   --时长
price  --价格
status  --状态
created_at
updated_at

--订单表
orders
id
biz_id
order_no
price



--菜单表
menus

--平台管理员表
admin_users

--权限表
permissions
id
biz_id
tenant_id

--角色表
roles

--公司表
companies
id              bigint PK
code            varchar(64) UNIQUE      --公司编号  （租户号前缀+雪花ID）