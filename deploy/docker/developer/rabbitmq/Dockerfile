# =========================================================
# 阶段一：下载延迟插件（不留在最终镜像中）
# =========================================================
FROM alpine:3.19 AS downloader

ARG DELAYED_MESSAGE_PLUGIN_VERSION=3.13.0
RUN apk add --no-cache curl \
    && curl -fSL \
       "https://github.com/rabbitmq/rabbitmq-delayed-message-exchange/releases/download/v${DELAYED_MESSAGE_PLUGIN_VERSION}/rabbitmq_delayed_message_exchange-${DELAYED_MESSAGE_PLUGIN_VERSION}.ez" \
       -o /plugins.ez

# =========================================================
# 阶段二：最终 RabbitMQ（Alpine，显式启用插件）
# =========================================================
FROM rabbitmq:3.13-management-alpine

ARG DELAYED_MESSAGE_PLUGIN_VERSION=3.13.0

# 拷贝插件并修正权限（ADD 远程文件默认 0600，rabbitmq 用户读不到）
COPY --from=downloader /plugins.ez \
    /opt/rabbitmq/plugins/rabbitmq_delayed_message_exchange-${DELAYED_MESSAGE_PLUGIN_VERSION}.ez

RUN chmod 0644 /opt/rabbitmq/plugins/rabbitmq_delayed_message_exchange-*.ez \
    && chown rabbitmq:rabbitmq /opt/rabbitmq/plugins/rabbitmq_delayed_message_exchange-*.ez \
    && test -s "/opt/rabbitmq/plugins/rabbitmq_delayed_message_exchange-${DELAYED_MESSAGE_PLUGIN_VERSION}.ez" \
    && rabbitmq-plugins enable --offline rabbitmq_delayed_message_exchange

COPY entrypoint.sh /usr/local/bin/rabbitmq-app-entrypoint.sh
RUN chmod 0755 /usr/local/bin/rabbitmq-app-entrypoint.sh

ENTRYPOINT ["/usr/local/bin/rabbitmq-app-entrypoint.sh"]