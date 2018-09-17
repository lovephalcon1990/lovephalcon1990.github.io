#!/bin/sh
#<?php die();?>
source /etc/profile
umask 002
if [[ $# -ne 3 ]];then
   echo "ERR ARGS"
   exit 256
else
   project_id=$1
   isRel=$2
   port=$3
fi

####main
#sh start.sh 13 1 6106
cd $(cd "$(dirname "$0")";pwd)
basedir="$(pwd)"
php7_exe="/usr/local/php7/bin/php"
php="${basedir}/sicboRobotServer.php ${project_id} ${isRel} ${port}"
run="${php7_exe} $php"

count=2
for((i=1;i<=5;i++));do 
    count=`ps -fe |grep "$run" | grep -v "grep" | wc -l`
    if [ $count -eq 2 ]; then
        break
    fi
    sleep 0.1
done
ret=0
if [ $count -lt 2 ]; then
    $(ps -eaf |grep "$php" | grep -v "grep"| awk '{print $2}'|xargs kill -9)
    $(ps -eaf |grep "$php" | grep -v "grep"| awk '{print $2}'|xargs kill -9)
    $(ps -eaf |grep "$php" | grep -v "grep"| awk '{print $2}'|xargs kill -9)
    sleep 2
    ulimit -c unlimited
    $run >/dev/null 2>&1 &
    ret=2
else
    ret=1
fi
${php7_exe} ${basedir}/include/Crontab.php ${project_id} $ret ${port}
echo 'ok'