# 星海商城 ShopLab

教学用漏洞靶场（PHP 8 + MySQL + PDO，无框架），只包含两个漏洞：

- **V1 任意文件上传**：个人中心头像上传
- **V2 水平越权（IDOR）**：订单详情页

其余功能均按安全写法实现。

## 环境要求

- Windows 10 / 11
- phpStudy（Apache 2.4 + PHP 8.2 NTS + MySQL 5.7）
- 站点目录：`E:\work\trae\shoplab`

## 目录结构

```
shoplab/
├── admin/              后台（控制台、漏洞开关）
├── assets/css/         样式
├── data/labs.json      漏洞开关状态
├── includes/           公共配置、数据库、函数、头尾模板
├── sql/shoplab.sql     建表与种子数据
├── uploads/avatars/    头像上传目录
├── config.php          数据库与站点配置
├── install.php         一键初始化数据库
├── start.bat           启动 Apache/MySQL 并打开浏览器
├── index.php           商品列表
├── product.php         商品详情、加入购物车
├── cart.php            购物车
├── checkout.php        确认订单
├── orders.php          我的订单
├── order.php           订单详情
├── login.php / register.php / logout.php
└── profile.php         个人中心（头像上传）
```

## 首次部署

1. 修改 `config.php` 中的数据库账号密码（默认 `root / root`）。
2. 在 Apache 的 `conf/vhosts/` 下新增 `shoplab_8080.conf`：

```apache
Listen 8080

<VirtualHost *:8080>
    DocumentRoot "E:/work/trae/shoplab"
    ServerName localhost:8080

    FcgidInitialEnv PHPRC "E:/php/phpstudy_pro/Extensions/php/php8.2.9nts"
    AddHandler fcgid-script .php
    FcgidWrapper "E:/php/phpstudy_pro/Extensions/php/php8.2.9nts/php-cgi.exe" .php

    <Directory "E:/work/trae/shoplab">
        Options FollowSymLinks ExecCGI
        AllowOverride All
        Require all granted
        DirectoryIndex index.php index.html
    </Directory>

    ErrorLog "E:/php/phpstudy_pro/Extensions/Apache2.4.39/logs/shoplab-error.log"
    CustomLog "E:/php/phpstudy_pro/Extensions/Apache2.4.39/logs/shoplab-access.log" common
</VirtualHost>
```

3. 启动 Apache 和 MySQL。
4. 浏览器访问 `http://127.0.0.2:8080/install.php` 初始化数据库（建表并写入 3 个账号、分类、8 件商品）。
5. 访问 `http://127.0.0.2:8080/`。

## 日常启动

双击 `start.bat`，脚本会检查 3306 / 8080 端口，按需启动 MySQL 与 Apache，并打开站点。

## 测试账号

| 账号 | 密码 | 角色 |
| --- | --- | --- |
| admin | 123456 | 管理员 |
| alice | 123456 | 普通用户 |
| bob | 123456 | 普通用户 |

## 漏洞开关

用 admin 登录后进入「后台 → 漏洞开关」，可分别开关：

- V1 任意文件上传（`profile.php`）
- V2 水平越权（`order.php`）

开关状态保存在 `data/labs.json`，默认均为开启。

## 演示步骤

### V1 任意文件上传

1. 用 alice 登录，进入「个人中心」。
2. 选择 `shell.php`（内容任意 PHP 代码），上传时把请求中的 `Content-Type` 改为 `image/png`。
3. 访问 `http://127.0.0.2:8080/uploads/avatars/shell.php`，PHP 代码被执行。
4. 后台关闭 V1 开关，再上传同样的文件，被后缀白名单拦截。

### V2 水平越权

1. alice 登录并下单，记下订单详情地址中的 `id`。
2. 用 bob 登录，访问 `http://127.0.0.2:8080/order.php?id=1`，能看到 alice 的订单。
3. 后台关闭 V2 开关，bob 再访问同一地址返回 404。

## 说明

本项目仅用于本地教学演示，请勿部署到公网。
