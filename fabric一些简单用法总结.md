**1.支持中文**

```python
#!/usr/bin/python env
# -*- coding: utf-8 -*-
```

**2.支持颜色**

```python
from fabric.colors import *
print(red("红色") + green("绿色"，bold=False) + blue("蓝色",bold=True) + white("白色") + yellow("黄色") + cyan("蓝绿色") + magenta("品红色"))
```

**3.设置主机组及账号密码**

主机密码不同时：

```python
env.hosts = ['tomcat@192.168.244.128','tomcat@192.168.244.129']
env.passwords = {'tomcat@192.168.244.128:22':'111111','tomcat@192.168.244.129:22':'111111'}
```

主机密码相同时:

```python
env.hosts=[ 
'tomcat@192.168.244.128:22', 
'tomcat@192.168.244.129:22', 
] 
env.password = '1111'
```

使用ssh keyfile:

```python
env.key_filename = ['/opt/fab/server_key']
env.user = 'tomcat'
env.password = '111111'
env.port = '2862'
```

**4.运行shell命令**

在本地运行命令:

```python
local('pwd')
local('set -m ; /etc/init.d/tomcat restart') 
# 如果是脚本，要加set -m 支持后台执行并返回状态，否则会报错
```

在服务器上运行命令:

```python
run('uname -a')
```

**5.切换目录执行**

```python
with cd('/opt/tomcat')
    run('set -m ;  ./bin/startup.sh')
with lcd('/opt/tomcat')
    run('pwd')
```

cd 是在远程服务器上执行，lcd是在本地执行

**6.上传下载文件**
从远端服务器下载:

```python
get('/remote/path/','/local/path/')
```

上传文件到远端服务器:

```python
 put('/local/path/','/remote/path')
```

这两种方式使用的是sftp协议

**7.判断文件或目录是否存在**

```python
from fabric.contrib.files import exists
    if exists('/opt/tomcat/logs/catalina.out'):
        print 'catalina.out exist'    
    else:
        print 'catalina.out not exist'
```

**8.判断远程主机的文件中是否存在文本**

```python
from fabric.contrib.files import contains
    if contains('/opt/tomcat/catalina.out','username1'):
        print "contains text"
    else：
        print "no contains"
```

**9.以sudo权限运行**

```python
sudo('whoami',user='tomcat')
```

用户须在/etc/sudoers里配置sudo权限

**10.命令嵌套**

```python
with prefix('cd /opt/fab'):
    run('pwd')
    with prefix('cd fabfiles'):
        run('pwd')
        run('ls')
```

上面的代码等同于:

```bash
cd /opt/fab && pwd
cd /var/fab && cd fabfiles && pwd && ls
```

**11.从键盘接收输入**

使用python的函数， 接收一行：

```python
text = raw_input() 
print text
```

接收一段：

```python
import sys
update_log = sys.stdin.read()
print update_log
```

**12.只运行一次函数**

```python
@runs_once
def local_file_backup
    print ‘我只运行一次’
```

fabric执行时都会在每台主机上执行所有函数，如果有函数只需执行一次，可以用这个参数

**13.用roles定义分组**

```python
env.roledefs = {
    'ftp': ['192.168.1.100'],
    'web': ['192.168.1.101', '192.168.1.102', '192.168.1.103'],
}
```

定义分组的好处是可以指定某一分组主机，执行某一任务
例如：从ftp主机下载代码，传到web上

```python
@runs_once
＠roles('ftp')
def download():
    print 'donwload files'

@roles('web')
    put('local/files','remote/files')
```

**14.使用rsync传输**
put/get/project.upload_project 都是使用sftp方式，比较慢，fabric提供了rsync方式

```python
from fabric.contrib import project, console
def syncfile():
    project.rsync_project(
        remote_dir=online_dir,
        local_dir=/tmp/upload/,
        default_opts='-avczp',
                    delete=True
    )
```

**15.任务增加说明**
用def一个函数后，在`fab -f fabfile.py -l`中只能看到函数名，没有说明，例如上面的rsync例子的结果：

```bash
root@localhost tmp]# fab -f rsync.py -l
Available commands:
syncfile
```

添加函数说明可以在def下用三个引号来写说明。格式：

```python
def rsyncfile():
    """使用rsync同步文件"""
```

在执行一下：

```bash
root@localhost tmp]# fab -f rsync.py -l
Available commands:
syncfile
```