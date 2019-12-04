# plugin
PHP 小程序 小应用

1,nginx:
rpm -ivh http://nginx.org/packages/centos/7/noarch/RPMS/nginx-release-centos-7-0.el7.ngx.noarch.rpm

systemctl enable nginx.service //开机启动

2,php:

yum install https://dl.fedoraproject.org/pub/epel/epel-release-latest-7.noarch.rpm

yum install http://rpms.remirepo.net/enterprise/remi-release-7.rpm

yum -y install yum-utils

yum-config-manager --enable remi-php73

yum -y install php php-mcrypt php-devel php-cli php-gd php-pear php-curl php-fpm php-mysql php-ldap php-zip php-fileinfo 

php -v //查看 php 版本

systemctl start php-fpm //启动php-fpm

systemctl enable php-fpm.service //开机启动

3，安装php redis 扩展

cd /home/download

wget https://pecl.php.net/get/redis-4.3.0.tgz

tar -xzvf redis-4.3.0.tgz

cd /home/download/redis-4.3.0

/usr/bin/phpize

./configure --with-php-config=/usr/bin/php-config

make && make install

echo "extension=redis.so;" >> /etc/php.d/20-redis.ini

php --ri redis //查看redis 安装是否成功

4， 安装php swoole 扩展

wget https://github.com/swoole/swoole-src/archive/v4.3.5.tar.gz

tar -xzvf swoole-src-4.3.5.tar.gz

cd /home/download/cd swoole-src-4.3.5

/usr/bin/phpize

./configure --with-php-config=/usr/bin/php-config

make && make install

echo "extension=swoole.so;" >> /etc/php.d/20-swoole.ini

php --ri swoole //查看 swoole 安装是否成功


5, 项目nginx 配置

vim /etc/nginx/nginx.conf  //修改 user  nginx ;为 user  apache;
//worker_processes  1;  cpu 核数


6, vmBOx 网络增强包 虚拟机初始密码 Root@aiwan@2019

1 下载扩展 https://www.cnblogs.com/jpfss/p/9156738.html；

2 将CD进行挂载。mount /dev/cdrom /data/wwwroot (mnt/目录下创建的文件夹)

3 cd /mnt; ./VBoxLinuxAdditions.run or ./VBoxLinuxAdditions.run --nox11

4 mount -t vboxsf wwwroot /data/wwwroot













