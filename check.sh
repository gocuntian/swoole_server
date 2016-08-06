#!/bin/bash

count=`ps -fe |grep "tcp_server" | grep -v "grep" | grep "master" | wc -l`

echo $count
if [ $count -lt 1 ]; then
ps -eaf |grep "tcp_server" | grep -v "grep"| awk '{print $2}'| xargs kill -9
sleep 1
echo "restart"
/usr/bin/php /www/swoole_server/tcp.php start
echo $(date +%Y-%m-%d_%H:%M:%S) >> logs/restart.log
fi

