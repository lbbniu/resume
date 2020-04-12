> 对刚接触Ngx_lua的读者来说，可能会存在下面两个困惑。
>
> 1、Lua在Nginx的哪些阶段可以执行代码？
> 2、Lua在Nginx的每个阶段可以执行哪些操作？
>
> 只有理解了这两个问题，才能在业务中巧妙地利用Ngx_Lua来完成各项需求。
>
> Nginx的11个执行阶段，每个阶段都有自己能够执行的指令，并可以实现不同的功能。Ngx_Lua的功能大部分是基于Nginx这11个执行阶段开发和配置的，Lua代码在这些指令块中执行，并依赖于它们的执行顺序。本章将对Ngx_Lua的执行阶段进行一一讲解。

# 一、 init_by_lua_block

init_by_lua_block是init_by_lua的替代版本，在OpenResty 1.9.3.1或Lua-Nginx-Modulev 0.9.17之前使用的都是init_by_lua。init_by_lua_block比init_by_lua更灵活，所以建议优先选用init_by_lua_block。
本章中的执行阶段都采用*_block格式的指令，后续不再说明。

**1.1　阶段说明**
语法：init_by_lua_block {lua-script-str}
配置环境：http
阶段：loading-config
含义：当Nginx的master进程加载Nginx配置文件（加载或重启Nginx进程）时，会在全局的Lua VM（Virtual Machine，虚拟机）层上运行<lua-script-str> 指定的代码，每次当Nginx获得HUP（即Hangup）重载信号加载进程时，代码都会被重新执行。

***\*1.2　初始化配置\****
在loading-config阶段一般会执行如下操作。
1．初始化Lua全局变量，特别适合用来处理在启动master进程时就要求存在的数据，对CPU消耗较多的功能也可以在此处处理。
2．预加载模块。
3．初始化lua_shared_dict共享内存的数据（关于共享内存详见第10章）。
示例如下：

```
user  webuser webuser;
worker_processes  1;
worker_rlimit_nofile 10240;

events {
    use epoll;
    worker_connections  10240;
}

http {
    include       mime.types;
    default_type  application/octet-stream;
    log_format main '$remote_addr-$remote_user[$time_local] "$request" '
                      '$status $body_bytes_sent "$http_referer" ' 
                      '"$http_user_agent" "$http_x_forwarded_for" "$request_time" "$upstream_addr $upstream_status $upstream_response_time"  "upstream_time_sum:$upstream_time_sum"  "jk_uri:$jk_uri"';
    access_log  logs/access.log  main;
    sendfile        on;
    keepalive_timeout  65;

    lua_package_path "/usr/local/nginx_1.12.2/conf/lua_modules/?.lua;;";
    lua_package_cpath "/usr/local/nginx_1.12.2/conf/lua_modules/c_package/?.so;;";

    lua_shared_dict dict_a 100k;  --声明一个Lua共享内存，dict_a为100KB
init_by_lua_block {
-- cjson.so文件需要手动存放在lua_package_cpath搜索路径下，如果是OpenResty，就不必了，因为它默认支持该操作
          cjson = require "cjson"; 
          local dict_a = ngx.shared.dict_a;
          dict_a:set("abc", 9)
    }

    server {
        listen       80;
        server_name  testnginx.com;
        location / {
           content_by_lua_block {
               ngx.say(cjson.encode({a = 1, b = 2}))
               local dict_a = ngx.shared.dict_a;
               ngx.say("abc=",dict_a:get("abc"))
           }
        }
    }
```

执行结果如下：

```
# curl -I http://testnginx.com/
{"a":1,"b":2}
abc=9
```

**1.3　控制初始值**
在init_by_lua_block阶段设置的初始值，即使在其他执行阶段被修改过，当Nginx重载配置时，这些值就又会恢复到初始状态。如果在重载Nginx配置时不希望再次改动这些初始值，可以在代码中做如下调整。

```
init_by_lua_block {
      local cjson = require "cjson";
      local dict_a = ngx.shared.dict_a;
      local v = dict_a:get("abc");  --判断初始值是否已经被set
      if not v then                 --如果没有，就执行初始化操作
        dict_a:set("abc", 9)
      end
}
```

**1.4　init_by_lua_file**
init_by_lua_file和init_by_lua_block的作用一样，主要用于将init_by_lua_block的内容转存到指定的文件中，这样Lua代码就不必全部写在Nginx配置里了，易读性更强。
init_by_lua_file支持配置绝对路径和相对路径。相对路径是在启动Nginx时由-p PATH 决定的，如果启动Nginx时没有配置-p PATH，就会使用编译时--prefix的值，该值一般存放在Nginx的$prefix（也可以用${prefix}来表示）变量中。init_by_lua_file和Nginx的include指令的相对路径一致。
举例如下：

```
init_by_lua_file conf/lua/init.lua;  --相对路径
init_by_lua_file /usr/local/nginx/conf/lua/init.lua; --绝对路径
init.lua文件的内容如下：
cjson = require "cjson"
local dict_a = ngx.shared.dict_a
local v = dict_a:get("abc")
if not v then
   dict_a:set("abc", 9)
end  
```

**1.5　可使用的Lua API指令**
init_by_lua*是Nginx配置加载的阶段，很多Nginx API for Lua命令是不支持的。目前已知支持的Nginx API for Lua的命令有ngx.log、ngx.shared.DICT、print。
注意：init_by_lua*中的*表示通配符，init_by_lua*即所有以init_by_lua开头的API。后续的通配符亦是如此，不再另行说明。

# 二、init_worker_by_lua_block

**2.1　阶段说明**
**语法：**init_worker_by_lua_block {lua-script-str}
**配置环境：**http
**阶段：**starting-worker
**含义：**当master进程被启动后，每个worker进程都会执行Lua代码。如果Nginx禁用了master进程，init_by_lua*将会直接运行。

**2.2　启动Nginx的定时任务**
在init_worker_by_lua_block执行阶段最常见的功能是执行定时任务。示例如下：

```
user  webuser webuser;
worker_processes  3;
worker_rlimit_nofile 10240;

events {
    use epoll;
    worker_connections  10240;
}

http {
    include       mime.types;
    default_type  application/octet-stream;
    sendfile        on;
    keepalive_timeout  65;

    upstream  test_12 {
        server 127.0.0.1:81  weight=20  max_fails=300000 fail_timeout=5s;
        server 127.0.0.1:82  weight=20  max_fails=300000 fail_timeout=5s;
    }

    lua_package_path "${prefix}conf/lua_modules/?.lua;;";
    lua_package_cpath "${prefix}conf/lua_modules/c_package/?.so;;";

    init_worker_by_lua_block  {
        local delay = 3  --3秒
        local cron_a   
    --定时任务的函数
        cron_a = function (premature)   
            if not  premature then  --如果执行函数时没有传参，则该任务会一直被触发执行
                ngx.log(ngx.ERR, "Just do it !")
            end

        end
    --每隔delay参数值的时间，就执行一次cron_a函数
        local ok, err = ngx.timer.every(delay, cron_a)   
        if not ok then
            ngx.log(ngx.ERR, "failed to create the timer: ", err)
            return
        end
    }
```

**2.3　动态进行后端健康检查**
在init_worker_by_lua_block阶段，也可以实现后端健康检查的功能，用于检查后端HTTP服务是否正常，类似于Nginx商业版中的health_check功能。
如果使用OpenResty 1.9.3.2及以上的版本，默认已支持此模块；如果使用Nginx，则首先需要安装此模块，安装方式如下：

```
# git clone https://github.com/openresty/lua-upstream-nginx-module.git
# cd nginx-1.12.2
# ./configure --prefix=/opt/nginx \
    --with-ld-opt="-Wl,-rpath,$LUAJIT_LIB" \
    --add-module=/path/to/lua-nginx-module \
    --add-module=/path/to/lua-upstream-nginx-module 
# make && make install
```

注意：安装完成后，重新编译Nginx时，请先确认之前安装模块的数量，避免遗忘某个模块。
然后，将lua-resty-upstream-healthcheck模块中的lib文件复制到lua_package_path指定的位置，示例如下：

```
# git clone https://github.com/openresty/lua-resty-upstream-healthcheck.git
# cp lua-resty-upstream-healthcheck/lib/resty/upstream/healthcheck.lua   /usr/local/nginx_1.12.2/conf/lua_modules/resty/
```

实现动态进行后端健康检查的功能，配置如下：

```
user  webuser webuser;
worker_processes  3;
worker_rlimit_nofile 10240;

events {
    use epoll;
    worker_connections  10240;
}
http {
    include       mime.types;
    default_type  application/octet-stream;
    sendfile        on;
    keepalive_timeout  65;

    upstream  test_12 {
        server 127.0.0.1:81  weight=20  max_fails=10 fail_timeout=5s;
        server 127.0.0.1:82  weight=20  max_fails=10 fail_timeout=5s;
        server 127.0.0.1:8231  weight=20  max_fails=10 fail_timeout=5s;
    }
    lua_shared_dict healthcheck 1m;  # 存放upstream servers的共享内存，后端服务器组越多，配置就越大
    lua_socket_log_errors off;  # 当TCP发送失败时，会发送error日志到error.log中，该过程会增加性能开销，建议关闭，以避免在健康检查过程中出现多台服务器宕机的情况，异常情况请使用ngx.log来记录
    lua_package_path "${prefix}conf/lua_modules/?.lua;;";
    lua_package_cpath "${prefix}conf/lua_modules/c_package/?.so;;";

    init_worker_by_lua_block  {
        local hc = require "resty.upstream.healthcheck"
        local ok, err = hc.spawn_checker{
            shm = "healthcheck",  -- 使用共享内存 
            upstream = "test_12", -- 进行健康检查的upstream名字
            type = "http",        -- 检查类型是http
            http_req = "GET /status HTTP/1.0\r\nHost: testnginx.com\r\n\r\n",
                    -- 用来发送HTTP请求的格式和数据，核实服务是否正常 
            interval = 3000,  -- 设置检查的频率为每3s一次
            timeout = 1000,   -- 设置请求的超时时间为1s
            fall = 3,  --设置连续失败3次后就把后端服务改为down 
            rise = 2,  --设置连续成功2次后就把后端服务改为up 
        valid_statuses = {200, 302},  --设置请求成功的响应状态码是200和302
            concurrency = 10,  --设置发送请求的并发等级
        }
        if not ok then
            ngx.log(ngx.ERR, "failed to spawn health checker: ", err)
            return
        end
    }
    server {
        listen       80;
        server_name  testnginx.com;
        location / {
           proxy_pass http://test_12;
        }
        # /status 定义了后端健康检查结果的输出页面
        location = /status {
            default_type text/plain;
            content_by_lua_block {
                local hc = require "resty.upstream.healthcheck"
                --输出当前检查结果是哪个worker进程的
                ngx.say("Nginx Worker PID: ", ngx.worker.pid())
--status_page()输出后端服务器的详细情况
                ngx.print(hc.status_page())
            }
        }
    }
```

访问http://testnginx.com/status查看检查的结果，图8-1所示为健康检查数据结果。
![Nginx Lua的执行阶段](https://s1.51cto.com/images/blog/201812/17/4b3019fc07a1f20d43cab853c7d5c907.png?x-oss-process=image/watermark,size_16,text_QDUxQ1RP5Y2a5a6i,color_FFFFFF,t_100,g_se,x_10,y_10,shadow_90,type_ZmFuZ3poZW5naGVpdGk=)
图8-1　健康检查数据结果
如果要检查多个upstream，则配置如下（只有黑色加粗位置的配置有变化）：

```
local ok, err = hc.spawn_checker{
    shm = "healthcheck",
    upstream = "test_12",
    type = "http",

    http_req = "GET /status HTTP/1.0\r\nHost: testnginx.com\r\n\r\n",

    interval = 3000,
    timeout = 1000,
    fall = 3,
    rise = 2,
    valid_statuses = {200, 302},  
    concurrency = 10, 
}
local ok, err = hc.spawn_checker{
    shm = "healthcheck",
    upstream = "test_34",
    type = "http",

    http_req = "GET /test HTTP/1.0\r\nHost: testnginx.com\r\n\r\n",
    interval = 3000,
    timeout = 1000,
    fall = 3,
    rise = 2,
    valid_statuses = {200, 302}, 
    concurrency = 10,  
```

如果把lua_socket_log_errors设置为on，那么当有异常出现时，例如出现了超时，就会往error.log里写日志，日志记录如图8-2所示。
![Nginx Lua的执行阶段](https://s1.51cto.com/images/blog/201812/17/4797175602cf8537f3a1dc999f27603b.png?x-oss-process=image/watermark,size_16,text_QDUxQ1RP5Y2a5a6i,color_FFFFFF,t_100,g_se,x_10,y_10,shadow_90,type_ZmFuZ3poZW5naGVpdGk=)
图8-2　日志记录
经过lua-resty-upstream-healthcheck的健康检查发现异常的服务器后，Nginx会动态地将异常服务器在upstream中禁用，以实现更精准的故障转移。

# 三、set_by_lua_block

**3.1　阶段说明**
**语法：**set_by_lua_block $res {lua-script-str}
**配置环境：**server，server if，location，location if
**阶段：**rewrite
**含义：**执行<lua-script-str>代码，并将返回的字符串赋值给$res。

**3.2　变量赋值**
本指令一次只能返回一个值，并赋值给变量$res（即只有一个$res被赋值），示例如下：

```
server {
    listen       80;
    server_name  testnginx.com;
    location / {
    set $a '';
    set_by_lua_block $a {
         local t = 'tes'
         return t
    }
    return 200 $a;
}
```

执行结果如下：

```
#  curl http://testnginx.com/
test
```

那如果希望返回多个变量，该怎么办呢？可以使用ngx.var.VARIABLE，示例如下：

```
server {
    listen       80;
    server_name  testnginx.com;
    location / {
    #使用ngx.var.VARIABLE前需先定义变量
    set $b '';
    set_by_lua_block $a {
        local t = 'test'
        ngx.var.b = 'test_b'
        return t
    }
    return 200 $a,$b;
    }
}
```

执行结果如下：

```
#  curl http://testnginx.com/test
test,test_b
```

**3.2　Rewrite阶段的混用模式**
因为set_by_lua_block处在rewrite阶段，所以它可以和ngx_http_rewrite_module、set-misc-nginx-module，以及array-var-nginx-module一起使用，在代码执行时，按照配置文件的顺序从上到下执行，示例如下：

```
server {
    listen       80;
    server_name  testnginx.com;
    location / {

    set $a '123';
    set_by_lua_block $b {
        local t = 'bbb'
        return t
    }
    set_by_lua_block $c {
        local t = 'ccc'  .. ngx.var.b
        return t
    }
    set $d "456$c"; 
    return 200 $a,$b,$c,$d;
    }

}
```

从执行结果可以看出数据是从上到下执行的，如下所示：

```
# curl  http://testnginx.com/test
123,bbb,cccbbb,456cccbbb
```

**3.3　阻塞事件**
set_by_lua_block指令块在Nginx中执行的指令是阻塞型操作，因此应尽量在这个阶段执行轻、快、短、少的代码，以避免耗时过多。set_by_lua_block不支持非阻塞I/O，所以不支持yield当前Lua的轻线程。
**3.4　被禁用的Lua API指令**
在set_by_lua_block阶段的上下文中，下面的Lua API是被禁止的（只罗列部分）。
输出类型的API函数（如ngx.say和ngx.send_headers）。
控制类型的API函数（如ngx.exit）。
子请求的API函数（如ngx.location.capture和ngx.location.capture_multi）。
Cosocket API函数（如ngx.socket.tcp和ngx.req.socket）。
休眠API函数ngx.sleep。

# 四、rewrite_by_lua_block

**4.1　阶段说明**
**语法：**rewrite_by_lua_block {lua-script-str}
**配置环境：**http，server，location，location if
**阶段：**rewrite tail
**含义：**作为一个重写阶段的处理程序，对每个请求执行<lua-script-str>指定的Lua代码。
这些Lua代码可以调用所有的Lua API，并且运行在独立的全局环境（类似于沙盒）中，以新的协程来执行。因为可以调用所有的Lua API，所以此阶段可以实现很多功能，例如对请求的URL进行重定向、读取MySQL或Redis数据、发送子请求、控制请求头等。

**4.2　利用rewrite_by_lua_no_postpone改变执行顺序**
rewrite_by_lua_block命令默认在ngx_http_rewrite_module之后执行，示例如下：

```
server {
    listen       80;
    server_name  testnginx.com;
    location / {
     set $b  '1';
     rewrite_by_lua_block { ngx.var.b = '2'}
     set $b  '3';
     echo $b;
    }
}
```

从代码来看，预计应输出的结果是3，但输出的结果却是2，如下所示：

```
#  curl  http://testnginx.com/test
2
```

上述配置的操作结果说明rewrite_by_lua_block始终都在rewrite阶段的后面执行，如果要改变这个顺序，需使用rewrite_by_lua_no_postpone指令。
**语法：**rewrite_by_lua_no_postpone on|off
**默认：**rewrite_by_lua_no_postpone off
**配置环境：**http
**含义：**在rewrite请求处理阶段，控制rewrite_by_lua*的所有指令是否被延迟执行，默认为off，即延迟到最后执行；如果设置为on，则会根据配置文件的顺序从上到下执行。
**示例：**

```
rewrite_by_lua_no_postpone on;   #只能在http阶段配置
server {
    listen       80;
    server_name  testnginx.com;
    location / {
     set $b  '1';
     rewrite_by_lua_block { ngx.var.b = '2'}
     set $b  '3';
     echo $b;
    }
}
```

执行结果如下：

```
#  curl -i http://testnginx.com/test
HTTP/1.1 200 OK
Server: nginx/1.12.2
Date: Mon, 28 May 2018 12:47:37 GMT
Content-Type: application/octet-stream
Transfer-Encoding: chunked
Connection: keep-alive

3
```

> 注意：if 语句是在rewrite_by_lua_block阶段之前执行的，所以在运用if语句时要特别留意执行顺序，避免出现意想不到的结果。

**4.3　阶段控制**
在rewrite_by_lua_block阶段，当调用ngx.exit(ngx.OK)之后，请求会退出当前的执行阶段，继续下一阶段的内容处理（如果想了解更多关于ngx.exit的使用方式，请参考7.16.3节）。

# 五、access_by_lua_block

**5.1　阶段说明**
**语法：**access_by_lua_block {lua-script-str}
**配置环境：**http，server，location，location if
**阶段：**access tail
**含义：**在Nginx的access阶段，对每个请求执行<lua-script-str>的代码，和rewrite_by*lua* block一样，这些Lua代码可以调用所有的Lua API，并且运行在独立的全局环境（类似于沙盒）中，以新的协程来执行。此阶段一般用来进行权限检查和黑白名单配置。

**5.2　利用access_by_lua_no_postpone改变执行顺序**
access_by_lua_block默认在ngx_http_access_module之后。但把access_by_lua_no_postpone设置为on可以改变执行顺序，变成根据配置文件的顺序从上到下执行。access_by_lua*no* postpone的用法和rewrite_by_lua_no_postpone类似。

**5.3　阶段控制**
access_by_lua_block和rewrite_by_lua_block一样，都可以通过ngx.exit来结束当前的执行阶段。

**5.4　动态配置黑白名单**
Nginx提供了allow、deny等指令来控制IP地址访问服务的权限，但如果每次新增的IP地址都需要重载配置文件就不太灵活了，而且反复重载Nginx也会导致服务不稳定。此时，可以通过access_by_lua_block来动态添加黑白名单。
下面是两种常见的动态配置黑白名单的方案。

1．将黑白名单存放在Redis中，然后使用Nginx+Lua读取Redis的数据，通过修改Redis中的数据来动态配置黑白名单。这种方案的缺点是增加了对网络I/O的操作，相比使用共享内存的方式，性能稍微差了些。

2．将黑白名单存放在Ngx_Lua提供的共享内存中，每次请求时都去读取共享内存中的黑白名单，通过修改共享内存的数据达到动态配置黑白名单的目的。本方案的缺点是在Nginx 重启的过程中数据会丢失。

# 六、content_by_lua_block

**6.1　阶段说明**
**语法：**content_by_lua_block {lua-script-str}
**配置环境：**location，location if
**阶段：**content
**含义：**content_by_lua_block 作为内容处理阶段，对每个请求执行<lua-script-str>的代码。和rewrite_by_lua_block一样，这些Lua代码可以调用所有的Lua API，并且运行在独立的全局环境（类似于沙盒）中，以新的协程来执行。
content_by_lua_block指令不可以和其他内容处理阶段的模块如echo、return、proxy_pass等放在一起，示例如下：

```
server {
    listen       80;
    server_name  testnginx.com;

    location / {
        content_by_lua_block {
             ngx.say("content_by_lua_block")
        }
        echo 'ok';
    }
}
```

输出结果只有ok，并未执行content_by_lua_block指令。

**6.2　动态调整执行文件的路径**
指令content_by_lua_file可以用来动态地调整执行文件的路径，它后面跟的Lua执行文件路径可以是变量，该变量可以是不同的参数或URL等，示例如下：

```
location / {
    #content_by_lua_file可以直接获取URL中参数file_name的值
    content_by_lua_file conf/lua/$arg_file_name;
}
```

# 七、balancer_by_lua_block

**7.1　阶段说明**
**语法：**balancer_by_lua_block { lua-script }
**配置环境：**upstream
**阶段：**content
**含义：**在upstream的配置中执行并控制后端服务器的地址，它会忽视原upstream中默认的配置。
**示例：**

```
upstream foo {
    server 127.0.0.1;  #会忽视这个配置
    balancer_by_lua_block {
        #真实的IP地址在这里配置
    }
}
```

**7.2　被禁用的Lua API指令**
在balancer_by_lua_block阶段，Lua代码的执行环境不支持yield，因此需禁用可能会产生yield的Lua API指令（如cosockets和light threads）。但可以利用ngx.ctx创建一个拥有上下文的变量，在本阶段前面的某个执行阶段（如rewrite_by_lua*阶段）将数据生成后传入upstream中。

# 八、header_filter_by_lua_block

**8.1　阶段说明**
**语法：**header_filter_by_lua_block { lua-script }
**配置环境：**http，server，location，location if
**阶段：**output-header-filter
**含义：**在header_filter_by_lua_block阶段，对每个请求执行<lua-script-str>的代码，以此对响应头进行过滤。常用于对响应头进行添加、删除等操作。
例如，添加一个响应头test，值为nginx-lua，示例如下：

```
location / {
    header_filter_by_lua_block {
       ngx.header.test = "nginx-lua";
    }
    echo 'ok';
}
```

**8.2　被禁用的Lua API指令**
在header_filter_by_lua_block阶段中，下列Lua API是被禁止使用的。
输出类型的API函数（如ngx.say和ngx.send_headers）。
控制类型的API函数（如ngx.redirect和ngx.exec）。
子请求的API函数（如ngx.location.capture和ngx.location.capture_multi）。
cosocket API函数（如ngx.socket.tcp和ngx.req.socket）。

# 九、body_filter_by_lua_block

**9.1　阶段说明**
**语法：**body_filter_by_lua_block { lua-script-str }
**配置环境：**http，server，location，location if
**阶段：**output-body-filter
**含义：**在body_filter_by_lua_block阶段执行<lua-script-str>的代码，用于设置输出响应体的过滤器。在此阶段可以修改响应体的内容，如修改字母的大小写、替换字符串等。

**9.2　控制响应体数据**
通过ngx.arg[1]（ngx.arg[1]是Lua的字符串类型的数据）输入数据流，结束标识eof是响应体数据的最后一位ngx.arg[2]（ngx.arg[2]是Lua的布尔值类型的数据）。
由于Nginx chain缓冲区的存在，数据流不一定都是一次性完成的，可能需要发送多次。在这种情况下，结束标识eof仅仅是Nginx chain缓冲区的last_buf（主请求）或last_in_chain（子请求），因此Nginx输出过滤器在一个单独的请求中会被多次调用，此阶段的Lua指令也会被执行多次，示例如下：

```
server {
    listen       80;
    server_name  testnginx.com;

    location / {
        #将响应体全部转换为大写
        body_filter_by_lua_block { ngx.arg[1] = string.upper(ngx. arg[1]) }
        echo 'oooKkk';
        echo 'oooKkk';
        echo 'oooKkk';
    }
}
```

执行结果如下：

```
#  curl -i http://testnginx.com/?file_name=1.lua
HTTP/1.1 200 OK
Server: nginx/1.12.2
Date: Tue, 29 May 2018 11:36:54 GMT
Content-Type: application/octet-stream
Transfer-Encoding: chunked
Connection: keep-alive

OOOKKK
OOOKKK
OOOKKK
使用return ngx.ERROR可以截断响应体，但会导致数据不完整，请求无效：
location / {
    body_filter_by_lua_block {
        ngx.arg[1] = string.upper(ngx.arg[1]);
        return ngx.ERROR
    }
    echo 'oooKkk';
    echo 'oooKkk';
    echo 'oooKkk';
}
```

执行结果如下：

```
#  curl -i http://testnginx.com/
curl: (52) Empty reply from server
```

ngx.arg[2]是一个布尔值，如果把它设为true，可以用来截断响应体的数据，和return ngx.ERROR不一样，此时被截断的数据仍然可以输出内容，是有效的请求，示例如下：

```
server {
    listen       80;
    server_name  testnginx.com;
    location / {
        body_filter_by_lua_block {
           local body_chunk = ngx.arg[1]
           --如果响应体匹配到了2ooo，就让ngx.arg[2]=true
           if string.match(body_chunk, "2ooo") then
               ngx.arg[2] = true 
               return
           end
--设置ngx.arg[1] = nil，表示此响应体不会出现在输出内容中
           ngx.arg[1] = nil

        }
        echo '1oooKkk';
        echo '2oooKkk';
        echo '3oooKkk';
    }
}
```

执行结果如下：

```
#  curl -i http://testnginx.com/?file_name=1.lua
HTTP/1.1 200 OK
Server: nginx/1.12.2
Date: Tue, 29 May 2018 11:48:52 GMT
Content-Type: application/octet-stream
Transfer-Encoding: chunked
Connection: keep-alive

2oooKkk
```

从输出结果可以得出如下结论。
1．没有输出1oooKkk，是因为它在if语句中匹配不成功，设置 ngx.arg[1] = nil将没有匹配成功的数据屏蔽了。
2．没有输出3oooKkk，是因为2oooKkk被匹配成功了，使用ngx.arg[2] = true终止了后续的操作，剩下的内容就不会再被输出了。

**9.3　被禁用的Lua API指令**
在body_filter_by_lua_block阶段中，下列Lua API是被禁止的。
输出类型的API函数（如ngx.say和ngx.send_headers）。
控制类型的API函数（如ngx.redirect和ngx.exec）。
子请求的API函数（如ngx.location.capture和ngx.location.capture_multi）。
cosocket API函数（如ngx.socket.tcp和ngx.req.socket）。

## 十、log_by_lua_block

**10.1　阶段说明**
**语法：**log_by_lua_block { lua-script }
**配置环境：**http，server，location，location if
**阶段：**log
**含义：**在日志请求处理阶段执行的{ lua-script }代码。它不会替换当前access请求的日志，而会运行在access的前面。
log_by_lua_block阶段非常适合用来对日志进行定制化处理，且可以实现日志的集群化维护（详见第12章）。另外，此阶段属于log阶段，这时，请求已经返回到了客户端，对Ngx_Lua代码的异常影响要小很多。
示例：

```
server {
    listen       80;
    server_name  testnginx.com;

    location / {
        log_by_lua_block {
            local ngx = require "ngx";
            xxxsada  --一个明显的语法错误
            ngx.log(ngx.ERR, 'x');
        }
        echo 'ok';
    }
}
```

上述代码的error_log虽有报错，但执行结果仍然会输出“ok”。

**10.2　被禁用的Lua API指令**
在log_by_lua_block阶段中，下列Lua API是被禁止的。
输出类型的API函数（如ngx.say和ngx.send_headers）。
控制类型的API函数（如ngx.redirect和ngx.exec）。
子请求的API函数（如ngx.location.capture和ngx.location.capture_multi）。
cosocket API函数（如ngx.socket.tcp和ngx.req.socket）。
休眠API函数ngx.sleep。

## 十一、Lua和ssl

Lua API还可以对HTTPS协议进行处理，可以使用ssl_certificate_by_lua_block、ssl_session_fetch_by_lua_block、ssl_session_store_by_lua_block这3个指令块进行配置，由于都涉及lua-resty-core模块的ngx.ssl指令（这已经超出了本章的内容），有兴趣的读者可以去查看lua-resty-core的相关资料。

## 十二、Ngx_Lua执行阶段

通过前面章节的学习，读者了解了Ngx_lua模块各个执行阶段的作用，现在将这些执行阶段汇总到一起来观察一下整体的执行流程。
图8-3所示为Ngx_lua的执行顺序，它引用自Lua-Nginx-Module的官方Wiki，很清晰地展示了Ngx_lua在Nginx中的执行顺序。下面举一个例子来验证一下这个执行顺序。
![Nginx Lua的执行阶段](https://s1.51cto.com/images/blog/201812/17/820202598bc4d651c42055003e464a5e.png?x-oss-process=image/watermark,size_16,text_QDUxQ1RP5Y2a5a6i,color_FFFFFF,t_100,g_se,x_10,y_10,shadow_90,type_ZmFuZ3poZW5naGVpdGk=)
图8-3　Ngx_Lua的执行顺序

1．测试代码使用 *_by_lua_file指令来配置，首先，建立如下的test.lua文件：

```
# vim /usr/local/nginx_1.12.2/conf/lua/test.lua
 local ngx  = require "ngx"
-- ngx.get_phase()可以获取Lua代码在运行时属于哪个执行阶段
 local phase = ngx.get_phase()
 ngx.log(ngx.ERR, phase, ':   Hello,world! ')
```

2．在Nginx配置中载入如下的文件：

```
# init_by_lua_file 和 init_worker_by_lua_file只能在http指令块执行
init_by_lua_file  /usr/local/nginx_1.12.2/conf/lua/test.lua;
init_worker_by_lua_file  /usr/local/nginx_1.12.2/conf/lua/test.lua;

server {
    listen       80;
    server_name  testnginx.com;

    location / {
       #文件的顺序是随意摆放的，观察日志，留意文件顺序是否对执行顺序有干扰
       body_filter_by_lua_file  /usr/local/nginx_1.12.2/conf/lua/test.lua;
       header_filter_by_lua_file  /usr/local/nginx_1.12.2/conf/lua/test.lua;
       rewrite_by_lua_file  /usr/local/nginx_1.12.2/conf/lua/test.lua;
       access_by_lua_file  /usr/local/nginx_1.12.2/conf/lua/test.lua;
       set_by_lua_file $test /usr/local/nginx_1.12.2/conf/lua/test.lua;
       log_by_lua_file  /usr/local/nginx_1.12.2/conf/lua/test.lua;
       # content_by_lua_file 和 balancer_by_lua_block 不能存在于同一个location，所以只用content_by_lua_file示例
       content_by_lua_file  /usr/local/nginx_1.12.2/conf/lua/test.lua;
    }
}
```

3．重载Nginx配置让代码生效，此时不要访问Nginx，可以观察到error.log在没有请求访问的情况下也会有内容输出。
如果输出“init”，表示此阶段属于init_by_lua_block阶段；如果输出“init_worker”，表示此阶段属于init_worker_by_lua_block阶段。
有些读者可能会发现init_worker的输出有3行，那是因为此阶段是在Nginx的worker进程上执行的，每个worker进程都会独立运行一次Lua代码，因此可以看出此时Nginx启动了3个worker进程，如下所示：

```
2018/06/04 19:00:23 [error] 12034#12034: [lua] test.lua:3: init:   Hello,world!
2018/06/04 19:00:23 [error] 21019#21019: *914 [lua] test.lua:3: init_worker:   Hello,world!, context: init_worker_by_lua*
2018/06/04 19:00:23 [error] 21020#21020: *915 [lua] test.lua:3: init_worker:   Hello,world!, context: init_worker_by_lua*
2018/06/04 19:00:23 [error] 21018#21018: *916 [lua] test.lua:3: init_worker:   Hello,world!, context: init_worker_by_lua*
```

4．发送请求：

```
curl -i http://testnginx.com/
HTTP/1.1 200 OK
Server: nginx/1.12.2
Date: Mon, 04 Jun 2018 11:00:29 GMT
Content-Type: application/octet-stream
Transfer-Encoding: chunked
Connection: keep-alive
```

5．观察access.log日志，能够看到每个执行阶段的优先级顺序：

```
2018/06/04 19:16:20 [error] 21019#21019: *920 [lua] test.lua:3: set:   Hello,world!, client: 10.19.64.210, server: testnginx.com, request: "GET /test?ss HTTP/1.1", host: "testnginx.com"
2018/06/04 19:16:20 [error] 21019#21019: *920 [lua] test.lua:3: rewrite:   Hello,world!, client: 10.19.64.210, server: testnginx.com, request: "GET /test?ss HTTP/1.1", host: "testnginx.com"
2018/06/04 19:16:20 [error] 21019#21019: *920 [lua] test.lua:3: access:   Hello,world!, client: 10.19.64.210, server: testnginx.com, request: "GET /test?ss HTTP/1.1", host: "testnginx.com"
2018/06/04 19:16:20 [error] 21019#21019: *920 [lua] test.lua:3: content:   Hello,world!, client: 10.19.64.210, server: testnginx.com, request: "GET /test?ss HTTP/1.1", host: "testnginx.com"
2018/06/04 19:16:20 [error] 21019#21019: *920 [lua] test.lua:3: header_filter:   Hello,world!, client: 10.19.64.210, server: testnginx.com, request: "GET /test?ss HTTP/1.1", host: "testnginx.com"
2018/06/04 19:16:20 [error] 21019#21019: *920 [lua] test.lua:3: body_filter:   Hello,world!, client: 10.19.64.210, server: testnginx.com, request: "GET /test?ss HTTP/1.1", host: "testnginx.com"
2018/06/04 19:16:20 [error] 21019#21019: *920 [lua] test.lua:3: log:   Hello,world! while logging request, client: 10.19.64.210, server: testnginx.com, request: "GET /test?ss HTTP/1.1", host: "testnginx.com"
```

通过access.log日志截图（如图8-4所示）可以看得更清晰。
![Nginx Lua的执行阶段](https://s1.51cto.com/images/blog/201812/17/2c9eab0a667dda8acea878cf1ddd26ed.png?x-oss-process=image/watermark,size_16,text_QDUxQ1RP5Y2a5a6i,color_FFFFFF,t_100,g_se,x_10,y_10,shadow_90,type_ZmFuZ3poZW5naGVpdGk=)
图8-4　access.log日志截图
观察Ngx_Lua整体的执行流程，可得出如下结论。
Ngx_Lua执行顺序如下：

```
init_by_lua_block
init_worker_by_lua_block
set_by_lua_block
rewrite_by_lua_block
access_by_lua_block
content_by_lua_block
header_filter_by_lua_block
body_filter_by_lua_block
log_by_lua_block
```

指令在配置文件中的顺序不会影响执行的顺序，这一点和Nginx一样。
http阶段的init_by_lua_block和init_worker_by_lua_block只会在配置重载时执行，只进行HTTP请求时，不会被执行。
注意：init_by_lua_file 在lua_code_cache 为off 的情况下，每次执行HTTP请求都会执行重载配置阶段的Lua代码。



> 作者：逸马
>
> 链接：https://blog.51cto.com/xikder/2331649
>
> 来源：51CTO博客