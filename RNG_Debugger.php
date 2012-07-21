<?php
/**
 * Class RNG_Debugger
 *
 * A Great Help if you want to debug your Application which should only
 * be displayed to your Trusted IPs.
 *
 * Partial Code taken from Seagull Framework.
 *
 * https://github.com/rungss/php-debugger
 *
 * @author Bijay Rungta <bijay.rungta@gmail.com>
 * @copyright Bijay Rungta http://bijayrungta.com
 * @package RNG
 */
class RNG_Debugger
{
    /**
     * Array of Trusted IPs.
     *
     * @var array
     */
    public static $aTrustedIPs = array(
        '127.0.0.1',    // Localhost
        '::1',          // Localhost in some environments
        '10.0.*.*',     // LAN
        '192.168.*.*',  // LAN
        // 'HHHHH',     // Enter your Office or Home Ip to debug.
    );

    /**
     * Determines current server API, ie, are we running from commandline or webserver.
     *
     * @return boolean
     */
    public static function isRunningFromCLI()
    {
        // STDIN isn't a CLI constant before 4.3.0
        $sapi = php_sapi_name();
        if (version_compare(PHP_VERSION, '4.3.0') >= 0 && $sapi != 'cgi') {
            if (!defined('STDIN')) {
                return false;
            } else {
                return @is_resource(STDIN);
            }
        } else {
            return in_array($sapi, array('cli', 'cgi')) && empty($_SERVER['REMOTE_ADDR']);
        }
    }

    /**
     * Register Trusted IPs.
     *
     * @param string|array of IPs to add to the Trusted List.
     * @return void
     */
    public static function registerTrustedIPs($ip)
    {
        if (is_array($ip)) {
            self::$aTrustedIPs = array_merge(self::$aTrustedIPs, $ip);
        } else {
            self::$aTrustedIPs[] = $ip;
        }
    }

    /**
     *
     * @param boolean $allowCli
     * @return boolean whUser's IP is a Trusted IP.
     */
    public static function isTrustedIP($disallowCli = false)
    {
        $isTrustedIP = false;

        // Allways return true for Local Server.
        if (in_array($_SERVER['SERVER_NAME'], array(
            'localhost',
            '127.0.0.1'
        ))) {
            return true;
        }

        if (!$disallowCli && isRunningFromCLI()) {
            return true;
        }

        if (empty($_SERVER['REMOTE_ADDR'])) {
            return false;
        }

        $endUserIP = self::getRealIpAddress();

        if (!empty(self::$aTrustedIPs)) {
            foreach (self::$aTrustedIPs as $trustedIP) {
                if (@preg_match("/^$trustedIP$/", $endUserIP, $aMatches)) {
                    return true;
                }
            }
        }
        return $isTrustedIP;
    }

    /**
     * Print the Object.
     *
     * @param mixed $obj The Object/Array or any other Variable for debugging.
     * @param string $header Any Heading.
     * @param boolean $exit if true, the Script Execution is halted after printing the Object.
     * @return type
     */
    public static function debugObject($obj = null, $header = '', $exit = false)
    {
        $isTrustedIP = self::isTrustedIP();
        $isRunningFromCLI = self::isRunningFromCLI();
        $strSeparator = "\n" . str_repeat('=', 80);
        if (!$isTrustedIP) {
            return false;
        }

        $headerHtml = $header;
        if (!$isRunningFromCLI && $header && strpos($header, '<h2>', 0) === false
                && strpos($header, '<h3', 0) === false) {
            $headerHtml = '<h2 style="color: #000000;">' . $header . '</h2>';
        }

        // Obtain backtrace information, if supported by PHP
        $backtraceInfo = '';
        if (!$isRunningFromCLI && version_compare(phpversion(), '4.3.0') >= 0) {
            $bt = debug_backtrace();
            $backtraceInfo .= 'Fired from ';
            if (isset($bt[1]['class'])
                && $bt[1]['type']
                && isset($bt[1]['function'])) {
                $backtraceInfo .= 'Method::' . $bt[1]['class'] . $bt[1]['type']
                    . $bt[1]['function'];
            } elseif (isset($bt[1]['function'])) {
                $backtraceInfo .= 'Function::' . $bt[1]['function'];
            }
            if (isset($bt[0]['file']) && isset($bt[0]['line'])) {
                $backtraceInfo .= " line " . $bt[0]['line'] . " of \n\"" . $bt[0]['file'] . '"';
            }
            $headerHtml .= "\n" . '<h3 style="border-bottom: 1px dashed #805E42;">'
                . $backtraceInfo . '</h3>';
            $header .= "\n" . $backtraceInfo . "\n";
        }

        if ($isRunningFromCLI) {
            if ($header) {
                echo $strSeparator;
                echo "\n" . $header;
                echo $strSeparator;
            }
            if (!is_scalar($obj) || $obj != '') {
                echo "\n";
                print_r($obj);
            }
            flush();
        } else {
            echo "\n";
            echo '<pre style="
                background-color: #FAFAFA;
                border: 1px solid #BBBBBB;
                text-align: left;
                font-size: 9pt;
                line-height: 125%;
                margin: 0.5em 1em 1.8em;
                overflow: auto;
                padding: 0.99em;">';
            echo $headerHtml;
            echo "\n";
            // $type = getVariableType($obj);
            // echo "<h4>Object Type: $type</h4>";
            print_r(self::htmlspecialcharsRecursive($obj));
            echo "\n</pre>";
        }

        if (is_callable(array('RNG', 'logMessage'))) {
            $message = print_r($obj, true);
            if (!empty($header)) {
                $message = $header . $message;
            }
            RNG::logMessage($message);
        }

        if ($exit) {
            exit();
        }
    }

    /**
     * Debug the Type of an Object.
     *
     * @param mixed $obj
     * @param string $header
     * @param boolean $exit
     */
    public static function debugObjectType($obj = null, $header = '', $exit = false)
    {
        //Build Header
        if ($header) {
            $header = "<h2>{$header}</h2>";
        }

        // Obtain backtrace information, if supported by PHP
        $backtraceInfo = '';
        if (version_compare(phpversion(), '4.3.0') >= 0) {
            $bt = debug_backtrace();
            $backtraceInfo .= 'Fired from ';
            if (isset($bt[1]['class'])
                && $bt[1]['type']
                && isset($bt[1]['function'])) {
                $backtraceInfo .= 'Method::' . $bt[1]['class'] . $bt[1]['type']
                    . $bt[1]['function'];
            } elseif (isset($bt[1]['function'])) {
                $backtraceInfo .= 'Function::' . $bt[1]['function'];
            }
            if (isset($bt[0]['file']) && isset($bt[0]['line'])) {
                $backtraceInfo .= " line " . $bt[0]['line'] . " of \n\"" . $bt[0]['file'] . '"';
            }
            $backtraceInfo = '<h3 style="border-bottom: 1px dashed #805E42;">'
                . $backtraceInfo . '</h3>';
        }
        $header .= $backtraceInfo . "\n";

        self::debugObject(self::getVariableType($obj), $header, $exit);
    }

    /**
     * Safely Print Object.
     *
     * @param type $obj
     * @param type $header
     * @param type $exit
     * @return type
     */
    public static function debugObjectSafe($obj = null, $header = '', $exit = false)
    {
        if (!self::isTrustedIP()) {
            return false;
        }

        //Build Header
        if ($header) {
            $header = "<h2>{$header}</h2>";
        }

        // Obtain backtrace information, if supported by PHP
        $backtraceInfo = '';
        if (version_compare(phpversion(), '4.3.0') >= 0) {
            $bt = debug_backtrace();
            $backtraceInfo .= 'Fired from ';
            if (isset($bt[1]['class'])
                && $bt[1]['type']
                && isset($bt[1]['function'])) {
                $backtraceInfo .= 'Method::' . $bt[1]['class'] . $bt[1]['type']
                    . $bt[1]['function'];
            } elseif (isset($bt[1]['function'])) {
                $backtraceInfo .= 'Function::' . $bt[1]['function'];
            }
            if (isset($bt[0]['file']) && isset($bt[0]['line'])) {
                $backtraceInfo .= " line " . $bt[0]['line'] . " of \n\"" . $bt[0]['file'] . '"';
            }
            $backtraceInfo = "<h3>$backtraceInfo</h3>";
        }
        $header .= $backtraceInfo . "\n";

        echo "\n";
        echo '<pre style="
            background-color: #FAFAFA;
            border: 1px solid #BBBBBB;
            text-align: left;
            font-size: 9pt;
            line-height: 125%;
            margin: 0.5em 1em 1.8em;
            overflow: auto;
            padding: 0.99em;">';
        echo $header;
        print_r($obj);
        echo "\n</pre>";
        if ($exit) {
            exit();
        }
    }

    /**
     * Get Variable type of an Object. Array, Resource, String..
     *
     * @param mixed $obj
     * @return string
     */
    public static function getVariableType($obj)
    {
        $type = 'unknown';
        if (is_object($obj)) {
            return get_class($obj);
        } elseif (is_resource($obj)) {
            return get_resource_type($obj);
        } elseif (is_array($obj)) {
            return 'Array';
        } elseif (is_numeric($obj)) {
            return 'Number';
        } elseif (is_string($obj)) {
            return 'String';
        }
        return $type;
    }

    /**
     * Print Backtrace Info..
     *
     * @param integer $maxLevel
     * @param string $header
     */
    public static function debugBacktraceInfo($maxLevel = 90, $header = '')
    {
        $bt = debug_backtrace();
        $aDebugObject = array();
        $level = 0;
        foreach ($bt as $btInfo) {
            if ($level > $maxLevel) {
                break;
            }
            $aBTInfo = array(
                'file'      => $btInfo['file'],
                'line'      => $btInfo['line'],
                'function'  => @$btInfo['function'],
                'class'     => @$btInfo['class'],
                'type'      => @$btInfo['type'],
            );
            $aDebugObject[] = $aBTInfo;
            $level++;
        }

        // Obtain backtrace information, if supported by PHP
        $backtraceInfo = '';
        if (version_compare(phpversion(), '4.3.0') >= 0) {
            $bt = debug_backtrace();
            $backtraceInfo .= 'Fired from ';
            if (isset($bt[1]['class'])
                && $bt[1]['type']
                && isset($bt[1]['function'])) {
                $backtraceInfo .= 'Method::' . $bt[1]['class'] . $bt[1]['type']
                    . $bt[1]['function'];
            } elseif (isset($bt[1]['function'])) {
                $backtraceInfo .= 'Function::' . $bt[1]['function'];
            }
            if (isset($bt[0]['file']) && isset($bt[0]['line'])) {
                $backtraceInfo .= " line " . $bt[0]['line'] . " of \n\"" . $bt[0]['file'] . '"';
            }
            $backtraceInfo = '<h3 style="border-bottom: 1px dashed #805E42;">'
                . $backtraceInfo . '</h3>';
        }
        $header .= $backtraceInfo . "\n";

        self::debugObject($aDebugObject, $header);
    }

    /**
     * Recursively Replace all HTML special Characters in a Object to their respective HTML
     * entities to show HTML Code in a visible format.
     *
     * @param mixed $obj
     * @return string String with html special character changed into their entities..
     */
    public static function htmlspecialcharsRecursive($obj = null)
    {
        if (is_array($obj)) {
            foreach ($obj as $key => $value) {
                $obj[$key] = self::htmlspecialcharsRecursive($value);
            }
        } elseif (is_object($obj)) {
            $aObjectVars = get_object_vars($obj);
            foreach ($aObjectVars as $key => $value) {
                $obj->$key = self::htmlspecialcharsRecursive($value);
            }
        } elseif (is_scalar($obj)) {
            $obj = htmlspecialchars($obj);
        }
        return $obj;
    }

    /**
     * Get Real IP Addres for multiple Server/LB Infrastructure.
     *
     * @author Bijay
     * @return string
     */
    public static function getRealIpAddress()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            //check ip from share internet
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            //to check ip is pass from proxy
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }
}