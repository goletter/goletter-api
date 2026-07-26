# =========================================================
# 阶段一：纯粹的编译沙盒（在这里把 xlswriter 啃下来）
# =========================================================
FROM hyperf/hyperf:8.3-alpine-v3.19-swoole AS builder

# 安装专门用于编译的依赖（只在这个临时阶段生效，不会带入正式镜像）
RUN apk add --no-cache \
        autoconf \
        gcc \
        g++ \
        make \
        libc-dev \
        pkgconf \
        re2c \
        php83-dev \
        php83-pear \
        libzip-dev \
        unzip \
        curl

# 下载并编译安装 xlswriter
RUN cd /tmp \
    && curl -SL https://pecl.php.net/get/xlswriter-1.5.8.tgz -o xlswriter.tgz \
    && mkdir -p xlswriter-src \
    && tar -xf xlswriter.tgz -C xlswriter-src --strip-components=1 \
    && cd xlswriter-src \
    && phpize83 \
    && ./configure --with-php-config=php-config83 \
    && make -j$(nproc) \
    && make install

# =========================================================
# 阶段二：你的正式运行镜像（继承自官方 Hyperf 镜像，保持纯净）
# =========================================================
FROM hyperf/hyperf:8.3-alpine-v3.19-swoole

ARG timezone
ENV TIMEZONE=${timezone:-"Asia/Shanghai"} \
    APP_ENV=prod \
    SCAN_CACHEABLE=true

# 只安装运行所需的最小化基础包
RUN apk add --no-cache libzip unzip curl

# 容器内手动执行 `composer install` 时默认按生产依赖安装，避免误拉 require-dev。
# 如需安装 dev 依赖，可执行：COMPOSER_INSTALL_WITH_DEV=1 composer install
RUN set -ex \
    && COMPOSER_BIN="$(command -v composer)" \
    && mv "$COMPOSER_BIN" /usr/local/bin/composer-original \
    && printf '%s\n' \
        '#!/bin/sh' \
        'if { [ "$1" = "install" ] || [ "$1" = "i" ]; } && [ "${COMPOSER_INSTALL_WITH_DEV:-0}" != "1" ]; then' \
        '    shift' \
        '    exec /usr/local/bin/composer-original install --no-dev --optimize-autoloader --ignore-platform-reqs "$@"' \
        'fi' \
        '' \
        'exec /usr/local/bin/composer-original "$@"' \
        > "$COMPOSER_BIN" \
    && chmod +x "$COMPOSER_BIN"

# 💡【核心杀手锏】：从上面的 builder 阶段中，直接把编译好的 xlswriter.so 偷过来！
# 并把它塞进 Hyperf 默认的扩展配置目录里
COPY --from=builder /usr/lib/php83/modules/xlswriter.so /usr/lib/php83/modules/xlswriter.so
RUN echo "extension=xlswriter.so" > /etc/php83/conf.d/50_xlswriter.ini

# ---------- PHP 配置与时区设置 ----------
RUN set -ex \
    && cd /etc/php* \
    && { \
        echo "upload_max_filesize=128M"; \
        echo "post_max_size=128M"; \
        echo "memory_limit=1G"; \
        echo "date.timezone=${TIMEZONE}"; \
    } | tee conf.d/99_overrides.ini \
    && ln -sf /usr/share/zoneinfo/${TIMEZONE} /etc/localtime \
    && echo "${TIMEZONE}" > /etc/timezone \
    && rm -rf /var/cache/apk/* /tmp/* /usr/share/man

WORKDIR /opt/www

# =========================================================
# 💡【层缓存优化】：依赖包和代码的分流处理
# =========================================================
COPY ./composer.* /opt/www/
RUN composer-original install --no-dev --no-scripts --no-autoloader --ignore-platform-reqs

# 复制其余业务源码
COPY . /opt/www

# 生成类映射优化
RUN composer-original dump-autoload -o

EXPOSE 9501

ENTRYPOINT ["php", "/opt/www/bin/hyperf.php", "start"]