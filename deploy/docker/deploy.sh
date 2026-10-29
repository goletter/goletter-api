#!/usr/bin/env bash
# -------------------------------------------------------------------
# 零闪断热更新：先 app_b → 等 healthy → 再 app_a → nginx reload
# 用法:
#   bash deploy/docker/deploy.sh
#   bash deploy/docker/deploy.sh --build
# -------------------------------------------------------------------
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "${SCRIPT_DIR}/../.." && pwd)"
COMPOSE_FILE="${SCRIPT_DIR}/docker-compose.yml"
RUNTIME_DIR="${ROOT_DIR}/runtime"
DC=(docker compose -f "${COMPOSE_FILE}")

cd "${SCRIPT_DIR}"

NEED_BUILD=false
UP_ARGS=(--force-recreate)

if [[ "${1:-}" == '--build' ]]; then
    NEED_BUILD=true
    UP_ARGS+=(--renew-anon-volumes)
fi

log() { printf '%s\n' "$*"; }
die() { printf '错误: %s\n' "$*" >&2; exit 1; }

command -v docker >/dev/null 2>&1 || die '未找到 docker'
docker compose version >/dev/null 2>&1 || die '当前 docker 不支持 compose 插件'
[[ -f "${COMPOSE_FILE}" ]] || die "找不到 ${COMPOSE_FILE}"

wait_for_healthy() {
    local service_name=$1
    local container_id status
    log "等待 ${service_name} 健康检查通过..."

    for _ in $(seq 1 30); do
        container_id="$("${DC[@]}" ps -q "${service_name}" 2>/dev/null || true)"
        [[ -n "${container_id}" ]] || die "找不到 ${service_name} 容器"

        status="$(docker inspect --format='{{if .State.Health}}{{.State.Health.Status}}{{else}}{{.State.Status}}{{end}}' "${container_id}" 2>/dev/null || echo failed)"

        case "${status}" in
            healthy)
                log "${service_name} 已就绪 (healthy)"
                return 0
                ;;
            unhealthy)
                die "${service_name} 健康检查失败 (unhealthy)"
                ;;
        esac
        sleep 2
    done

    die "${service_name} 启动超时"
}

log '========================================================='
log '开始热更新'
log '========================================================='

if [[ "${NEED_BUILD}" == true ]]; then
    log '[1/5] 构建镜像...'
    "${DC[@]}" build app_a

    if [[ -d "${RUNTIME_DIR}" ]]; then
        log '[1.1] 清理宿主机 runtime 缓存...'
        # 只清容器代理缓存目录，保留按节点拆分的 logs
        find "${RUNTIME_DIR}" -mindepth 1 -maxdepth 1 \
            ! -name 'app_a' ! -name 'app_b' \
            -exec rm -rf {} +
    fi
else
    log '[1/5] 跳过构建（代码 volume 挂载）'
fi

log '[2/5] 重启备份节点 app_b...'
"${DC[@]}" up -d "${UP_ARGS[@]}" app_b
wait_for_healthy app_b

log '[3/5] 重启主节点 app_a...'
"${DC[@]}" up -d "${UP_ARGS[@]}" app_a
wait_for_healthy app_a

log '[4/5] 热重载 Nginx...'
"${DC[@]}" exec -T nginx nginx -s reload

if [[ "${NEED_BUILD}" == true ]]; then
    log '[5/5] 回收旧镜像与构建缓存...'
    docker builder prune -f >/dev/null
    docker image prune -f >/dev/null
else
    log '[5/5] 跳过镜像回收'
fi

log '========================================================='
log '热更新完成'
log '========================================================='
