#!/usr/bin/env bash
# -------------------------------------------------------------------
# 新建发版目录：秒级时间 + 随机数 + 功能名
# 用法: ./scripts/new-release.sh <功能名>
# Windows PowerShell 请用: .\scripts\new-release.ps1 <功能名>
# -------------------------------------------------------------------
set -euo pipefail

export LANG="${LANG:-en_US.UTF-8}"
export LC_ALL="${LC_ALL:-en_US.UTF-8}"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
RELEASES_DIR="${ROOT_DIR}/releases"

usage() {
    cat <<EOF
用法: $0 <功能名>

会在 releases/ 下创建:
  YYYYMMDDHHmmss_随机数_功能名/{upgrade.sh, NOTES.md}
EOF
    exit 1
}

if [[ $# -lt 1 ]] || [[ "${1:-}" == "-h" ]] || [[ "${1:-}" == "--help" ]]; then
    usage
fi

FEATURE_RAW="$1"
FEATURE_NAME="$(echo "$FEATURE_RAW" | sed -E 's/[[:space:]]+/_/g; s/[\/\\:*?"<>|]+/_/g; s/_+/_/g; s/^_|_$//g')"

if [[ -z "$FEATURE_NAME" ]]; then
    echo "错误: 功能名无效"
    exit 1
fi

TIME_STR="$(date +%Y%m%d%H%M%S)"
RAND_STR="$(printf '%04d' $((RANDOM % 10000)))"
DIR_NAME="${TIME_STR}_${RAND_STR}_${FEATURE_NAME}"
TARGET_DIR="${RELEASES_DIR}/${DIR_NAME}"

while [[ -d "$TARGET_DIR" ]]; do
    RAND_STR="$(printf '%04d' $((RANDOM % 10000)))"
    DIR_NAME="${TIME_STR}_${RAND_STR}_${FEATURE_NAME}"
    TARGET_DIR="${RELEASES_DIR}/${DIR_NAME}"
done

mkdir -p "$TARGET_DIR"

cat > "${TARGET_DIR}/upgrade.sh" <<EOF
#!/usr/bin/env bash
# 发版: ${DIR_NAME}（${FEATURE_RAW}）
# 顺序: migrate → 自定义 → deploy
# 代码已 volume 挂载，CLI 读磁盘最新代码；deploy 只重启常驻 Swoole 进程。
set -euo pipefail

ROOT_DIR="\$(cd "\$(dirname "\${BASH_SOURCE[0]}")/../.." && pwd)"
cd "\$ROOT_DIR"
DC=(docker compose -f docker/docker-compose.yml)

echo "========================================================="
echo "开始发版: ${DIR_NAME}"
echo "========================================================="

echo "[1/3] 执行 migrations ..."
"\${DC[@]}" exec -T app_a php bin/hyperf.php migrate

echo "[2/3] 执行自定义脚本 ..."
# "\${DC[@]}" exec -T app_a php bin/hyperf.php xxx:fix

echo "[3/3] 热更新服务 ..."
bash docker/deploy.sh

echo "========================================================="
echo "发版完成: ${DIR_NAME}"
echo "========================================================="
EOF

cat > "${TARGET_DIR}/NOTES.md" <<EOF
# ${FEATURE_RAW}

## 历史 SQL

-

## 本次改动

-
EOF

chmod +x "${TARGET_DIR}/upgrade.sh"

echo "✅ 已创建: ${TARGET_DIR}"
