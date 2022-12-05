<?php
declare(strict_types=1);

namespace alanrogers\tools\helpers;

class IP implements HelperInterface
{
    /**
     * Is the supplied ip address a "local" address - NOT public.
     * i.e. something like 192.168.*.*, 127.*.*.* or 10.*.*.*
     * @param string $ip
     * @return bool
     */
    public static function isIPLocal(string $ip) : bool
    {
        if (strpos($ip, '127.0.') === 0) {
            return true;
        }

        return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }
}