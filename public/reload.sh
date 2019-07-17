#!/bin/bash
#重启9502 swlooe 进程
pid=$(netstat -anp|grep 9502|awk '{printf $7}'|cut -d/ -f1 | xargs kill -9)
echo "进程以杀死"
nohup php im_SwooleWebSocketServer.php &
echo "重启成功"