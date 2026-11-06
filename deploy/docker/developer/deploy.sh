#!/usr/bin/env bash
# ===================================================================
# wewall-cluster 部署脚本（v3）
#
# 用法:
#   ./deploy.sh                          增量更新：不 build，仅重建容器
#   ./deploy.sh --build                  构建镜像并更新
#   ./deploy.sh --build --pull           构建时拉取最新基础镜像
#   ./deploy.sh --pull                   仅拉取镜像并重建容器（up 加 --pull=always）
#   ./deploy.sh --init                   初始部署：构建 + 全量启动 + 全量健康检查
#   ./deploy.sh --check                  仅预检：docker/compose/.env/配置解析，不做变更
#   ./deploy.sh --init --offline         初始部署（离线，不拉镜像）
#   ./deploy.sh --init --clean           初始部署前先 down 旧环境
#   ./deploy.sh --init --yes             非交互确认（已存在 .deploy-state.json 时）
#   ./deploy.sh --build --renew-volumes  构建时重建匿名卷（危险，默认保留）
#   ./deploy.sh --keep-volumes           兼容旧参数（等价于 --renew-volumes=false）
#   ./deploy.sh --no-rollback            失败时不自动回滚
#   ./deploy.sh --project myproj         指定 compose project name
#   ./deploy.sh -h|--help                显示帮助
#
# 环境变量:
#   HEALTH_TIMEOUT            单服务健康检查超时秒数（默认 180）
#   HEALTH_INTERVAL           轮询间隔秒数（默认 2）
#   HEALTH_INITIAL_WAIT       健康检查前初始等待秒数（默认 3，防 recreate 竞态）
#   LOG_FILE                  日志路径（默认 <script_dir>/deploy.log）
#   LOG_MAX_BYTES             日志轮转阈值（默认 10485760 = 10MB）
#   WEBHOOK_URL               成功/失败通知 webhook（JSON POST）
#   COMPOSE_PROJECT           compose project name（等同 --project）
#   ONESHOT_SERVICES          一次性任务服务列表（如 "migrate seed"）
#   ALLOW_EXIT_ZERO_SERVICES  允许 exit 0 视为成功的额外服务列表
#   INIT_HOOK                 init 模式下执行的初始化命令/脚本
#   INFRA_SERVICES            deploy 模式先启动的基础设施（默认 "redis rabbitmq mysql"）
#   ROLLBACK_ON_FAILURE       失败时自动回滚（默认 true）
#   SKIP_PORT_CHECK           跳过端口冲突检查（默认 false）
#   DATA_READY_CHECK          init 模式数据就绪探针开关（默认 true）
# ===================================================================
# 常用示例
## 新机器自检（不构建、不启动，只验证环境与配置）
#./deploy.sh --check
#
## 首次基座部署（在线）
#./deploy.sh --init --project wewall
#
## 首次基座部署（离线）
#./deploy.sh --init --offline --project wewall
#
## 已存在部署状态，非交互确认
#./deploy.sh --init --yes
#
## 常规增量更新（保留匿名卷）
#./deploy.sh
#
## 拉取镜像 + 使用新镜像重建
#./deploy.sh --pull

set -euo pipefail

# ---------- 全局配置 ----------
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

HEALTH_TIMEOUT="${HEALTH_TIMEOUT:-180}"
HEALTH_INTERVAL="${HEALTH_INTERVAL:-2}"
HEALTH_INITIAL_WAIT="${HEALTH_INITIAL_WAIT:-3}"
LOG_FILE="${LOG_FILE:-$SCRIPT_DIR/deploy.log}"
LOG_MAX_BYTES="${LOG_MAX_BYTES:-10485760}"
WEBHOOK_URL="${WEBHOOK_URL:-}"
ONESHOT_SERVICES="${ONESHOT_SERVICES:-}"
ALLOW_EXIT_ZERO_SERVICES="${ALLOW_EXIT_ZERO_SERVICES:-}"
INIT_HOOK="${INIT_HOOK:-}"
ROLLBACK_ON_FAILURE="${ROLLBACK_ON_FAILURE:-true}"
SKIP_PORT_CHECK="${SKIP_PORT_CHECK:-false}"
DATA_READY_CHECK="${DATA_READY_CHECK:-true}"
INFRA_SERVICES="${INFRA_SERVICES:-redis rabbitmq mysql}"
STATE_FILE="$SCRIPT_DIR/.deploy-state.json"

# ---------- 参数（while 循环，正确支持 shift） ----------
MODE="deploy"                 # deploy | init
CHECK_ONLY=false              # --check：仅预检，不做任何变更
NEED_BUILD=false
NEED_PULL=false
RENEW_VOLUMES=false           # 默认保留匿名卷（安全）
OFFLINE=false
CLEAN=false
ASSUME_YES=false
COMPOSE_PROJECT="${COMPOSE_PROJECT:-}"

usage() {
    cat <<'EOF'
用法:
  ./deploy.sh                          增量更新：不 build，仅重建容器
  ./deploy.sh --build                  构建镜像并更新
  ./deploy.sh --build --pull           构建时拉取最新基础镜像
  ./deploy.sh --pull                   仅拉取镜像并重建容器
  ./deploy.sh --init                   初始部署：构建 + 全量启动 + 全量健康检查
  ./deploy.sh --check                  仅预检：docker/compose/.env/配置解析，不做变更
  ./deploy.sh --init --offline         初始部署（离线，不拉镜像）
  ./deploy.sh --init --clean           初始部署前先 down 旧环境
  ./deploy.sh --init --yes             非交互确认
  ./deploy.sh --build --renew-volumes  构建时重建匿名卷（危险，默认保留）
  ./deploy.sh --keep-volumes           兼容旧参数（默认已保留匿名卷）
  ./deploy.sh --no-rollback            失败时不自动回滚
  ./deploy.sh --project myproj         指定 compose project name
  ./deploy.sh -h|--help                显示帮助
EOF
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --build)          NEED_BUILD=true; shift ;;
        --pull)           NEED_PULL=true; shift ;;
        --check)          CHECK_ONLY=true; shift ;;
        --init)           MODE="init"; NEED_BUILD=true; NEED_PULL=true; shift ;;
        --offline)        OFFLINE=true; shift ;;
        --clean)          CLEAN=true; shift ;;
        --renew-volumes)  RENEW_VOLUMES=true; shift ;;
        --keep-volumes)   RENEW_VOLUMES=false; shift ;;
        --no-rollback)    ROLLBACK_ON_FAILURE=false; shift ;;
        --yes|-y)         ASSUME_YES=true; shift ;;
        --project)
            if [ -z "${2:-}" ]; then echo "❌ --project 需要参数"; exit 1; fi
            COMPOSE_PROJECT="$2"; shift 2 ;;
        --project=*)      COMPOSE_PROJECT="${1#*=}"; shift ;;
        -h|--help)        usage; exit 0 ;;
        *) echo "❌ 未知参数: $1"; usage; exit 1 ;;
    esac
done

if [ "$OFFLINE" = true ]; then
    NEED_PULL=false
fi

# 显式 project name，避免 --remove-orphans 误删其他 stack。
# 默认与 docker-compose.yml 顶层 name 保持一致（不一致会导致"脚本建的 stack"与
# "手工 docker compose 命令看到的 stack"分叉成两份）。
if [ -n "$COMPOSE_PROJECT" ]; then
    export COMPOSE_PROJECT_NAME="$COMPOSE_PROJECT"
elif [ -z "${COMPOSE_PROJECT_NAME:-}" ]; then
    COMPOSE_PROJECT_NAME="$(sed -n 's/^name:[[:space:]]*//p' "$SCRIPT_DIR/docker-compose.yml" 2>/dev/null | head -n1 || true)"
    export COMPOSE_PROJECT_NAME="${COMPOSE_PROJECT_NAME:-hyperf-base}"
fi

# ---------- 仓库根 .env 引导（compose 凭据只读根 .env：AMQP_PASSWORD / REDIS_AUTH / DB_PASSWORD） ----------
ROOT_ENV_FILE="$(cd "$SCRIPT_DIR/../.." && pwd)/.env"
ROOT_ENV_EXAMPLE="$ROOT_ENV_FILE.example"

gen_password() {
    if command -v openssl >/dev/null 2>&1; then
        openssl rand -hex 16
    else
        LC_ALL=C tr -dc 'a-f0-9' </dev/urandom 2>/dev/null | head -c 32 || true
    fi
}

ensure_root_env_file() {
    [ -f "$ROOT_ENV_FILE" ] && return 0
    if [ ! -f "$ROOT_ENV_EXAMPLE" ]; then
        echo "❌ 缺少仓库根 .env，且无 $ROOT_ENV_EXAMPLE 模板"
        exit 1
    fi
    local rp dp dbp
    rp="$(gen_password)"
    dp="$(gen_password)"
    dbp="$(gen_password)"
    awk -v rp="$rp" -v dp="$dp" -v dbp="$dbp" '
        /^AMQP_PASSWORD=/ { print "AMQP_PASSWORD=" rp; next }
        /^REDIS_AUTH=/ { print "REDIS_AUTH=" dp; next }
        /^DB_PASSWORD=/ { print "DB_PASSWORD=" dbp; next }
        { print }
    ' "$ROOT_ENV_EXAMPLE" > "$ROOT_ENV_FILE"
    chmod 600 "$ROOT_ENV_FILE" 2>/dev/null || true
    echo "🔑 首次部署：已按模板生成 $ROOT_ENV_FILE（AMQP / Redis / MySQL 随机密码）"
}

ensure_root_env_file

compose() {
    (
        cd "$SCRIPT_DIR"
        docker compose "$@"
    )
}

# ---------- 并发锁（跨平台：mkdir 原子锁；Git Bash / macOS 默认没有 flock） ----------
LOCK_DIR="$SCRIPT_DIR/.deploy.lock.d"
LOCK_PID_FILE="$LOCK_DIR/pid"
if ! mkdir "$LOCK_DIR" 2>/dev/null; then
    holder="$(cat "$LOCK_PID_FILE" 2>/dev/null || true)"
    if [ -n "$holder" ] && kill -0 "$holder" 2>/dev/null; then
        echo "❌ 另一个部署正在进行（PID $holder，锁: $LOCK_DIR），退出。"
        exit 1
    fi
    echo "⚠️  检测到陈旧部署锁（持有者 PID ${holder:-未知} 已不存在），清理后继续"
    rm -rf "$LOCK_DIR" 2>/dev/null || true
    mkdir "$LOCK_DIR" 2>/dev/null || { echo "❌ 无法获取部署锁（$LOCK_DIR），退出。"; exit 1; }
fi
echo $$ > "$LOCK_PID_FILE" 2>/dev/null || true
release_lock() { rm -rf "$LOCK_DIR" 2>/dev/null || true; }
trap release_lock EXIT

# ---------- 日志轮转 + 重定向 ----------
mkdir -p "$(dirname "$LOG_FILE")" 2>/dev/null || true
if [ -f "$LOG_FILE" ]; then
    sz=$(stat -c%s "$LOG_FILE" 2>/dev/null || stat -f%z "$LOG_FILE" 2>/dev/null || echo 0)
    if [ "${sz:-0}" -gt "$LOG_MAX_BYTES" ]; then
        mv "$LOG_FILE" "$LOG_FILE.$(date +%Y%m%d%H%M%S)" 2>/dev/null || true
    fi
fi
exec > >(tee -a "$LOG_FILE") 2>&1

# ---------- 通知 ----------
notify() {
    local status="$1" message="$2"
    [ -n "$WEBHOOK_URL" ] || return 0
    command -v curl >/dev/null 2>&1 || { echo "⚠️  webhook 需要 curl，已跳过"; return 0; }
    local payload
    payload=$(printf '{"status":"%s","message":"%s","host":"%s","time":"%s"}' \
        "$status" "$(printf '%s' "$message" | sed 's/"/\\"/g')" \
        "$(hostname 2>/dev/null || echo unknown)" "$(date -Is 2>/dev/null || date)")
    curl -fsS --max-time 10 -X POST -H 'Content-Type: application/json' \
        -d "$payload" "$WEBHOOK_URL" >/dev/null 2>&1 \
        || echo "⚠️  webhook 通知失败"
}

# ---------- 回滚点记录（含完整 sha256） ----------
ROLLBACK_SERVICES=()
ROLLBACK_IMAGE_REFS=()
ROLLBACK_IMAGE_IDS=()

record_rollback() {
    local svc cid img_ref img_id
    for svc in $services; do
        cid=$(compose ps -q "$svc" 2>/dev/null | head -n1 || true)
        [ -z "$cid" ] && continue
        img_ref=$(docker inspect -f '{{.Config.Image}}' "$cid" 2>/dev/null || true)
        img_id=$(docker inspect -f '{{.Id}}' "$cid" 2>/dev/null || true)   # 完整 sha256
        [ -z "$img_ref" ] && continue
        [ -z "$img_id" ] && continue
        ROLLBACK_SERVICES+=("$svc")
        ROLLBACK_IMAGE_REFS+=("$img_ref")
        ROLLBACK_IMAGE_IDS+=("$img_id")
    done
    if [ "${#ROLLBACK_SERVICES[@]}" -gt 0 ]; then
        echo "💾 已记录 ${#ROLLBACK_SERVICES[@]} 个回滚点"
    fi
}

do_rollback() {
    [ "$ROLLBACK_ON_FAILURE" = true ] || { echo "⏭️  ROLLBACK_ON_FAILURE=false，跳过回滚"; return 0; }
    if [ "${#ROLLBACK_SERVICES[@]}" -eq 0 ]; then
        echo "⚠️  无可回滚的旧镜像，跳过"
        return 0
    fi
    echo "🔙 尝试回滚到部署前镜像..."
    local eligible=()
    local i svc ref id
    for i in "${!ROLLBACK_SERVICES[@]}"; do
        svc="${ROLLBACK_SERVICES[$i]}"
        ref="${ROLLBACK_IMAGE_REFS[$i]}"
        id="${ROLLBACK_IMAGE_IDS[$i]}"
        if ! docker image inspect "$id" >/dev/null 2>&1; then
            echo "⚠️  $svc 的旧镜像 $id 已被清理，无法自动回滚该服务"
            continue
        fi
        echo "   $svc -> $ref ($id)"
        if ! docker tag "$id" "$ref" 2>/dev/null; then
            echo "⚠️  $svc 打标签失败，跳过"
            continue
        fi
        eligible+=("$svc")
    done
    if [ "${#eligible[@]}" -eq 0 ]; then
        echo "❌ 没有可回滚的服务（旧镜像均不可用）"
        return 1
    fi
    if compose up -d --no-build --force-recreate "${eligible[@]}" 2>&1; then
        echo "✅ 回滚完成（${#eligible[@]} 个服务）"
        return 0
    else
        echo "❌ 回滚启动失败，请手动检查"
        return 1
    fi
}

# ---------- 部署状态 ----------
write_state() {
    local tmp="$STATE_FILE.tmp.$$"
    {
        printf '{\n'
        printf '  "version": "%s",\n' "$(git rev-parse --short HEAD 2>/dev/null || echo unknown)"
        printf '  "deployed_at": "%s",\n' "$(date -Is 2>/dev/null || date)"
        printf '  "mode": "%s",\n' "$MODE"
        printf '  "project": "%s",\n' "$COMPOSE_PROJECT_NAME"
        printf '  "script_version": "v3"\n'
        printf '}\n'
    } > "$tmp" && mv "$tmp" "$STATE_FILE"
    echo "📝 部署状态已写入 $STATE_FILE"
}

# ---------- 失败诊断 ----------
on_error() {
    local code=$?
    set +e
    echo "========================================================="
    echo "❌ 部署失败（退出码 $code），当前容器状态："
    compose ps || true
    echo "💡 排查: docker compose logs --tail=100 <service>"
    echo "💡 日志: $LOG_FILE"
    echo "========================================================="
    do_rollback
    notify "failed" "部署失败（exit=$code）"
    exit "$code"
}
trap on_error ERR

# ---------- 基础检查 ----------
require_cmd() {
    command -v "$1" >/dev/null 2>&1 || { echo "❌ 缺少命令: $1"; exit 1; }
}
require_cmd docker
# 分层诊断：CLI 可执行 → compose 插件 → daemon 连接（三者失败原因与处置不同）
docker --version >/dev/null 2>&1 || {
    echo "❌ docker 命令不可执行（若使用 Docker Desktop，请先启动 Docker Desktop）"
    exit 1
}
docker compose version >/dev/null 2>&1 || {
    echo "❌ docker compose v2 不可用"
    echo "   Docker Desktop：请确认已启动（WSL 里的 docker 依赖 Desktop 运行）"
    echo "   原生 Docker Engine：需安装 docker-compose-plugin"
    exit 1
}
docker info >/dev/null 2>&1 || { echo "❌ 无法连接 Docker daemon（未启动或无权限）"; exit 1; }

# 注意：--wait（compose v2.1.1+）与 --wait-timeout（compose v2.20+）是两个独立能力，
# 而下面的 up 会同时传这两个标志——必须都支持才走快路径，否则旧版 compose
# （如 v2.15）会报 "unknown flag: --wait-timeout" 直接部署失败。
HAS_COMPOSE_WAIT=false
compose up --help 2>&1 | grep -q -- '--wait-timeout' && HAS_COMPOSE_WAIT=true || true

HAS_JQ=false
command -v jq >/dev/null 2>&1 && HAS_JQ=true || true

HAS_JSON=false
compose config --format json >/dev/null 2>&1 && HAS_JSON=true || true

echo "========================================================="
echo "🚀 模式: $MODE | build=$NEED_BUILD | pull=$NEED_PULL | renew_volumes=$RENEW_VOLUMES"
echo "   project=$COMPOSE_PROJECT_NAME | rollback=$ROLLBACK_ON_FAILURE | offline=$OFFLINE"
echo "========================================================="

# ---------- 预检查 ----------
check_disk() {
    local pct
    pct=$(df -P "$SCRIPT_DIR" 2>/dev/null | awk 'NR==2{gsub("%","",$5); print $5}')
    if [ -n "${pct:-}" ] && [ "$pct" -ge 90 ]; then
        echo "⚠️  磁盘使用率 ${pct}%，建议清理后再部署"
    fi
    return 0
}

port_in_use() {
    local port="$1"
    if command -v ss >/dev/null 2>&1; then
        ss -tln 2>/dev/null | awk 'NR>1{print $4}' | grep -qE "[:.]${port}$"
    elif command -v netstat >/dev/null 2>&1; then
        netstat -tln 2>/dev/null | awk 'NR>2{print $4}' | grep -qE "[:.]${port}$"
    else
        return 1
    fi
}

check_ports() {
    [ "$SKIP_PORT_CHECK" = true ] && return 0
    [ "$HAS_JQ" = true ] && [ "$HAS_JSON" = true ] || return 0
    local conflicts
    conflicts=$(compose config --format json 2>/dev/null | jq -r '
        .services | to_entries[] | .key as $s | .value.ports[]?
        | select(.published != null) | "\(.published) \($s)"
    ' 2>/dev/null || true)
    [ -z "$conflicts" ] && return 0
    local port svc
    while read -r port svc; do
        [ -z "$port" ] && continue
        if port_in_use "$port"; then
            if docker ps --format '{{.Ports}}' 2>/dev/null | grep -q ":${port}->"; then
                echo "ℹ️  端口 $port（$svc）已被容器占用（可能是本 stack）"
            else
                echo "⚠️  端口 $port（$svc）已被非 Docker 进程占用"
            fi
        fi
    done <<< "$conflicts"
    return 0
}

check_registry_dns() {
    [ "$NEED_PULL" = true ] || return 0
    [ "$HAS_JQ" = true ] && [ "$HAS_JSON" = true ] || return 0
    if ! command -v getent >/dev/null 2>&1; then
        echo "ℹ️  当前环境缺少 getent，跳过镜像仓库 DNS 预检"
        return 0
    fi
    local hosts
    hosts=$(compose config --format json 2>/dev/null | jq -r '.services[].image // empty' 2>/dev/null \
        | awk -F/ '{if (NF>1 && $1 ~ /[.:]/) print $1; else print "docker.io"}' | sort -u)
    [ -z "$hosts" ] && return 0
    local h
    while read -r h; do
        [ -z "$h" ] && continue
        if ! getent hosts "$h" >/dev/null 2>&1; then
            echo "⚠️  无法解析镜像仓库主机: $h（如需 pull 可能失败，可用 --offline）"
        fi
    done <<< "$hosts"
    return 0
}

check_disk
check_ports
check_registry_dns

# ---------- 服务列表 ----------
services="$(compose config --services 2>/dev/null | tr -d '\r')" || {
    echo "❌ docker compose config 解析失败，compose 输出如下："
    compose config --services 2>&1 | head -20 | sed 's/^/   /' || true
    echo "   常见原因：仓库根 .env 不存在或缺少 AMQP_PASSWORD / REDIS_AUTH / DB_PASSWORD"
    echo "   （从仓库根 .env.example 复制，或在 deploy/docker 运行 ./deploy.sh --check）"
    exit 1
}
[ -n "$services" ] || { echo "❌ compose 中没有任何服务"; exit 1; }

# ---------- --check：仅预检，不做任何变更（新机器自检用） ----------
if [ "$CHECK_ONLY" = true ]; then
    echo "🔍 完整解析 compose 配置（config --quiet）..."
    if ! cfg_out="$(compose config --quiet 2>&1)"; then
        echo "❌ compose 配置解析失败："
        printf '%s\n' "$cfg_out" | sed 's/^/   /'
        exit 1
    fi
    echo "✅ 预检通过：docker / compose / .env / compose 配置均正常"
    echo "   下一步：./deploy.sh --init（首次部署）或 ./deploy.sh（增量更新）"
    exit 0
fi

has_service() { printf '%s\n' "$services" | grep -Fqx "$1"; }

is_oneshot() {
    local s="$1" o
    for o in $ONESHOT_SERVICES; do
        [ "$o" = "$s" ] && return 0
    done
    return 1
}

# 判断某服务"exit 0 是否算成功"
exit_zero_allowed_for() {
    local s="$1" o
    for o in $ONESHOT_SERVICES $ALLOW_EXIT_ZERO_SERVICES; do
        [ "$o" = "$s" ] && return 0
    done
    local cid label
    cid=$(compose ps -q "$s" 2>/dev/null | head -n1 || true)
    if [ -n "$cid" ]; then
        label=$(docker inspect -f '{{index .Config.Labels "deploy.exit_policy"}}' "$cid" 2>/dev/null || true)
        [ "$label" = "allow_zero" ] && return 0
    fi
    return 1
}

is_running() {
    local svc="$1" cid state
    cid=$(compose ps -q "$svc" 2>/dev/null | head -n1)
    [ -z "$cid" ] && return 1
    state=$(docker inspect -f '{{.State.Status}}' "$cid" 2>/dev/null || echo unknown)
    [ "$state" = "running" ]
}

# ---------- 配置备份 ----------
backup_configs() {
    local dir="$SCRIPT_DIR/.deploy-backups/$(date +%Y%m%d-%H%M%S)"
    mkdir -p "$dir" 2>/dev/null || { echo "⚠️  无法创建备份目录 $dir"; return 0; }
    local f
    for f in docker-compose.yml docker-compose.yaml compose.yml compose.yaml .env .env.local; do
        if [ -f "$SCRIPT_DIR/$f" ]; then
            cp -a "$SCRIPT_DIR/$f" "$dir/" 2>/dev/null || true
        fi
    done
    echo "📦 配置已备份到 $dir"
    echo "💡 数据库快照请自行处理（如 mysqldump / pg_dump）"
}
backup_configs

# ---------- 幂等检查（init） ----------
if [ "$MODE" = "init" ] && [ -f "$STATE_FILE" ]; then
    echo "⚠️  检测到已有部署状态："
    cat "$STATE_FILE" || true
    if [ "$ASSUME_YES" != true ]; then
        if [ -t 0 ]; then
            read -r -p "❓ 继续 init 部署？(y/N) " ans
            case "$ans" in
                y|Y|yes|YES) ;;
                *) echo "已取消"; exit 1 ;;
            esac
        else
            echo "❌ 非交互环境且 $STATE_FILE 已存在。使用 --yes 确认继续，或先 --clean。"
            exit 1
        fi
    else
        echo "ℹ️  --yes 已指定，跳过确认"
    fi
fi

# ---------- 记录回滚点 ----------
record_rollback

# ---------- 健康检查 ----------
wait_for_oneshot() {
    local service_name="$1"
    local timeout="${2:-$HEALTH_TIMEOUT}"
    local elapsed=0
    echo "⏳ 等待一次性任务 $service_name 完成（超时 ${timeout}s）..."
    while [ "$elapsed" -lt "$timeout" ]; do
        local ids
        ids="$(compose ps -a -q "$service_name" 2>/dev/null || true)"
        if [ -n "$ids" ]; then
            local all_done=true
            while IFS= read -r cid; do
                [ -z "$cid" ] && continue
                local state
                state="$(docker inspect -f '{{.State.Status}}:{{.State.ExitCode}}' "$cid" 2>/dev/null || echo "unknown:1")"
                case "$state" in
                    exited:0) ;;
                    exited:*)
                        echo "❌ $service_name 任务失败（exit=${state#exited:}）"
                        return 1
                        ;;
                    *) all_done=false ;;
                esac
            done <<< "$ids"
            if [ "$all_done" = true ]; then
                echo "✅ $service_name 任务完成"
                return 0
            fi
        fi
        sleep "$HEALTH_INTERVAL"
        elapsed=$((elapsed + HEALTH_INTERVAL))
    done
    echo "❌ $service_name 任务超时（${timeout}s）"
    return 1
}

wait_for_healthy() {
    local service_name="$1"
    local timeout="${2:-$HEALTH_TIMEOUT}"
    local elapsed=0

    if is_oneshot "$service_name"; then
        wait_for_oneshot "$service_name" "$timeout"
        return $?
    fi

    echo "⏳ 等待 $service_name 就绪（超时 ${timeout}s）..."
    sleep "$HEALTH_INITIAL_WAIT"

    while [ "$elapsed" -lt "$timeout" ]; do
        local ids
        ids="$(compose ps -q "$service_name" 2>/dev/null || true)"

        if [ -z "$ids" ]; then
            echo "    … 暂无 $service_name 容器实例"
        else
            local all_ok=true
            local any_unhealthy=false
            local crashed=false

            while IFS= read -r cid; do
                [ -z "$cid" ] && continue
                local status
                status="$(docker inspect \
                    -f '{{if .State.Health}}{{.State.Health.Status}}{{else}}none{{end}}' \
                    "$cid" 2>/dev/null || echo "failed")"

                case "$status" in
                    healthy) ;;
                    unhealthy)
                        any_unhealthy=true
                        all_ok=false
                        ;;
                    none)
                        local state
                        state="$(docker inspect -f '{{.State.Status}}' "$cid" 2>/dev/null || echo "unknown")"
                        case "$state" in
                            running) ;;
                            exited)
                                local ec
                                ec="$(docker inspect -f '{{.State.ExitCode}}' "$cid" 2>/dev/null || echo 1)"
                                if [ "$ec" -eq 0 ] && exit_zero_allowed_for "$service_name"; then
                                    echo "ℹ️  $service_name 容器已 exit 0（allow_zero），视为完成"
                                else
                                    echo "❌ $service_name 容器已退出（exit=$ec）"
                                    crashed=true
                                    all_ok=false
                                fi
                                ;;
                            *) all_ok=false ;;
                        esac
                        ;;
                    *) all_ok=false ;;
                esac
            done <<< "$ids"

            if [ "$any_unhealthy" = true ]; then
                echo "❌ $service_name 健康检查失败（Unhealthy）"
                return 1
            fi
            if [ "$crashed" = true ]; then
                return 1
            fi
            if [ "$all_ok" = true ]; then
                echo "✅ $service_name 已就绪"
                return 0
            fi
        fi

        sleep "$HEALTH_INTERVAL"
        elapsed=$((elapsed + HEALTH_INTERVAL))
    done

    echo "❌ $service_name 启动超时（${timeout}s）"
    return 1
}

# ---------- 数据就绪探针（init 模式） ----------
check_data_ready() {
    [ "$DATA_READY_CHECK" = true ] || { echo "⏭️  DATA_READY_CHECK=false，跳过数据就绪探针"; return 0; }
    local ok=true svc
    if has_service mysql && is_running mysql; then
        echo "🔍 验证 MySQL 可连接..."
        if ! compose exec -T mysql mysqladmin ping -h localhost --silent >/dev/null 2>&1; then
            echo "❌ MySQL ping 失败"; ok=false
        fi
    fi
    if has_service mariadb && is_running mariadb; then
        echo "🔍 验证 MariaDB 可连接..."
        if ! compose exec -T mariadb mysqladmin ping -h localhost --silent >/dev/null 2>&1; then
            echo "❌ MariaDB ping 失败"; ok=false
        fi
    fi
    if has_service postgres || has_service postgresql; then
        svc="postgres"; has_service postgres || svc="postgresql"
        if is_running "$svc"; then
            echo "🔍 验证 PostgreSQL 可连接..."
            if ! compose exec -T "$svc" pg_isready -q >/dev/null 2>&1; then
                echo "❌ PostgreSQL pg_isready 失败"; ok=false
            fi
        fi
    fi
    if has_service redis && is_running redis; then
        echo "🔍 验证 Redis 可连接..."
        # redis 带 --requirepass 启动时裸 ping 会 NOAUTH 失败；密码取容器内环境变量
        # （与 compose healthcheck 同一约定），未设置密码时退回裸 ping。
        if ! compose exec -T redis sh -c \
            'if [ -n "$REDIS_AUTH" ]; then redis-cli -a "$REDIS_AUTH" --no-auth-warning ping; else redis-cli ping; fi' \
            2>/dev/null | grep -q PONG; then
            echo "❌ Redis ping 失败"; ok=false
        fi
    fi
    if has_service rabbitmq && is_running rabbitmq; then
        echo "🔍 验证 RabbitMQ 可连接..."
        if ! compose exec -T rabbitmq rabbitmq-diagnostics -q ping >/dev/null 2>&1; then
            echo "❌ RabbitMQ diagnostics ping 失败"; ok=false
        fi
    fi
    if has_service mongodb || has_service mongo; then
        svc="mongodb"; has_service mongodb || svc="mongo"
        if is_running "$svc"; then
            echo "🔍 验证 MongoDB 可连接..."
            if ! compose exec -T "$svc" mongosh --quiet --eval 'db.adminCommand({ping:1}).ok' 2>/dev/null | grep -q 1; then
                echo "⚠️  MongoDB 探测失败（可能缺少 mongosh 客户端）"
            fi
        fi
    fi
    [ "$ok" = true ]
}

# ---------- up 参数 ----------
UP_OPTIONS=(--force-recreate)
if [ "$RENEW_VOLUMES" = true ]; then
    echo "⚠️  已启用 --renew-anon-volumes：匿名卷将被重建，请确认业务数据不在匿名卷中"
    UP_OPTIONS+=(--renew-anon-volumes)
fi

PULL_ARGS=()
if [ "$NEED_PULL" = true ] && [ "$NEED_BUILD" = false ]; then
    PULL_ARGS+=(--pull=always)
fi

# ---------- init: 清理旧环境（可选） ----------
if [ "$MODE" = "init" ] && [ "$CLEAN" = true ]; then
    echo "🧹 [Init] 清理旧环境（down --remove-orphans）..."
    compose down --remove-orphans 2>&1 || true
fi

# ---------- Step 1: 构建 / 拉取 ----------
if [ "$NEED_BUILD" = true ]; then
    echo "📦 [Step 1] 构建镜像..."

    BUILD_TARGETS=()
    if has_service app_a; then
        BUILD_TARGETS+=(app_a)
        has_service app_b && BUILD_TARGETS+=(app_b)
    elif has_service app; then
        BUILD_TARGETS+=(app)
    else
        echo "❌ 未找到 app / app_a 服务，无法构建"
        exit 1
    fi

    for t in "${BUILD_TARGETS[@]}"; do
        if [ "$NEED_PULL" = true ]; then
            echo "📥 构建 $t（拉取最新基础镜像）..."
            compose build --pull "$t"
        else
            echo "🔨 构建 $t..."
            compose build "$t"
        fi
    done

    # 仓库根 runtime/container（SCAN_CACHEABLE 注解缓存 + proxy 快照）。注意：
    # 1. 路径是仓库根（$SCRIPT_DIR/../../runtime），不是 deploy/runtime；
    # 2. 仅 --build 路径清理；无 --build 的热更新若含注解/代理类变更，需手动删
    #    （wsl -u root rm -rf <repo>/runtime/container 后 restart app）。
    if [ -d "$SCRIPT_DIR/../../runtime/container" ]; then
        echo "🧹 [Step 1.1] 清理宿主机 runtime/container 注解缓存..."
        rm -rf "$SCRIPT_DIR/../../runtime/container" 2>/dev/null || true
        if [ -d "$SCRIPT_DIR/../../runtime/container" ]; then
            # 容器 root 建的文件 WSL 普通用户删不动，回退到 app 容器内删（源码 bind mount，等价）
            echo "   宿主机删除失败，尝试容器内清理..."
            compose exec -T app rm -rf /opt/www/runtime/container 2>/dev/null || true
        fi
    fi
else
    echo "⏩ [Step 1] 跳过镜像构建（仅热重启容器）"
fi

if [ "$NEED_PULL" = true ] && [ "$NEED_BUILD" = false ]; then
    echo "📥 拉取 compose 中所有镜像..."
    compose pull
fi

# ---------- Step 2: 基础设施（deploy 模式） ----------
if [ "$MODE" = "deploy" ]; then
    for infra in $INFRA_SERVICES; do
        if has_service "$infra"; then
            echo "🔄 [Step 2] 启动基础设施 $infra..."
            compose up -d "$infra"
            wait_for_healthy "$infra"
        fi
    done
fi

# ---------- Step 3: init 全量启动 ----------
if [ "$MODE" = "init" ]; then
    echo "🚀 [Init] 全量启动所有服务..."

    # 拆分 oneshot 服务：--wait 对它们可能不友好
    NON_ONESHOT_SERVICES=()
    for svc in $services; do
        is_oneshot "$svc" || NON_ONESHOT_SERVICES+=("$svc")
    done

    if [ "$HAS_COMPOSE_WAIT" = true ]; then
        echo "⏳ 使用 docker compose up --wait（按 depends_on 拓扑等待就绪）..."
        if [ "${#NON_ONESHOT_SERVICES[@]}" -gt 0 ]; then
            compose up -d --remove-orphans --wait --wait-timeout "$HEALTH_TIMEOUT" \
                "${PULL_ARGS[@]}" "${NON_ONESHOT_SERVICES[@]}"
        fi
        if [ -n "$ONESHOT_SERVICES" ]; then
            echo "🚀 启动一次性任务服务..."
            compose up -d "${PULL_ARGS[@]}" $ONESHOT_SERVICES
            for s in $ONESHOT_SERVICES; do
                wait_for_oneshot "$s"
            done
        fi
    else
        echo "ℹ️  当前 compose 不支持 --wait-timeout（需 v2.20+），回退到逐服务健康检查"
        compose up -d --remove-orphans "${PULL_ARGS[@]}"
        sleep "$HEALTH_INITIAL_WAIT"
        for svc in $services; do
            wait_for_healthy "$svc"
        done
    fi

    # 数据面就绪探测（MySQL/PG/Redis/MQ/Mongo）
    if ! check_data_ready; then
        echo "❌ 数据面未就绪，终止 init"
        exit 1
    fi

    # 数据初始化钩子（migration / seeding）
    hook="${INIT_HOOK:-$SCRIPT_DIR/init-hook.sh}"
    if [ -f "$hook" ]; then
        echo "🔧 [Init] 执行初始化钩子: $hook"
        bash "$hook"
    elif [ -n "$INIT_HOOK" ]; then
        echo "🔧 [Init] 执行 INIT_HOOK: $INIT_HOOK"
        bash -c "$INIT_HOOK"
    fi

    echo "========================================================="
    echo "🎉 初始部署完成"
    echo "========================================================="
    write_state
    notify "success" "初始部署完成（project=$COMPOSE_PROJECT_NAME）"
    exit 0
fi

# ---------- Step 3: 应用（deploy 模式） ----------
if has_service app_b; then
    echo "🔄 [Step 3] 重启备份节点 app_b..."
    compose up -d "${UP_OPTIONS[@]}" "${PULL_ARGS[@]}" app_b
    wait_for_healthy app_b

    echo "🔄 [Step 3.1] 重启主节点 app_a..."
    compose up -d "${UP_OPTIONS[@]}" "${PULL_ARGS[@]}" app_a
    wait_for_healthy app_a
elif has_service app; then
    echo "🔄 [Step 3] 启动应用 app..."
    compose up -d "${UP_OPTIONS[@]}" "${PULL_ARGS[@]}" app
    wait_for_healthy app
else
    echo "❌ 未找到 app / app_a / app_b 服务"
    exit 1
fi

# ---------- Step 4: 清理 ----------
if [ "$NEED_BUILD" = true ]; then
    echo "🧹 [Step 5] 清理旧镜像与构建缓存（仅 7 天前）..."
    docker builder prune -f --filter until=168h || true
    docker image prune -f --filter until=168h || true
fi

echo "========================================================="
echo "🎉 部署完成（project=$COMPOSE_PROJECT_NAME）"
echo "========================================================="
write_state
notify "success" "部署完成（project=$COMPOSE_PROJECT_NAME）"