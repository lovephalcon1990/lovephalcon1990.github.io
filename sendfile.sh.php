#!/bin/sh
#<?php die();?>
source /etc/profile

#示例 sh sendfile.sh.php 'alenai' 'model/' 'local' '/data/wwwroot/hy/cms/' '192.168.56.2 192.168.56.2' '/tmp'

#######################################################################
#
# 用途：从本机器同步文件至一台或多台指定ip、目录，可回滚
# send
#		$1 操作cmd(send发布,rollback回滚)
#		$2 操作人账号名; 						例如:'alenai'
#		$3 文件名，多个以空格隔开，支持目录			例如：'model/abc.php model/def.php api/'
#		$4 来源IP [目前仅支持从本机]				例如：localhost
# 		$5 来源根目录;  						例如：'/data/wwwroot/cms/'
# 		$6 目标IP，多个以空格隔开					例如：'192.168.56.1 192.168.56.2'
#		$7 目标目录								例如：'/data/wwwroot/cms/demo/'
#
# rollback
#		$1 操作cmd(send发布,rollback回滚)
#		$2 目标ip
#		$3 目标目录
#    	$4 目标服务器备份存放路径
#
#######################################################################

function argCheck() {
	if [ -z "$0" -o -z "$1" -o -z "$2" -o -z "$3" -o -z "$4" -o -z "$5" -o -z "$6" -o -z "$7" ];then
	  echo "ERROR=Usage: $0 'cmd' 'Your Name' 'File List...' 'sourceIp' 'sourceDir' 'targetIp' 'targetDir'";
	  exit 1;
	fi

	if [ ! -d $5 ];then
		echo "ERROR=sourceDir not a directory, ${5}, ERROR!";
		exit 2;
	fi
	readonly CMD=${1};
	readonly NAME=${2};
	readonly FILES=${3};
	readonly SOURCE_IP=${4};
	readonly SOURCE_DIR=${5};
	readonly TARGET_IP=${6};
	readonly TARGET_DIR=${7};
}

function argCheckRollBack(){
	if [ -z "$0" -o -z "$1" -o -z "$2" -o -z "$3" -o -z "$4" ];then
	  echo "ERROR=Usage: $0 'cmd' 'targetIp' 'targetDir' 'targetTarBak'";
	  exit 1;
	fi
	readonly CMD=${1};
	readonly TARGET_IP=${2};
	readonly TARGET_DIR=${3};
	readonly TARGET_TAR_BAK=${4};
}

function versionCheck(){
	#检查是否安装expect机
	if [ ! `which expect` ]; then
			cd /tmp;
			wget -N ftp://ftp.za.freebsd.org/sourceforge/t/tc/tcl/Tcl/8.4.16/tcl8.4.16-src.tar.gz;
			tar zxvf tcl8.4.16-src.tar.gz
			cd tcl8.4.16/unix
			./configure
			make && make install

			cd /tmp;
			wget -N ftp://ftp.netbsd.org/pub/pkgsrc/distfiles/expect5.45.tar.gz;
			tar zxvf expect5.45.tar.gz
			cd expect5.45
			./configure --with-tcl=/usr/local/lib/ --with-tclinclude=/tmp/tcl8.4.16/generic/ --with-x=no
			make && make install
	fi

	#检查rsync版本
	if [ `rsync --version |awk '/rsync  version/{print $3}'` != '2.6.8' ]; then
			cd /tmp;
			wget -N https://ftp.osuosl.org/pub/blfs/conglomeration/rsync/rsync-2.6.8.tar.gz
			tar xvzf rsync-2.6.8.tar.gz
			cd rsync-2.6.8
			./configure
			make && make install
	fi
}

#检查语法
function checkSyntax() {
	if [ ! -f $1 ]; then
		return;
	fi
	#echo -ne "check syntax in $1 ..............";
	if [[ ${#1} -gt 7 && ${1: -7} == .sh.php ]]; then
		#if grep -q $'\x0D\x0A' $1 ; then
			#echo -en "\033[1;31;5m ERROR! \033[0m file $1 is DOS format !\n";
			#exit;
		#fi
		/bin/sh -n $1;
		if [ $? != 0 ]; then #检查语法
			echo -en "ERROR= file $1 have syntax error or not exist!\n";
			exit 3;
		fi
	fi;
	if [[ ${#1} -gt 4 && ${1: -4} == .php ]]; then
		if [ $(/usr/local/php/bin/php -l $1|grep "No syntax errors"|wc -l) != 1 ]; then #检查语法
			echo -en "ERROR= file $1 have syntax error or not exist!\n";
			#exit 4;
		fi
		if grep -q $'^\xEF\xBB\xBF' $1 ; then
			echo -en "ERROR= Byte Order Mark be found in $1 !\n";
			exit 5;
		fi
	fi;
	#echo -ne " ok\n";
}

#文件检查
function fileCheck(){
	for i in ${FILES}
	do

		i=${i//\\/\/};#把windows的目录分隔符换成linux的目录分隔符

		if [[ $i == "#" || $i == '/' || $i == '.' || $i == '..' || $i == '../' ]];then
			echo "ERROR=$i Please enter a file or folder";
			exit 7;
		fi
		if [ -d $i ];then
			for j in `find $i -type f -name '*.php'`;
			do
				checkSyntax "$j";
			done;
			continue;
		fi
		if [ -f $i ];then
			checkSyntax "$i";
			continue;
		else
			echo "ERROR=$i File does not exist";
			exit 6;
		fi

		echo "ERROR=$i Please enter a file or folder";
		exit 8;

	done;
}

function sshCmd(){
	ip=${1}
	cmd=${2}
	ssh -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no -i /home/www/.ssh/id_rsa ${USERNAME}@${ip} -p3600 "${cmd}"
	echo "ssh -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no -i /home/www/.ssh/id_rsa ${USERNAME}@${ip} -p3600 ${cmd}";
}

#回滚,无备份
function rollback(){
	argCheckRollBack "$@";
	for ip in ${TARGET_IP}
	do
		#解压原备份文件
		sshCmd "${ip}" "mkdir -p ${TARGET_DIR} && cd ${TARGET_DIR} && tar zxvf ${TARGET_TAR_BAK}" || ERROR=$?;
		if [[ ${ERROR} -gt 0 ]]; then
			echo "ERROR=ROLLBACK:"${ERROR};
			exit 1;
		fi
	done;
}

#发布
function send(){

	argCheck "$@";

	readonly FILE_NAME="/tmp/rsync_${NAME}_${TIME}.tar";
	readonly LOCAL_TAR="${FILE_NAME}.html";#本地压缩包文件存放路径
	readonly REMOTE_TAR="${FILE_NAME}.remote.html";#远程压缩包存放路径
	readonly REMOTE_BAK="${FILE_NAME}.remote.bak.html";#远程备份存放路径

	cd ${SOURCE_DIR};

	fileCheck "$@";

	#压缩
	tar -zcvf "${LOCAL_TAR}" --exclude=".svn" --exclude="docs" --exclude="tpl_cache" ${FILES};

	chmod -R 775 ${LOCAL_TAR}

	for ip in ${TARGET_IP}
	do

		#备份已有文件
		sshCmd "${ip}" "mkdir -p ${TARGET_DIR} && cd ${TARGET_DIR} && tar -zcvf ${REMOTE_BAK} ${FILES}"; # || ERROR=$?;
		#if [[ ${ERROR} -gt 0 ]]; then
		#	echo "ERROR=BAK:"${ERROR};
		#	exit 1;
		#fi

		#上传
		rsync -avzP -e "ssh -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no -i /home/www/.ssh/id_rsa -p3600" ${LOCAL_TAR} ${USERNAME}@${ip}:${REMOTE_TAR} || ERROR=$?;

		if [[ ${ERROR} -gt 0 ]]; then
			echo "ERROR=RSYNC:"${ERROR};
			exit 1;
		fi

		#解压覆盖
		sshCmd "${ip}" "mkdir -p ${TARGET_DIR} && cd ${TARGET_DIR} && chmod 775 ${REMOTE_TAR} && tar zxvf ${REMOTE_TAR} && chmod 775 -R ${TARGET_DIR}" || ERROR=$?;
		if [[ ${ERROR} -gt 0 ]]; then
			echo "ERROR=UNTAR:"${ERROR};
			exit 1;
		fi
	done;

	#删除本地压缩包
	rm -f "${LOCAL_TAR}";

	#远程服务器上压缩包
	echo "REMOTE_TAR="${REMOTE_TAR};
	echo "REMOTE_BAK="${REMOTE_BAK};
	exit 0;
}

function main() {
	case $1 in
		rollback)
			rollback "$@";
			;;
		send)
			send "$@";
			;;
		*)
			echo 'cmd error';
			exit 1;
			;;
	esac
	exit 0;
}

#versionCheck "$@";
readonly TIME=`date +%Y%m%d%H%M%S`;
readonly USERNAME='www';
main "$@";
exit 0;

#上传
#expect -c "
#set timeout -1
#set i [ lindex $argv 0 ]
#spawn rsync -avzP -e \"ssh -p 3600\" \"${LOCAL_TAR}\" ${USERNAME}@${ip}:\"${REMOTE_TAR}\"
#expect {
#		\"yes/no\" { send \"yes\r\"; exp_continue};
#		\"password:\" { send \"${PASSWORD}\r\" };
#		};
#expect eof;"

#备份并解压  #&& rm -rf ${REMOTE_TAR} tar -zcvf ./ttt.tar `tar -tf /tmp/rsync_alenai_20170612122149620490762.tar.html`  && tar zxf ${REMOTE_TAR}
#&& (tar -zcvf ${REMOTE_BAK} ${3})
#expect -c "
#set timeout -1
#set i [ lindex $argv 0 ]
#spawn ssh -p 3600 ${USERNAME}@${ip} \"mkdir -p ${TARGET_DIR} && cd ${TARGET_DIR}    && tar zxf ${REMOTE_TAR} \"
#expect {
#		\"yes/no\" { send \"yes\r\"; exp_continue};
#		\"password:\" { send \"${PASSWORD}\r\" };
#		};
#expect eof;"
