# =========================================================
# 单阶段构建 xlswriter，避免 builder/runtime PHP ABI 不一致
# （hyperf 镜像内 PHP 常落后于 alpine 仓库；apk add php83-dev 会升级 PHP，
#  若只把 .so 拷到旧 PHP，会报 smart_str_ferealloc: symbol not found）
# =========================================================
FROM hyperf/hyperf:8.3-alpine-v3.19-swoole

ARG timezone
ENV TIMEZONE=${timezone:-"Asia/Shanghai"} \
    APP_ENV=prod \
    SCAN_CACHEABLE=true

# 运行依赖 + 编译 xlswriter；编译完删除 build-deps（PHP 本体会随仓库升级并保留）
RUN set -ex \
    && apk add --no-cache libzip unzip curl \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS libzip-dev \
    && cd /tmp \
    && curl -SL https://pecl.php.net/get/xlswriter-1.5.8.tgz -o xlswriter.tgz \
    && mkdir -p xlswriter-src \
    && tar -xf xlswriter.tgz -C xlswriter-src --strip-components=1 \
    && cd xlswriter-src \
    && phpize83 \
    && ./configure --with-php-config=php-config83 \
    && make -j$(nproc) \
    && make install \
    && echo "extension=xlswriter.so" > /etc/php83/conf.d/50_xlswriter.ini \
    && cd / \
    && apk del .build-deps \
    && rm -rf /tmp/* /var/cache/apk/* \
    && php -m | grep -i xlswriter \
    && php -v

# PHP 配置与时区设置
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

# 先复制 composer 文件与本地 path 包，利用 Docker 层缓存
COPY ./composer.json ./composer.lock /opt/www/
COPY ./packages /opt/www/packages

# 校验 lock 是否同步，然后按 lock 安装生产依赖
RUN composer validate --no-check-publish --strict \
    && composer install \
        --no-dev \
        --prefer-dist \
        --no-interaction \
        --no-progress \
        --no-scripts \
        --no-autoloader \
        --ignore-platform-reqs

# 复制业务源码（含 app/config/bin；packages 已在上层复制）
COPY . /opt/www

# 生成优化 autoload
RUN composer dump-autoload -o

EXPOSE 8501

ENTRYPOINT ["php", "/opt/www/bin/hyperf.php", "start"]