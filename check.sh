#!/bin/bash

count=`ps -fe |grep "ws_server" | grep -v "grep" | grep "master" | wc -l`

echo $count
if [ $count -lt 1 ]; then
ps -eaf |grep "ws_server" | grep -v "grep"| awk '{print $2}'| xargs kill -9
sleep 1
echo "restart"
/usr/bin/php /www/tcp_server/ws_server.php start
echo $(date +%Y-%m-%d_%H:%M:%S) >> /data/logs/restart.log
fi

