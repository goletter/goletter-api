#!/bin/bash
# -------------------------------------------------------------------
# 🚀 wewall-cluster 工业级 100% 零闪断热更新脚本 (终极闭环版)
# -------------------------------------------------------------------
set -e # 遇到任何一个命令出错，立即停止执行

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# 检测是否传入了 --build 参数
NEED_BUILD=false
UP_OPTIONS="--force-recreate" # 💡 默认参数：仅强制重建容器

if [ "$1" == "--build" ]; then
    NEED_BUILD=true
    # 💡【核心修改】：当有 --build 时，额外追加 -V 参数，强制刷新 Docker 匿名卷缓存
    UP_OPTIONS="--force-recreate --renew-anon-volumes"
fi

echo "========================================================="
echo "🚀 开始执行 100% 零闪断热更新流程..."
echo "========================================================="

# 1. 判断是否需要构建最新镜像
if [ "$NEED_BUILD" = true ] ; then
    echo "📦 [Step 1] 检测到 --build 参数，开始全新构建代码镜像..."
    docker compose build app_a

    # 清理宿主机的旧 runtime 缓存
    if [ -d "../runtime" ]; then
        echo "🧹 [Step 1.1] 检测到依赖变更，正在清除宿主机旧的 runtime 代理缓存..."
        rm -rf ../runtime/*
    fi
else
    echo "⏩ [Step 1] 仅修改业务代码，跳过镜像构建，将直接挂载最新代码热重启..."
fi

# 定义智能健康状态检查函数
wait_for_healthy() {
    local service_name=$1
    echo "⏳ 等待 $service_name 彻底通过 Docker 健康检查..."

    for i in {1..30}; do
        CONTAINER_ID=$(docker compose ps -q "$service_name")
        if [ -z "$CONTAINER_ID" ]; then
            echo "❌ 错误: 找不到 $service_name 的容器实例！"
            exit 1
        fi

        STATUS=$(docker inspect --format='{{.State.Health.Status}}' "$CONTAINER_ID" 2>/dev/null || echo "failed")

        if [ "$STATUS" == "healthy" ]; then
            echo "✅ $service_name 已完全就绪（Healthy）！"
            return 0
        elif [ "$STATUS" == "unhealthy" ]; then
            echo "❌ 错误: $service_name 健康检查判定为失败（Unhealthy），正在终止更新！"
            exit 1
        fi
        sleep 2
    done

    echo "❌ 错误: $service_name 启动超时，未能在规定时间内通过健康检查！"
    exit 1
}

# 2. 更新备份节点 app_b
echo "🔄 [Step 2] 正在重启备份节点 app_b..."
# 💡【核心落地】：应用动态参数。日常只加 --force-recreate；build时会带上 --renew-anon-volumes 强刷 vendor 卷！
docker compose up -d $UP_OPTIONS app_b
wait_for_healthy "app_b"

# 3. 更新主节点 app_a
echo "🔄 [Step 3] 正在重启主节点 app_a..."
# 💡【核心落地】：同理，强刷主节点卷缓存
docker compose up -d $UP_OPTIONS app_a
wait_for_healthy "app_a"

# 4. 热重载 Nginx
echo "🔄 [Step 4] 正在热重载 Nginx 配置..."
docker compose exec -T nginx nginx -s reload

# 5. 如果执行了 build，则自动回收磁盘垃圾
if [ "$NEED_BUILD" = true ] ; then
    echo "🧹 [Step 5] 正在回收旧镜像与构建缓存..."
    docker builder prune -f
    docker image prune -f
fi

echo "========================================================="
echo "🎉 零停机平滑更新完全成功！所有缓存已冲刷完毕。"
echo "========================================================="