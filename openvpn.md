#### 初始化

```bash
# 证书密码见服务器 mm.md
# 创建目录
mkdir -p /data/openvpn/conf
# 初始化 openvpn 配置
docker run -v /data/openvpn:/etc/openvpn --rm kylemanna/openvpn ovpn_genconfig -u udp://vpn.lbbniu.com
# 初始化证书 根据提示数据证书密码和组织名称
docker run -v /data/openvpn:/etc/openvpn --rm -ti kylemanna/openvpn ovpn_initpki
# 增加用户
docker run -v /data/openvpn:/etc/openvpn --rm -ti kylemanna/openvpn easyrsa build-client-full lbbniu nopass
# 导出用户配置文件
docker run -v /data/openvpn:/etc/openvpn --rm -ti kylemanna/openvpn ovpn_getclient lbbniu > /data/openvpn/conf/lbbniu.ovpn
# 启动服务
docker run --name openvpn -v /data/openvpn:/etc/openvpn -d -p 1194:1194/udp --cap-add=NET_ADMIN kylemanna/openvpn
```

#### 添加用户脚本

```bash
#!/bin/bash
# openvpn_useradd.sh
password='证书密码'
read -p "please your username: " NAME
expect <<EOF
    spawn docker exec -it openvpn easyrsa build-client-full $NAME nopass
    expect "ca.key:" {send "$password\n"}
    expect eof
EOF
docker exec -ti openvpn ovpn_getclient $NAME > /data/openvpn/conf/"$NAME".ovpn
sed -i 's/redirect-gateway/#redirect-gateway/' /data/openvpn/conf/"$NAME".ovpn
```

#### 删除用户脚本

```bash
#!/bin/bash
# openvpn_userdel.sh
password='证书密码'
read -p "Delete username: " DNAME
expect <<EOF
    spawn docker exec -ti openvpn easyrsa revoke $DNAME
    expect {
        "revocation:" { send "yes\n";exp_continue }
        "ca.key:" { send "$password\n" }
    }
    expect eof
    spawn docker exec -ti openvpn easyrsa gen-crl
    expect "ca.key:" { send "$password\n"}
    expect eof
EOF
docker exec -ti openvpn rm -f /etc/openvpn/pki/reqs/"$DNAME".req
docker exec -ti openvpn rm -f /etc/openvpn/pki/private/"$DNAME".key
docker exec -ti openvpn rm -f /etc/openvpn/pki/issued/"$DNAME".crt
docker exec -ti openvpn rm -f /etc/openvpn/conf/"$DNAME".ovpn
docker exec -ti openvpn rm -rf /etc/openvpn/clients/"$DNAME"
```

#### 添加删除用户操作

```bash
cd /root/docker-compose
# 添加用户 然后输入用户名
./openvpn_useradd.sh
# 删除用户 然后输入用户名
./openvpn_userdel.sh
```



#### 其他常用操作

```bash
# 查看连接用户列表
docker exec openvpn ovpn_status

# 配置走vpn的路由
```

mac客户端：https://github.com/Tunnelblick/Tunnelblick/releases

参考链接：https://blog.whsir.com/post-2809.html

路由配置: https://www.bbsmax.com/A/lk5aNy3Zd1/

docker修改默认网关：https://www.cnblogs.com/52fhy/p/9308422.html

七款适用于企业的开源 VPN 工具：http://www.zzvips.com/news/2317.html

