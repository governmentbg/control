<?php

declare(strict_types=1);

namespace middleware;

use vakata\http\Request;
use vakata\http\Response;

class ClientIP
{
    public static function ip(): ?string
    {
        $ip = '';
        if (isset($_SERVER['REMOTE_ADDR']) && isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        if (strpos($ip, ',') !== false) {
            $ip = array_reverse(explode(',', $ip))[0];
        }
        $ip = trim($ip);
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6) === false) {
            $ip = null;
        }
        return $ip;
    }
    public static function check(array $masks): bool
    {
        $ip = static::ip();
        if (!isset($ip)) {
            return false;
        }
        return static::checkIP($ip, $masks);
    }

    public static function checkIP(string $ip, array $masks): bool
    {
        $method = substr_count($ip, ':') > 1 ? 'checkIP6' : 'checkIP4';
        foreach ($masks as $mask) {
            if (static::$method($ip, $mask)) {
                return true;
            }
        }
        return false;
    }

    public static function checkIp4(string $ip, string $mask): bool
    {
        if (!filter_var($ip, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4)) {
            return false;
        }

        if (str_contains($mask, '/')) {
            [$address, $netmask] = explode('/', $mask, 2);
            if ('0' === $netmask) {
                return filter_var($address, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4) !== false;
            }
            $netmask = (int)$netmask;
            if ($netmask < 0 || $netmask > 32) {
                return false;
            }
        } else {
            $address = $mask;
            $netmask = 32;
        }

        if (false === ip2long($address)) {
            return false;
        }

        return 0 === substr_compare(sprintf('%032b', ip2long($ip)), sprintf('%032b', ip2long($address)), 0, $netmask);
    }

    public static function checkIp6(string $ip, string $mask): bool
    {
        if (str_contains($ip, '.') || str_contains($mask, '.')) {
            return false;
        }

        if (!filter_var($ip, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV6)) {
            return false;
        }

        if (str_contains($mask, '/')) {
            [$address, $netmask] = explode('/', $mask, 2);

            if ('0' === $netmask) {
                return (bool) unpack('n*', (string)@inet_pton($address));
            }
            $netmask = (int)$netmask;
            if ($netmask < 1 || $netmask > 128) {
                return false;
            }
        } else {
            $address = $ip;
            $netmask = 128;
        }

        $bytesAddr = unpack('n*', (string)@inet_pton($address));
        $bytesTest = unpack('n*', (string)@inet_pton($ip));

        if (!$bytesAddr || !$bytesTest) {
            return false;
        }

        for ($i = 1, $ceil = ceil($netmask / 16); $i <= $ceil; ++$i) {
            $left = $netmask - 16 * ($i - 1);
            $left = ($left <= 16) ? $left : 16;
            $msk = ~(0xFFFF >> $left) & 0xFFFF;
            if (($bytesAddr[$i] & $msk) != ($bytesTest[$i] & $msk)) {
                return false;
            }
        }

        return true;
    }

    public function __invoke(Request $req, callable $next): Response
    {
        $req = $req->withAttribute('client-ip', static::ip());
        return $next($req)->withHeader('X-Client-IP', $req->getAttribute('client-ip') ?? '');
    }
}
