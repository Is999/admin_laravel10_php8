<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>
<p align="center">
<img src="https://raw.githubusercontent.com/Is999/admin_laravel10/master/public/images/adminVbenVue/menu.png" alt="菜单管理">
<img src="https://raw.githubusercontent.com/Is999/admin_laravel10/master/public/images/adminVbenVue/role.png" alt="角色管理">
<img src="https://raw.githubusercontent.com/Is999/admin_laravel10/master/public/images/adminVbenVue/permission.png" alt="权限管理">
<img src="https://raw.githubusercontent.com/Is999/admin_laravel10/master/public/images/adminVbenVue/user.png" alt="账号管理">
<img src="https://raw.githubusercontent.com/Is999/admin_laravel10/master/public/images/adminVbenVue/config.png" alt="参数配置">
<img src="https://raw.githubusercontent.com/Is999/admin_laravel10/master/public/images/adminVbenVue/cache.png" alt="缓存管理">
<img src="https://raw.githubusercontent.com/Is999/admin_laravel10/master/public/images/adminVbenVue/userlog.png" alt="操作日志">
<img src="https://raw.githubusercontent.com/Is999/admin_laravel10/master/public/images/adminVbenVue/setting.png" alt="个人设置">
</p>



## 启动laravel [Laravel Sail](https://learnku.com/docs/laravel/8.5/sail/10428)

1. 进入项目根目录执行下面命令

   ```bash
   ./vendor/bin/sail up
   ```

   

## composer.json 添加引入的文件或包

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

   - 添加完成后执行以下命令,完成自动加载

   ```bash
   composer dump-autoload
   ```

2. 安装包

   ```shell
   # 安装php-jwt
   composer require firebase/php-jwt
   
   # 安装telegram-bot-sdk
   composer require irazasyed/telegram-bot-sdk
   
   # 安装验证码
   composer require mews/captcha
   ```

3. 安装包安装完成后，生成对应的配置文件

   ```shell
   php artisan vendor:publish
   ```

   

4. 引入 lgvpay 包

   - 在项目根目录下创建 libs, 并添加要引入的 lgvpay 包
   - composer.jon 文件里找到autoload.psr-4添加自动加载

   ```json
   {
     "autoload": {
             "psr-4": {
                 "Lgvpay\\Api\\Client\\": "libs/lgvpay/api-client/src/"
             }
         }
     }
   ```

    - 添加完成后执行以下命令,完成自动加载

   ```bash
   composer dump-autoload
   ```

    - 配置自动加载服务

      config/app.php 配置 providers

   ```php
   'providers' => [
   		Lgvpay\Api\Client\ApiProvider::class, //添加 Lgvpay
   ]
   ```

## logger 日志

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

   ```php
   protected $middlewareGroups = [
           'web' => [
              // \App\Http\Middleware\AssignRequestId::class, //日志添加 request_id
           ],
   
           'api' => [
               \App\Http\Middleware\AssignRequestId::class, //日志添加 request_id
           ],
       ];
   ```

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

   

9. 发送异步消息

   1. ### 配置消息队列驱动

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

     ```sh
     -- php artisan make:job 文件名
     php artisan make:job SendVerificationMessage
     ```

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

     

  5. 调用示例

     ```php
     SendVerificationMessage::dispatch($user, $mail);
     ```

  6. 文档参考：https://laravelacademy.org/books/laravel-queue-action

10. Laravel 命令参考

    ```
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
    ```

    ```
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

    

14. 登录密码

    账号：super999 密码：Super@123

​		账号：admin999 密码：Admin@123
