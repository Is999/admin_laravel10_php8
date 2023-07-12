<?php

namespace App\Services;

use App\Enum\LogChannel;
use App\Exceptions\CustomizeException;
use App\Logging\Logger;
use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use GuzzleHttp\Psr7;

class IpService extends Service
{
    /**
     * 生成token
     * @param string $ip
     * @return string
     */
    public static function getIpAddr(string $ip): string
    {
        try {
            if ($ip == '' || $ip == '0.0.0.0' || $ip == '127.0.0.1') {
                return "";
            }
            $client = new  Client ([
                'timeout' => 5.0,
            ]);
            $respList = [];

            // 异步并发请求
            /*$promises = [
                'inte' => self::getIpAddrByInte($client, $ip, $respList),
                'ipapi' => self::getIpAddrByIpApi($client, $ip, $respList),
                'baidu' => self::getIpAddrByBaidu($client, $ip, $respList),
                'vore' => self::getIpAddrByVore($client, $ip, $respList),
            ];


            $results = Promise\Utils::unwrap($promises);
            foreach ($results as $v) {
                if ($v != null) {
                    return $v;
                }
            }*/

            $cf = [
                [
                    'method' => 'getIpAddrByInte', // 方法名
                    'status' => true, // 状态：true 启用， false 禁用
                    'weights' => 4, // 权重：值越大排序越前
                ],
                [
                    'method' => 'getIpAddrByIpApi',
                    'status' => true,
                    'weights' => 2,
                ],
                [
                    'method' => 'getIpAddrByBaidu',
                    'status' => true,
                    'weights' => 3,
                ],
                [
                    'method' => 'getIpAddrByVore',
                    'status' => true,
                    'weights' => 1,
                ],
            ];

            // 过滤
            $list = array_filter($cf, function ($v) {
                return $v['status'];
            });

            // 根据权重排序
            array_multisort(array_column($list, 'weights'), SORT_DESC, $list);

            do {
                $method = array_shift($list);
                Logger::info(LogChannel::DEV, 'get ip method: {{method}}', $method);
                $result = self::{$method['method']}($client, $ip, $respList);
                $result->resolve("广东省深圳市");
                $ipaddr = $result->wait();
                if ($ipaddr) {
                    return $ipaddr;
                }
                if (!$list) {
                    // 循环完毕，未获取到值
                    Logger::error(LogChannel::DEV, '循环完毕，未获取到值', [
                        'ip' => $ip,
                    ]);
                }
            } while ($list);
            return "";
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEV, '获取IP归属地异常', [
                'ip' => $ip,
            ], $e);
            return '';
        }
    }

    public static function getIpAddrByInte(Client &$client, string $ip, array &$respList): PromiseInterface
    {
        $url = "https://www.inte.net/tool/ip/api.ashx?datatype=json&key=12&ip={$ip}";
        return $client->getAsync($url)->then(function (ResponseInterface $response) use ($client, &$respList) {
            $respList['inte'] = "--";
            if ($response->getStatusCode() == 200) {
                $body = json_decode($response->getBody()->getContents(), true);
                if (!empty($body['data']) && isset($body['data'][0]) && isset($body['data'][1])) {
                    $respList['inte'] = $body['data'][0] . $body['data'][1];
                    return $body['data'][0] . $body['data'][1];
                }
            }
            return null;
        }, function (Throwable $e) use ($ip) {
            if ($e instanceof RequestException) {
                $req = Psr7\Message::toString($e->getRequest());
                $rep = null;
                if ($e->hasResponse()) {
                    $rep = Psr7\Message::toString($e->getResponse());
                }
                Logger::error(LogChannel::DEV, '获取IP归属地异常', ['ip' => $ip, 'Request' => $req, 'Response' => $rep], $e);
            } else {
                Logger::error(LogChannel::DEV, '获取IP归属地异常', ['ip' => $ip], $e);
            }
        });
    }

    public static function getIpAddrByIpApi(Client &$client, string $ip, array &$respList): PromiseInterface
    {
        $url = "https://ip-api.com/{$ip}?lang=zh-CN";
        return $client->getAsync($url)->then(function (ResponseInterface $response) use ($client, &$respList) {
            $respList['ipapi'] = '--';
            if ($response->getStatusCode() == 200) {
                $body = json_decode($response->getBody()->getContents(), true);
                if (isset($body['regionName']) && isset($body['city'])) {
                    $respList['ipapi'] = $body['regionName'] . $body['city'];
                    return $body['regionName'] . $body['city'];
                }
            }
            return null;
        }, function (Throwable $e) use ($ip) {
            if ($e instanceof RequestException) {
                $req = Psr7\Message::toString($e->getRequest());
                $rep = null;
                if ($e->hasResponse()) {
                    $rep = Psr7\Message::toString($e->getResponse());
                }
                Logger::error(LogChannel::DEV, '获取IP归属地异常', ['ip' => $ip, 'Request' => $req, 'Response' => $rep], $e);
            } else {
                Logger::error(LogChannel::DEV, '获取IP归属地异常', ['ip' => $ip], $e);
            }
        });
    }

    public static function getIpAddrByBaidu(Client &$client, string $ip, array &$respList): PromiseInterface
    {
        $url = "http://opendata.baidu.com/api.php?co=&resource_id=6006&oe=utf8&query={$ip}";
        return $client->getAsync($url)->then(function (ResponseInterface $response) use ($client, &$respList) {
            $respList['baidu'] = '--';
            if ($response->getStatusCode() == 200) {
                $body = json_decode($response->getBody()->getContents(), true);
                if (!empty($body['data']) && isset($body['data']['location'])) {
                    $respList['baidu'] = $body['data']['location'];
                    return $body['data']['location'];
                }
            }
            return null;
        }, function (Throwable $e) use ($ip) {
            if ($e instanceof RequestException) {
                $req = Psr7\Message::toString($e->getRequest());
                $rep = null;
                if ($e->hasResponse()) {
                    $rep = Psr7\Message::toString($e->getResponse());
                }
                Logger::error(LogChannel::DEV, '获取IP归属地异常', ['ip' => $ip, 'Request' => $req, 'Response' => $rep], $e);
            } else {
                Logger::error(LogChannel::DEV, '获取IP归属地异常', ['ip' => $ip], $e);
            }
        });
    }

    public static function getIpAddrByVore(Client &$client, string $ip, array &$respList): PromiseInterface
    {
        $url = "https://api.vore.top/api/IPdata?ip={$ip}";
        return $client->getAsync($url)->then(function (ResponseInterface $response) use ($client, $respList) {
            $respList['vore'] = '--';
            if ($response->getStatusCode() == 200) {
                $body = json_decode($response->getBody()->getContents(), true);
                if (!empty($body['adcode']) && isset($body['adcode']['o'])) {
                    $respList['vore'] = $body['adcode']['o'];
                    return $body['adcode']['o'];
                }
            }
            return null;
        }, function (Throwable $e) use ($ip) {
            if ($e instanceof RequestException) {
                $req = Psr7\Message::toString($e->getRequest());
                $rep = null;
                if ($e->hasResponse()) {
                    $rep = Psr7\Message::toString($e->getResponse());
                }
                Logger::error(LogChannel::DEV, '获取IP归属地异常', ['ip' => $ip, 'Request' => $req, 'Response' => $rep], $e);
            } else {
                Logger::error(LogChannel::DEV, '获取IP归属地异常', ['ip' => $ip], $e);
            }
        });
    }
}
