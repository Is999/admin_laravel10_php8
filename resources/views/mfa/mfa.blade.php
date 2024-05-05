<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="author" content="phpartisan.cn"/>
    <title>绑定身份验证器</title>
    <script type="text/javascript">
        function startTime() {
            //获取当前系统日期
            var myDate = new Date();
            var y = myDate.getFullYear(); //获取当前年份(2位)
            var m = myDate.getMonth() + 1; //获取当前月份(0-11,0代表1月)
            var d = myDate.getDate(); //获取当前日(1-31)
            var h = myDate.getHours(); //获取当前小时数(0-23)
            var mi = myDate.getMinutes(); //获取当前分钟数(0-59)
            var s = myDate.getSeconds(); //获取当前秒数(0-59)
            var hmiao = myDate.getMilliseconds(); //获取当前毫秒数(0-999)
            //s设置层txt的内容
            document.getElementById('txt').innerHTML = y + "-" + checkTime(m) + "-" + checkTime(d) + " " + checkTime(h) + ":" + checkTime(mi) + ":" + checkTime(s);
            //过500毫秒再调用一次
            t = setTimeout('startTime()', 500)

            //小于10，加0
            function checkTime(i) {
                if (i < 10) {
                    i = "0" + i
                }
                return i
            }
        }

        function startCountdown(duration, display) {
            let timer = duration, minutes, seconds;
            const intervalId = setInterval(function () {
                minutes = parseInt(timer / 60, 10);
                seconds = parseInt(timer % 60, 10);

                minutes = minutes < 10 ? "0" + minutes : minutes;
                seconds = seconds < 10 ? "0" + seconds : seconds;
                document.getElementById(display).innerHTML = minutes + ":" + seconds;

                if (--timer < 0) {
                    timer = duration;
                }
            }, 1000);

            // 5秒后关闭定时器
            setTimeout(function () {
                clearInterval(intervalId); // 使用标识符关闭定时器
                console.log("定时器已关闭。");
            }, 1000 * duration); // 5秒后执行
        }

        @if(Session::has('exist_mfa'))
        // 启动到计时
        const duration = 5; // 单位秒
        startCountdown(duration, "time");
        // 关闭页面
        setTimeout('window.close()', 1000 * duration);
        @else
        // 启动到计时
        const ttl = parseInt({{$ttl}}); // 单位秒
        startCountdown(ttl, "ttl");

        // 刷新页面
        if (ttl > 0) {
            console.log('@@@ttl',ttl);
            setTimeout('location.reload()', 1000 * ttl);
        }
        @endif
    </script>
    <style type="text/css">
        body {
            background-color: #2E363F;
            margin: 0px;
            padding: 0px;
        }

        ul, li {
            list-style: none;
            padding: 0px;
            margin: 0px;
        }

        .container {
            width: 98%;
            max-width: 1000px;
            min-width: 600px;
            background-color: #FFF;
            height: 1800px;
            padding: 20px 20px 100px 20px;
            margin: 30px auto;
            line-height: 25px;
            font-family: 微软雅黑;
            font-size: 15px;
            color: #666666;
        }

        .container span {
            font-weight: bold;
            color: #666666;
            font-size: 15px;
            line-height: 35px;
        }

        .container h3 {
            font-size: 24px;
            color: #333333;
        }

        .container h4 {
            font-size: 18px;
            color: #333333;
        }

        .container h5 {
            font-size: 15px;
            color: #333333;
        }

        .discription {
            width: 100%;
            height: auto;
            border-bottom: thin dashed #CCC;
            padding-bottom: 20px;
            float: left;
        }

        .appdownloadcode {
            width: 100%;
            height: 230px;
            padding-top: 20px;
        }

        .appdownloadcode li {
            width: 50%;
            float: left;
            text-align: center;
        }

        .appdownloadcode img {
            width: 200px;
            height: 200px;
            margin-left: auto;
            margin-right: auto;
        }

        .container-form {
            width: 100%;
            text-align: center;
            margin-top: 10px;
            padding-bottom: 10px;
            float: left;
            border-top: thin dashed #CCC;
            border-bottom: dashed #CCC 3px;
        }

        .container-form img {
            width: 250px;
            height: 250px;
            margin: 0px auto 10px auto;
            padding: 5px;
            border: thin solid #CCC;
            border-radius: 10px;
        }

        .verificationcode {
            width: 300px;
            height: 35px;
            outline: none;
            border: thin solid #CCC;
            font-family: 微软雅黑;
            font-size: 14px;
            padding-left: 20px;
            margin-top: 20px;
        }

        .submit-button {
            width: 150px;
            height: 35px;
            border-radius: 2px;
            outline: none;
            background-color: #0C6;
            color: #FFF;
            font-family: 微软雅黑;
            border: none;
            font-size: 15px;
            margin-top: 20px;
        }

        a {
            color: #09C;
        }

        .notice {
            width: 100%;
            float: left;
            color: #FF6666;
            margin-top: 20px;
        }

        .auth-title {
            color: #003cff;
            font-weight: bolder;
        }
    </style>
</head>

<body onload="startTime()">
<div class="container">
    <h3>绑定身份验证器(MFA)</h3>
    <div class="container-form">
        <p><span id="ttl">05:00</span> 本页面会刷新并重置二维码，请重新扫描</p>
        {!! QrCode::encoding('UTF-8')->size(200)->margin(1)->generate($createSecret["codeurl"]); !!}
        <br/>服务器当前时间为：<span id="txt"></span>
        <br/>如果图片无法显示或者无法扫描，请在手机登录器中手动输入:
        <span style="color: #FF6666">{{ $createSecret["secret"] }}</span>
        <form action="{{ empty(Config::get('google.authenticatorurl')) ? URL::current() : Config::get('google.authenticatorurl') }}"
              method="POST">
            {!! csrf_field() !!}
            @if(!Session::has('exist_mfa'))
                @if(!Session::has('error_mfa'))
                    <input name="onecode" type="text" maxlength="6" class="verificationcode" placeholder="请输入扫描后手机显示的6位动态密码"
                           value="{{ old('onecode') }}"/>
                    @foreach($parameter as $parame)
                        <input type="hidden" name="{{ $parame['name'] }}" value="{{ $parame['value'] }}"/>
                    @endforeach

                    <input type="hidden" name="google" value="{{ $createSecret['secret'] }}"/>
                    <br/>
                    <button class="submit-button">立即绑定</button>
                @endif
            @else
                <div class="notice" style="color: green; font-weight: bolder; font-size: 1.3em">恭喜你绑定成功！本页面将在<span id="time">00:05</span>秒后关闭
                </div>
            @endif
            @if(Session::has('msg'))
                <div class="notice">{{ Session::get('msg') }}</div>
            @endif
        </form>
    </div>

    <div class="discription">
        <h4>使用说明：</h4>
        <p style="font-size: 12px; color: #7c8087; margin-left: 1em">
            身份验证器（TOTP MFA 应用程序）<br/>
            - TOTP：基于时间的动态密码；<br/>
            - MFA：多重身份验证，如两步验证（2FA），常用于登录或其它敏感操作的身份验证；<br/>
            - 常用的身份验证器APP(基于时间的动态密码 (TOTP) 多重身份验证 (MFA))：Google
            Authenticator、Microsoft Authenticator、Authing令牌、宁盾令牌 ......，可在应用市场搜索下载
        </p>
        <h5>步骤一：</h5>
        <p>1. 手机下载安装 <span class="auth-title">Google Authenticator</span></p>
        <p style="margin-left: 1em">请参考：<a href="https://support.google.com/accounts/answer/1066447?hl=zh-Hans"
                                              target="_blank">Google
                Authenticator帮助文档</a> 或扫描下方二维码</p>
        <div class="appdownloadcode">
            <ul>
                <li>
                    <img src="/images/mfa/ios-google.png" width="280" height="280"/><br/>Ios扫描下载
                </li>
                <li>
                    <img src="/images/mfa/android-google.png" width="280" height="280"/><br/>安卓扫描下载
                </li>
            </ul>
        </div>

        <hr>
        <p>2. 手机下载安装 <span class="auth-title">Microsoft Authenticator</span></p>
        <p style="margin-left: 1em">请参考：<a
                    href="https://support.microsoft.com/zh-cn/account-billing/%E4%B8%8B%E8%BD%BD%E5%B9%B6%E5%AE%89%E8%A3%85microsoft-authenticator%E5%BA%94%E7%94%A8-351498fc-850a-45da-b7b6-27e523b8702a"
                    target="_blank">Microsoft Authenticator帮助文档</a> 或扫描下方二维码</p>
        <div class="appdownloadcode">
            <ul>
                <li>
                    <img src="/images/mfa/ios-microsoft.png" width="280" height="280"/><br/>Ios扫描下载
                </li>
                <li>
                    <img src="/images/mfa/android-microsoft.png" width="280" height="280"/><br/>安卓扫描下载
                </li>
            </ul>
        </div>

        <hr>
        <p>3. 手机下载安装 <span class="auth-title">其它的身份验证器</span></p>
        <p style="margin-left: 1em">更多身份验证器可在应用市场搜索下载</p>

        <h5>步骤二：</h5>
        <p>
            软件安装完成后，选择开始设置-扫描条形码，来扫描本页面的二维码，扫描成功后，您手机里的身份验证器（MFA）会生成一个与您账户对应的六位动态密码，每30秒变化一次。</p>
        <h5>步骤三：</h5>
        <p>之后您每次登陆时都需要输入身份验证器（MFA）动态密码，无论手机是否连接网络都可以使用。在允许时间内输入有效数字，保证了账户安全。
        </p>
    </div>
</div>
</body>
</html>