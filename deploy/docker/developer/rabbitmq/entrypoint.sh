#!/bin/sh
# 启动 RabbitMQ，并把仓库根 .env 里的 AMQP_USER / AMQP_PASSWORD 同步到 broker。
# 命名卷只在首次初始化时读 RABBITMQ_DEFAULT_*；之后改 .env 不会自动改库内密码，此处补齐。
set -e

if [ -z "${AMQP_PASSWORD:-}" ]; then
    echo "AMQP_PASSWORD missing: set it in repository root .env" >&2
    exit 1
fi

AMQP_USER="${AMQP_USER:-guest}"
AMQP_VHOST="${AMQP_VHOST:-/}"

export RABBITMQ_DEFAULT_USER="$AMQP_USER"
export RABBITMQ_DEFAULT_PASS="$AMQP_PASSWORD"

docker-entrypoint.sh rabbitmq-server &
server_pid=$!

trap 'kill -TERM "$server_pid" 2>/dev/null; wait "$server_pid" 2>/dev/null; exit 143' TERM INT

ready=0
i=0
while [ "$i" -lt 120 ]; do
    if rabbitmqctl list_users --silent >/dev/null 2>&1; then
        ready=1
        break
    fi
    i=$((i + 1))
    sleep 1
done

if [ "$ready" -ne 1 ]; then
    echo "RabbitMQ did not become ready within 120s" >&2
    kill -TERM "$server_pid" 2>/dev/null || true
    wait "$server_pid" 2>/dev/null || true
    exit 1
fi

if rabbitmqctl list_users --silent | awk '{print $1}' | grep -qx "$AMQP_USER"; then
    rabbitmqctl change_password "$AMQP_USER" "$AMQP_PASSWORD"
else
    rabbitmqctl add_user "$AMQP_USER" "$AMQP_PASSWORD"
    rabbitmqctl set_user_tags "$AMQP_USER" administrator
fi

rabbitmqctl set_permissions -p "$AMQP_VHOST" "$AMQP_USER" ".*" ".*" ".*"

wait "$server_pid"
