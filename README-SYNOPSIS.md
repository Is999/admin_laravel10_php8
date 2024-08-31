<p align="center" style="font-size: 36px; font-weight: bolder; color: red">Laravel + Vue</p>

<p align="center">
<img src="https://raw.githubusercontent.com/Is999/admin_laravel10/master/public/static/images/adminVbenVue/login.png" alt="登录">
<img src="https://raw.githubusercontent.com/Is999/admin_laravel10/master/public/static/images/adminVbenVue/verifyMFA.png" alt="验证身份验证码">
<img src="https://raw.githubusercontent.com/Is999/admin_laravel10/master/public/static/images/adminVbenVue/mine.png" alt="个人信息">
<img src="https://raw.githubusercontent.com/Is999/admin_laravel10/master/public/static/images/adminVbenVue/security.png" alt="安全设置">
<img src="https://raw.githubusercontent.com/Is999/admin_laravel10/master/public/static/images/adminVbenVue/user.png" alt="账号管理">
<img src="https://raw.githubusercontent.com/Is999/admin_laravel10/master/public/static/images/adminVbenVue/role.png" alt="角色管理">
<img src="https://raw.githubusercontent.com/Is999/admin_laravel10/master/public/static/images/adminVbenVue/roleEdit.png" alt="编辑角色">
<img src="https://raw.githubusercontent.com/Is999/admin_laravel10/master/public/static/images/adminVbenVue/permission.png" alt="权限管理">
<img src="https://raw.githubusercontent.com/Is999/admin_laravel10/master/public/static/images/adminVbenVue/permissionEdit.png" alt="编辑权限">
<img src="https://raw.githubusercontent.com/Is999/admin_laravel10/master/public/static/images/adminVbenVue/menu.png" alt="菜单管理">
<img src="https://raw.githubusercontent.com/Is999/admin_laravel10/master/public/static/images/adminVbenVue/config.png" alt="参数配置">
<img src="https://raw.githubusercontent.com/Is999/admin_laravel10/master/public/static/images/adminVbenVue/cache.png" alt="缓存管理">
<img src="https://raw.githubusercontent.com/Is999/admin_laravel10/master/public/static/images/adminVbenVue/userlog.png" alt="操作日志">
</p>


------

后台：PHP [Laravel](https://laravel.com)  [Laravel 中文文档](https://laravel-docs.catchadmin.com/)

前端：Vue [vue-vben-admin](https://github.com/vbenjs/vue-vben-admin)

交流QQ群：171927930

反馈邮箱：909931038@qq.com



## 搭建Docker环境 [dnmp](https://github.com/Is999/dnmp)

1. 使用dnmp https://github.com/Is999/dnmp/tree/laravel-admin  laravel-admin 分支有相关配置

    1. Nginx 参考配置： https://github.com/Is999/dnmp/blob/laravel-admin/services/nginx/conf.d/admin.conf

    2. 本地hosts 配置：127.0.0.1 www.admin.cc

    3. 项目存放路径 .env 参数 SOURCE_DIR，可修改该路径。laravel-admin 分支配置的默认路径【SOURCE_DIR=../www】dnmp同级目录下的www下面。


2. 安装Laravel vendor依赖包, 进入项目根目录执行下面命令

   ```sh
    composer install
   ```


3. 数据库使用Mysql,
   表数据导入到数据库：https://github.com/Is999/laravel-admin/blob/master/database/mysql/admin_db_all_table.sql


4. 加密、解密、签名、验签（可参考登录接口）

    1. 签名、验签 /app/Http/Middleware/SignatureData.php

       ```
       * 对响应和请求的`敏感数据`进行签名和验签
       * 
       * 签名和验签支持RSA、AES、MD5
       *
       * 签名及验证签名规则在 \App\Enum\SignRules 定义
       ```
    
       签名规则示例：
    
       ```php
       /**
        * 签名规则
        * key 路由
        * value 签名参数
        */
       const array signRules = [
           'user.login' => self::userLogin,
       ];
       
       /**
        * 登录签名参数
        * request 请求签名参数
        * response 响应签名参数
        */
       const array userLogin = [
           'request' => ['name', 'password', 'secureCode'],
           'response' => ['token'],
       ];
       ```
    
       
    
    2. 加密、解密 /app/Http/Middleware/CryptoData.php
    
       ```php
       * 对响应和请求的`敏感数据`进行加密解密
       *
       * 加密解密支持 RSA、AES
       *
       * 加密解密的参数放在header['X-Cipher']中：
       * 1. 整体加密解密：`X-Cipher`值等于cipher，加密解密ciphertext参数或body数据；
       * 2. 细分加密解密：`X-Cipher`值不等于cipher，原始类型是一个数组，进行了json编码和base64编码；
       *
       * 注意事项：加密解密的参数只能是请求或响应的`首层数据`
       * Array或者Object类型的数据要在参数前面标记`json:`，否则不用标记
       *
       * 响应头设置示例：
       * response()->header('X-Cipher', base64_encode(json_encode(['json:user'，'token'])))
       * response()->header('X-Cipher', 'cipher')
       ```
    
       加密：对响应数据进行整体加密或部分数据精准加密
    
       ```php
       // 参考 \App\Http\Controllers\UserController::login 
       // 返回加密数据：对user参数加密，user在这里是一个数组对象，签名要加json: 标记
       return Response::success(['user' => $userInfo, 'token' => $userService->generateToken($user)])->header('X-Cipher', base64_encode(json_encode(['json:user'，'token'])));
       
       // 参考 \App\Http\Controllers\UserController::mine
       // 返回加密数据: 对所有数据加密
       return Response::success($userInfo)->header('X-Cipher', 'cipher');
       ```
    
       解密：请参考 /app/Http/Middleware/CryptoData.php
    
       
    
    3. 配置RSA 秘钥和AES秘钥示例
    
     ```sh
     # 管理后台前端应用(secret_key表第一条记录) YWRtaW4wMDAx 测试PEM秘钥文件，可自己重新生成秘钥
      
     # 创建目录
     mkdir ./storage/app/pem 
      
     # 复制测试文件
     cp ./tests/Temp/pem/private.pem ./storage/app/pem/
     cp ./tests/Temp/pem/public.pem ./storage/app/pem/
     cp ./tests/Temp/pem/public_user_YWRtaW4wMDAx.pem ./storage/app/pem/
     ```


5. 根据自身的相关环境修改Laravel配置 .env, 到这里就可以正常运行项目了！

   ```sh
   cp .env.example .env # 复制环境变量文件
   ```


6. 前端页面使用Vue项目： https://github.com/Is999/vue-vben-admin


7. 登录密码，谷歌、微软身份验证器

   账号：super999 密码：Super@123 身份验证器MFA秘钥： **JXI4KHCZYC7NJZPE**

   账号：admin999 密码：Admin@123 身份验证器MFA秘钥：**HSVZSFSGGJGUF7YO**

------

## 其它事项记录：

### 添加引入的文件或三方包

1. 引入自定义帮助函数 app/Helpers/function.php

   - app目录下创建Helpers目录并在Helpers目录下创建function.php
   - composer.jon 文件里找到autoload.files添加自动加载

   ```json
   {
     "autoload": {
             "files": [
                 "app/Helpers/function.php"
             ]
         }
     }
   ```



2. 安装包

   ```shell
   # 安装ext-openssl
   composer require ext-openssl
   # 安装ext-redis
   composer require ext-redis
   # 安装guzzlehttp/guzzle
   composer require guzzlehttp/guzzle
   
   # 安装php-jwt
   composer require firebase/php-jwt
   
   # 安装telegram-bot-sdk
   composer require irazasyed/telegram-bot-sdk
   
   # 生成对应的配置文件
   php artisan vendor:publish --provider="Telegram\Bot\Laravel\TelegramServiceProvider"
   
   # 安装验证码
   composer require mews/captcha
   
   # 生成对应的配置文件
   php artisan vendor:publish --provider="Mews\Captcha\CaptchaServiceProvider"
   
   # 安装基于时间的一次性密码(TOTP)验身份验证器
   composer require earnp/laravel-google-authenticator
   # 安装二维码生成器
   composer require simplesoftwareio/simple-qrcode
   
   # 等待下载安装完成，需要在`bootstrap/providers.php`中注册服务提供者：
   # Earnp\GoogleAuthenticator\GoogleAuthenticatorServiceprovider::class
   
   
   # 生成对应的配置文件
   php artisan vendor:publish --provider="Earnp\GoogleAuthenticator\GoogleAuthenticatorServiceProvider"
   
   
   ```


3. 安装包安装完成后，生成对应的配置文件

   ```shell
   php artisan vendor:publish
   ```


4. 添加完成后执行以下命令,完成自动加载

    ```bash
   composer dump-autoload
   ```
   
   

### logger 日志

 App\Logging\Logger 日志类

1. 系统日志(默认通道)


2. DB sql日志（sqlLog通道） 需要配置 config/logging.php 配置sqlLog 通道 记录sql日志, .env文件 DB_LOG 控制是否记录日志


3. DEV日志（devLog通道） config/logging.php 配置devLog 通道 开发人员专用日志通道


4. 网关日志（gatewayLog通道）记录请求日志


5. 配置 config/logging.php

   ```php
   'channels' => [
      // sql日志
       'sqlLog' => [
           'driver' => 'daily',
           'tap' => [App\Logging\CustomizeFormatter::class], // 自定义日志格式解析类
           'path' => storage_path('logs/sql-log.log'),
           'level' => env('LOG_LEVEL', 'info'),
           'days' => 10,
       ],
   
       // 开发调试日志
       'devLog' => [
           'driver' => 'daily',
           'tap' => [App\Logging\CustomizeFormatter::class], // 自定义日志格式解析类
           'path' => storage_path('logs/dev-log.log'),
           'level' => env('LOG_LEVEL', 'debug'),
           'formatter_with' => [
               'dateFormat' => 'Y-m-d H:i:s.u',
           ],
           'days' => 30,
       ],
   
       // 网关日志
       'gatewayLog' => [
           'driver' => 'daily',
           'tap' => [App\Logging\CustomizeFormatter::class], // 自定义日志格式解析类
           'path' => storage_path('logs/gateway-log.log'),
           'level' => env('LOG_LEVEL', 'debug'),
           'formatter_with' => [
               'dateFormat' => 'Y-m-d H:i:s.u',
           ],
           'days' => 30,
       ],
   ],
   ```

   

6. 日志上下文消息, 详细参考(App\Http\Middleware\AssignRequestId) 中间件

   `app/Http/Kernel.php` 文件中为中间件分配一个键, 项目使用api, 在api下面配置

   


7. DB日志监控

   - 添加以下代码到 App\Providers\AppServiceProvider::boot 方法中,  并配置.env DB_LOG 参数

   ```php
   //sql 日志
   try {
       if (Env::get('DB_LOG') == true) {
           DB::listen(function ($query) {
               $sql = $query->sql;
               foreach ($query->bindings as $key => $value) {
                   if ($value instanceof \DateTime) {
                       $value = $value->format('Y-m-d H:i:s');
                   }
                   $rkey = is_numeric($key) ? '?' : ':' . $key;
                   $sql = Str::replaceFirst($rkey, "'{$value}'", $sql);
               }
   
               if ($query->time > 2000) { //添加慢日志
                   Logger::warning(LogChannel::SQL, sprintf('[%s] %s', $query->time, $sql));
                   return;
               }
               Logger::info(LogChannel::SQL, sprintf('[%s] %s', $query->time, $sql));
           });
       }
   } catch (\Exception $e) {
       Logger::error(LogChannel::DEFAULT,'sql log 写入失败', [], $e);
   }
   ```

   

8. Logger 使用示例

   ```tex
   
    * ----------------------------------------
    * 使用示例:
    * 这里只定义了 error、 warning、notice、 info、 debug 函数
    * 其它未定义的日志函数通过 __callStatic 函数调用 laravel log 函数
    * 支持 emergency、 alert、 critical、 error、 warning、 notice、 info、 debug
    * warning 及其之上的日志级别支持第四个参数: 实现Throwable接口的异常对象
    *
    * ----------------------------------------
    * 示例1: Logger::debug(LogChannel::DEFAULT, '{{name}}正在{{doing}}', ['name' => '小明', 'doing'=>'玩游戏']);
    * ----------------------------------------
    * 示例2: Logger::info(LogChannel::DEV, '{{0}}正在{{1}}', ['小明',  '读书']);
    * ----------------------------------------
    * 示例3: Logger::critical(LogChannel::DEV, '{{0}}正在{{1}}', ['小明',  '读书']);
    
   ```

### 发送异步消息

1. 配置消息队列驱动

   ```sh
   -- queue参数：
   -- ⇂ queue:batches-table  
   -- ⇂ queue:clear  
   -- ⇂ queue:failed  
   -- ⇂ queue:failed-table  
   -- ⇂ queue:flush  
   -- ⇂ queue:forget  
   -- ⇂ queue:listen  
   -- ⇂ queue:monitor  
   -- ⇂ queue:prune-batches  
   -- ⇂ queue:prune-failed  
   -- ⇂ queue:restart  
   -- ⇂ queue:retry  
   -- ⇂ queue:retry-batch  
   -- ⇂ queue:table  
   -- ⇂ queue:work 
   
   
   php artisan queue:table
   
   -- 使用redis 跳过 
   php artisan migrate
   ```


2. 配置 .env 将消息队列驱动配置为 `database`

   ```ini
   QUEUE_CONNECTION=database
   ```


3. 启动消息队列应用进程

   ```
   -- 运行队列
   php artisan queue:work
   
   -- 本地开发可以使用
   php artisan queue:listen
   
   -- 指定连接 & 队列
   php artisan queue:work redis --queue=emails
   
   -- 进程睡眠时间
   php artisan queue:work --sleep=3
   
   -- 队列优先级
   php artisan queue:work --queue=high,low
   
   -- queue:restart 命令优雅地重新启动所有进程
   php artisan queue:restart
   ```

   如果系统很繁忙，一个队列处理进程忙不过来，可以启动多个进程并行处理，以便充分利用系统资源（多核 CPU），加快队列处理速度


4. 创建消息队列文件实现异步

    1. 使用命令创建文件

       ```sh
       -- php artisan make:job 文件名
       php artisan make:job SendVerificationMessage
       ```

     2. 文件

        ```php
        <?php
        
        namespace App\Jobs;
        
        use App\Models\User;
        use Illuminate\Bus\Queueable;
        use Illuminate\Contracts\Queue\ShouldBeUnique;
        use Illuminate\Contracts\Queue\ShouldQueue;
        use Illuminate\Foundation\Bus\Dispatchable;
        use Illuminate\Queue\InteractsWithQueue;
        use Illuminate\Queue\SerializesModels;
        use Illuminate\Support\Facades\Mail;
        
        class SendVerificationMessage implements ShouldQueue
        {
            use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
        
            public $user;
           public $mail;
        
            /**
             * Create a new job instance.
             */
            public function __construct(User $user, $mail)
            {
                $this->user=$user;
                $this->mail=$mail;
            }
        
            /**
             * Execute the job.
             */
            public function handle(): void
            {
                Mail::to($this->user)->send($this->mail);
            }
        }
        ```

        

     3. 调用示例

        ```
          SendVerificationMessage::dispatch($user, $mail);
        ```

### 参考文档和命令

1. 文档参考：https://laravelacademy.org/books/laravel-queue-action


2. Laravel 命令参考

    ```sh
    
    php artisan cache:clear：清除应用程序缓存
    php artisan command:make 命令名：在 app/commands 目录下生成一个名为 命令名.php 的自定义命令文件
    php artisan controller:make 控制器名：在 app/controllers 目录下生成一个名为 控制器名.php 的控制器文件
    php artisan db:seed：对数据库填充种子数据，以用于测试
    php artisan key:generate：生成一个随机的 key，并自动更新到 app/config/app.ph 的 key 键值对
    php artisan migrate:install：初始化迁移数据表
    php artisan migrate:make 迁移名：这将在 app/database/migrations 目录下生成一个名为 时间+迁移名.php 的数据迁移文件，并自动执行一次 php artisan dump-autoload 命令
    php artisan migrate:refresh：重置并重新执行所有的数据迁移
    php artisan migrate:reset：回滚所有的数据迁移
    php artisan migrate:rollback：回滚最近一次数据迁移
    php artisan session:table：生成一个用于 session 的数据迁移文件
    
    php artisan：显示详细的命令行帮助信息，同 php artisan list
    php artisan –help：显示帮助命令的使用格式，同 php artisan help
    php artisan –version：显示当前使用的 Laravel 版本
    php artisan changes：列出当前版本相对于上一版本的主要变化
    php artisan down：将站点设为维护状态
    php artisan up：将站点设回可访问状态
    php artisan optimize：优化应用程序性能，生成自动加载文件，且产生聚合编译文件 bootstrap/compiled.php
    php artisan dump-autoload：重新生成框架的自动加载文件，相当于 optimize 的再操作
    php artisan clear-compiled：清除编译生成的文件，相当于 optimize 的反操作
    php artisan migrate：执行数据迁移
    php artisan routes：列出当前应用全部的路由规则
    php artisan serve：使用 PHP 内置的开发服务器启动应用 【要求 PHP 版本在 5.4 或以上】
    php artisan tinker：进入与当前应用环境绑定的 REPL 环境，相当于 Rails 框架的 rails console 命令
    php artisan workbench 组织名/包名：这将在应用根目录产生一个名为 workbench 的文件夹，然后按 组织名/包名 的形式生成一个符合 Composer 标准的包结构，并自动安装必要的依赖【需要首先完善好 app/config/workbench.php 文件的内容】
    ```

​		

