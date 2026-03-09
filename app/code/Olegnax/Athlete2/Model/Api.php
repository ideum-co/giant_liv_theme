<?php


namespace Olegnax\Athlete2\Model;


use Closure;
use Exception;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Filesystem\Directory\ReadFactory;
use Olegnax\Core\Helper\Helper;

class Api
{
    const THEME_PATH = 'frontend/Olegnax/athlete2';

    public static function activate(Client $a, Closure $b, $c)
    {
        // Prepare
        $d = $b();
        if (!is_a($d, Request::class)) {
            throw new Exception(__('Closure must return an object instance of Request.'));
        }
        // Call
        if (!array_key_exists('domain', $d->request) || empty($d->request['domain'])) {
            $d->request['domain'] = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'Unknown';
        }
        $d->request['domain'] = preg_replace('/^(www|dev)\./i', '', $d->request['domain']);
        $d->request['meta'] = static::getMeta();
        $d->request['meta']['activate_user_ip'] = $d->request['meta']['user_ip'];
        unset($d->request['meta']['user_ip']);
        static::log(1000);
        $e = $a->call(base64_decode('bGljZW5zZV9rZXlfYWN0aXZhdGU='), $d);
        if ($e && isset($e->error)
            && $e->error === false
        ) {
            if (isset($e->notices)) {
                $d->notices = (array)$e->notices;

            }
            $d->data = (array)$e->data;
            $d->touch();
            call_user_func($c, (string)$d);
            static::log(2000);
        } elseif ($d) {
            static::log(4000, [(array)$d->errors]);
        } else {
            static::log(3000);
        }

        return $e;
    }

    private static function getMeta()
    {
        return [
            'user_ip' => static::getClientIp(),
            'magento_version' => static::getMagentoVersion(),
            'theme_version' => static::getThemeVersion(static::THEME_PATH),
        ];
    }

    public static function getClientIp()
    {
        return isset($_SERVER['HTTP_CLIENT_IP'])
            ? $_SERVER['HTTP_CLIENT_IP']
            : (
            isset($_SERVER['REMOTE_ADDR'])
                ? $_SERVER['REMOTE_ADDR']
                : (
            isset($_SERVER['REMOTE_HOST'])
                ? $_SERVER['REMOTE_HOST']
                : 'UNKNOWN'
            )
            );
    }

    private static function getMagentoVersion()
    {
        return ObjectManager::getInstance()->get(ProductMetadataInterface::class)->getVersion();
    }

    private static function getThemeVersion($a)
    {
        /** @var ComponentRegistrarInterface $b */
        $b = ObjectManager::getInstance()->get(ComponentRegistrarInterface::class);
        $c = $b->getPath(ComponentRegistrar::THEME, $a);
        if ($c) {
            /** @var ReadFactory $d */
            $d = ObjectManager::getInstance()->get(ReadFactory::class);
            $e = $d->create($c);
            if ($e->isExist('composer.json')) {
                $f = (string)$e->readFile('composer.json');
                $f = json_decode($f, true);
                if (isset($f['version'])) {
                    return $f['version'];
                }
            }
        }
        return '';
    }

    protected static function log($message, array $context = [])
    {
        $level = (int)(floor($message / 1000) * 100);
        static::_log($level, $message, $context);
    }

    protected static function _log($level, $message, array $context = [])
    {
        ObjectManager::getInstance()->get(Helper::class)->log($level, 'Athlete2', $message, $context);
    }

    public static function validate(
        Client $a,
        Closure $b,
        $c,
        $d = null,
        $f = false,
        $g = false,
        $h = 2,
        $i = '+1 hour'
    ) {
        // Prepare
        $j = $b();
        if (!is_a($j, Request::class)) {
            static::log(3010);
            throw new Exception(' \Closure must return an object instance of Request.');
        }
        $j->updateVersion();
        // Check j data

        if ($j->isEmpty || empty($j->data['the_key'])) {
            static::log(3020);
            return false;
        }
        // No need to check if j already expired.
        if ('active' != $j->data['status']) {
            static::log(1010);
            return false;
        }
        // Validate cached j data
        if (!$f
            && time() < $j->nextCheck
            && $j->isValid
        ) {
            return true;
        }
        // Call
        if (empty($d)) {
            $d = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'Unknown';
        }
        $j->request['domain'] = preg_replace('/^(www|dev)\./i', '', $d);
        $j->request['meta'] = static::getMeta();
        $k = null;
        try {
            static::log(1000);
            $k = $a->call(base64_decode('bGljZW5zZV9rZXlfdmFsaWRhdGU='), $j);
        } catch (Exception $e) {
            static::_log(400, $e->getMessage());
            $k = null;
        }
        if (empty($k)) {
            static::log(3000);
            if ($g && $j->retries < $h) {
                $j->addRetryAttempt($i);
                static::log(3020,
                    [$j->retries . '/' . $h]);
                call_user_func($c, (string)$j);
                return true;
            } else {
                static::log(3030);
            }
        } elseif (isset($k->error)) {
            $j->data = isset($k->data) ? (array)$k->data : [];
            if ($k->error && isset($k->errors)) {
                static::log(4010, [(array)$k->errors]);
                $j->data = ['errors' => $k->errors];
            } else {
                static::log(2020);
            }
            $j->touch();
            call_user_func($c, (string)$j);
            return $k->error === false;
        }

        return false;
    }

    public static function deactivate(Client $a, Closure $b, $c, $d = null)
    {
        // Prepare
        $e = $b();
        if (!is_a($e, Request::class)) {
            static::log(3010);
            throw new Exception(' \Closure must return an object instance of Request.');
        }
        $e->updateVersion();
        // Call
        if (empty($d)) {
            $d = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'Unknown';
        }
        $e->request['domain'] = preg_replace('/^(www|dev)\./i', '', $d);
        $e->request['meta'] = static::getMeta();
        static::log(1000);
        $f = $a->call(base64_decode('bGljZW5zZV9rZXlfZGVhY3RpdmF0ZQ=='), $e);
        // Remove e
        if ($f && isset($f->error)) {
            if ($f->error === false) {
                call_user_func($c, null);
                static::log(2010);
            } else {
                if (isset($f->errors)) {
                    foreach ($f->errors as $key => $message) {
                        if ($key === base64_decode('YWN0aXZhdGlvbl9pZA==')) {
                            call_user_func($c, null);
                            static::log(2010);
                            break;
                        }
                    }
                }
            }
        } else {
            static::log(3000);
        }
        return $f;
    }

    public static function softValidate(Closure $a)
    {
        // Prepare
        $b = $a();
        if (!is_a($b, Request::class)) {
            static::log(3010);
            throw new Exception(' \Closure must return an object instance of Request.');
        }
        $b->updateVersion();
        // Check b data
        if ($b->isEmpty || !$b->data['the_key']) {
            static::log(3020);
            return false;
        }
        if ('active' != $b->data['status']) {
            static::log(1010);
            return false;
        }
        // Validate cached b data
        return $b->isValid;
    }

    public static function check(
        Client $a,
        Closure $b,
        $c,
        $j,
        $g = false,
        $h = 2,
        $i = '+1 hour'
    ) {
        // Prepare
        $d = $b();
        if (!is_a($d, Request::class)) {
            static::log(3010);
            throw new Exception(' \Closure must return an object instance of Request.');
        }
        $d->updateVersion();
        // Check d data
        if ($d->isEmpty || !$d->data['the_key']) {
            static::log(3020);
            return false;
        }
        if ('active' != $d->data['status']) {
            static::log(1010);
            return false;
        }
        // Call
        if (empty($j)) {
            $j = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'Unknown';
        }
        $d->request['domain'] = preg_replace('/^(www|dev)\./i', '', $j);
        $f = null;
        try {
            static::log(1000);
            $f = $a->call('license_key_validate', $d);
        } catch (Exception $e) {
            static::_log(400, $e->getMessage());
            $f = null;
        }
        if (empty($f)) {
            static::log(3000);
            if ($g && $d->retries < $h) {
                $d->addRetryAttempt($i);
                static::log(3020,
                    [$d->retries . '/' . $h]);
                call_user_func($c, (string)$d);
                return true;
            } else {
                static::log(3030);
            }
        } elseif (isset($f->error)) {
            $d->data = isset($f->data) ? (array)$f->data : [];
            if ($f->error && isset($f->errors)) {
                static::log(4010, [(array)$f->errors]);
                $d->data = ['errors' => $f->errors];
            } else {
                static::log(2020);
            }
            $d->touch();
            call_user_func($c, (string)$d);
            return $f->error === false;
        }

        return $f;
    }
}
