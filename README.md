### 说明
本项目为开源项目，项目比较简单， 主要是对 swoole 扩展进行了封装， 便于基于 swoole 提供的强大网络通信功能进行业务上的开发。
项目因物联网而生，主要提供 tcp_server 部分， 包括设备端通信协议的实现， 设备连接，状态上报，消息推送。
该项目不是很成熟，测试的也不是很充分，架构也不是很理想，有待于重构，仅供学习测试使用， 不建议用于生产环境。

### 依赖

- php5.3.0 +
- redis-server，详见 redis.io
- mosquitto-server 详见： http://mosquitto.org/download/
- libmosquitto-dev 详见： http://mosquitto.org/download/
- php 扩展 swoole 1.8.0 + 详见： https://github.com/swoole/swoole-src
- php 扩展 redis 详见： https://github.com/phpredis/phpredis
- php 扩展 mosquitto-PHP 详见： https://github.com/mgdm/Mosquitto-PHP

在安装本项目之前，你最好已经安装好了以上环境，便于测试。php扩展的安装 需要 phpize工具，因此需要安装php-devel（centos） 或者 php-dev（ubuntu）。

### 如何安装
这是一个非常小的 framework， 核心是另外一个项目 http://github.com/nosun/swoole, 已经做了 composer 包。

安装过程非常简单， 假设你已经安装了composer，此时 clone 该项目，进入 clone 的目录中，运行：
```
composer install
```
就安装完毕了。

### 如何配置

配置文件有几个，分别是server.php, redis.php, app.php, mqtt.php, ssdb.php(如果需要存储大量数据，可以考虑使用ssdb 代替 redis，或者配合使用)，本项目未考虑mysql的情况，后面有空再说吧。

关键的配置是 server.php， 主要是swoole server 的配置参数， 请参看 wiki.swoole.com 进行理解设置。

### 如何使用

业务主要在app文件中完成，主要的在 app 下的 server 中的 tcp.php， 这个文件中 主要实现了 swoole 的tcp server的回调函数。

启动文件为根目录下的 tcp.php, 主要的命令有如下三个：

```
php tcp.php start
php tcp.php stop
php tcp.php restart
```

### 协议

原始的协议如下： https://github.com/nosun/skyiot/blob/master/protocol/standard/T.md

本项目做了几点主要的修改：
1. 关键字 `cmd` 修改为 `action`
2. data 中的 json array，修改为 json对象，去除 双引号的格式
3. 去除pkey等安全相关的验证环节。
4. pid 设置为产品类型，为上传过程中的必填字段 （这里有点偷懒了，后面有需要在优化）
