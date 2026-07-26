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
# 阶段二：正式运行镜像
# =========================================================
FROM hyperf/hyperf:8.3-alpine-v3.19-swoole

ARG timezone
ENV TIMEZONE=${timezone:-"Asia/Shanghai"} \
    APP_ENV=prod \
    SCAN_CACHEABLE=true

# 只安装运行所需的最小化基础包
RUN apk add --no-cache libzip unzip curl

# 拷贝编译好的 xlswriter 扩展
COPY --from=builder /usr/lib/php83/modules/xlswriter.so /usr/lib/php83/modules/xlswriter.so
RUN echo "extension=xlswriter.so" > /etc/php83/conf.d/50_xlswriter.ini

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

# 先复制 composer 文件，利用 Docker 层缓存
COPY ./composer.json ./composer.lock /opt/www/

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

# 复制业务源码
COPY . /opt/www

# 生成优化 autoload
RUN composer dump-autoload -o

EXPOSE 9501

ENTRYPOINT ["php", "/opt/www/bin/hyperf.php", "start"]