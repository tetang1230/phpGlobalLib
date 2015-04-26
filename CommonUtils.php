<?php

class CommonUtils {
    
    static $CACHE = array ();
    
    /**
     * random out a string
     * @param unknown_type $length
     * @param unknown_type $string
     * @return Ambigous <string, unknown>
     */
    public final static function /*string*/ random($length = 8, $string = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz') {
        $random = '';
        $strlen = strlen($string);
        
        for($i = 0; $i < $length; ++$i) {
            $random .= $string{mt_rand(0, $strlen - 1)};
        }
        
        return $random;
    }
    
    /**
     * Chek the passed sig whether equals the sig value by the key
     * @param unknown_type $value
     * @param unknown_type $pass_sig
     * @return boolean
     */
    public final static function /*bool*/ checkCommonSig($value, $pass_sig) {
        $calculate_sig = base64_encode(md5($value));
        return strcmp($pass_sig, $calculate_sig) === 0;
    }
    
    /**
     * Generate the sig for array values
     * @param unknown_type $values
     * @return string|NULL
     */
    public final static function generateSig($values = array()) {
        if (!empty($values)) {
            return base64_encode(md5(implode('', $values)));
        }
        return null;
    }
    
    /**
     * generate a uniqid
     * @return string
     */
    public final static function genUniqid() {
        return md5(uniqid(rand(), true));
    }
    
    /**
     * Check the http get url sig
     * @param string $seed
     * @param string $sig_field
     * @return boolean
     */
    public final static function checkHttpGetSig($seed = '', $sig_field = 'sig') {
        if(empty($_GET) || !isset($_GET[$sig_field])) {
            return false;
        }
        
        $sig = self::calculateHttpGetSig($seed, $sig_field);
        return strcmp($_GET[$sig_field], $sig) === 0;
    }
    
    /**
     * Calculate the http get sig
     * @param string $seed
     * @param string $sig_field
     * @return string
     */
    public final static function calculateHttpGetSig($seed = '', $sig_field = 'sig') {
        // not contains sig
        $get_query_str = '';
        foreach ($_GET as $key => $value) {
            if($key === $sig_field) {
                continue;
            }
            $get_query_str .= "{$key}={$value}&";
        }
        $get_query_str = substr($get_query_str, 0, strlen($get_query_str) - 1);
        
        // calulate the sig
        // 1. joint the http get query string and the seed
        // 2. md5 the step 1 result
        // 3. substring the step 2 result from 3, length is 24
        // 4. encode the step 3 result by base64
        return base64_encode(substr(md5($get_query_str . $seed), 3, 24));
    }
    
    /**
     * DES Encrypt
     * @param unknown_type $input
     * @param unknown_type $key
     * @return string|NULL
     */
    public final static function /*string*/ desEncrypt($input, $key) {
        $size = mcrypt_get_block_size('des', 'ecb');
        $input = CommonUtils::en_pkcs5_pad($input, $size);
        $td = mcrypt_module_open('des', '', 'ecb', '');
        $key = substr($key, 0, mcrypt_enc_get_key_size($td));
        $iv_size = mcrypt_enc_get_iv_size($td);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        
        /* Initialize encryption handle */
        if (mcrypt_generic_init($td, $key, $iv) != -1) {
            
            /* Encrypt data */
            $c_t = mcrypt_generic($td, $input);
            mcrypt_generic_deinit($td);
            mcrypt_module_close($td);
            return $c_t;
        }
        
        return null;
    }
    
    /**
     * DES Decrypt
     * @param unknown_type $input
     * @param unknown_type $key
     * @return string|NULL
     */
    public final static function /*string*/ desDecrypt($input, $key) {
        $td = mcrypt_module_open('des', '', 'ecb', '');
        $key = substr($key, 0, mcrypt_enc_get_key_size($td));
        $iv_size = mcrypt_enc_get_iv_size($td);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        
        /* Initialize encryption handle */
        if (mcrypt_generic_init($td, $key, $iv) != -1) {
            
            /* Decrypt data */
            $p_t = mdecrypt_generic($td, $input);
            
            /* Clean up */
            mcrypt_generic_deinit($td);
            mcrypt_module_close($td);
            return $p_t;
        }
        
        return null;
    }
    
    /**
     * padd to text by the blocksize
     * @param unknown_type $text
     * @param unknown_type $blocksize
     * @return string
     */
    private final static function en_pkcs5_pad($text, $blocksize) {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }
    
    public final static function de_pkcs5_pad($text) {
        $len = strlen($text);
        $last_pad = substr($text, $len - 1);
        $pad_len = ord($last_pad);
        return substr($text, 0, $len - $pad_len);
    }
    
    /**
     * Send http error status header and exit the current performance
     * @param unknown_type $status
     * @param unknown_type $echo_info
     */
    public final static function sendErrorHeaderAndExit($status, $echo_info) {
        header("HTTP/1.1 " . $status . ' ERROR');
        if (!empty($echo_info)) {
            exit('' . $echo_info);
        } else {
            exit();
        }
    }
    
    /**
     * get the http headers
     * @return unknown
     */
    public final static function getHeaders() {
        if (!isset($GLOBALS['__headers__'])) {
            if (function_exists('apache_request_headers')) {
                $GLOBALS['__headers__'] = apache_request_headers();
            } else {
                $GLOBALS['__headers__'] = self::getCommonHttpHeaders();
            }
        }
        return $GLOBALS['__headers__'];
    }
    
    /**
     * check OS whether WINNT
     * @return boolean
     */
    public final static function isWinOS() {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }
    
    /**
     * check whether json rpc by http content-type field
     * @return boolean
     */
    public final static function isJsonRPC() {
        return $_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false;
    }
    
    /**
     * chekc whether rpc debug mode by headers
     * @param unknown_type $headers
     * @return boolean
     */
    public final static function isRPCDebug($headers) {
        return (isset($headers['RPC_DEBUG']) && $headers['RPC_DEBUG'] == 'true') || (isset($headers['Rpc_debug']) && $headers['Rpc_debug'] == 'true');
    }
    
    /**
     * Check app whether is debug mode
     * @return boolean
     */
    public final static function isAppDebug() {
        return defined('APP_DEBUG') && APP_DEBUG;
    }
    
    public final static function intRev($number) {
        if ($number && is_int($number)) {
            return intval(strrev(strval($number)));
        }
        return 0;
    }
    
    private final static function intRev2Hex($number) {
        if ($number && is_numeric($number)) {
            $str = strrev(strval($number));
            $str_a = str_split($str);
            $str_result = array ();
            foreach ( $str_a as $b ) {
                $token_result[] = dechex(ord($b));
            }
            return implode($token_result);
        }
        return 0;
    }
    
    private static function getCommonHttpHeaders() {
        $headers = array ();
        foreach ( $_SERVER as $key => $value ) {
            if ('HTTP_' == substr($key, 0, 5)) {
                $headers[str_replace('_', '-', substr($key, 5))] = $value;
            }
        }
        if (isset($_SERVER['PHP_AUTH_DIGEST'])) {
            $header['AUTHORIZATION'] = $_SERVER['PHP_AUTH_DIGEST'];
        } elseif (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
            $header['AUTHORIZATION'] = base64_encode($_SERVER['PHP_AUTH_USER'] . ':' . $_SERVER['PHP_AUTH_PW']);
        }
        if (isset($_SERVER['CONTENT_LENGTH'])) {
            $header['CONTENT-LENGTH'] = $_SERVER['CONTENT_LENGTH'];
        }
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $header['CONTENT-TYPE'] = $_SERVER['CONTENT_TYPE'];
        }
    }
    
    /**
     * calculate url token sig
     * @param unknown_type $request_id
     * @param unknown_type $token
     * @param unknown_type $token_secret
     * @param unknown_type $xor
     * @return string
     */
    public final static function calculateURLTokenSig($request_id, $token, $token_secret, $xor = 'D5') {
        $xor = hexdec($xor);
        $request_id_rev = self::intRev2Hex($request_id);
        $token_cal = ~$token;
        $token_cal_a = str_split($token_cal);
        $token_result = array ();
        foreach ( $token_cal_a as $b ) {
            $token_result[] = dechex(ord($b));
        }
        $token_cal = implode($token_result);
        $token_secret_array = str_split($token_secret);
        $token_secret_result = array ();
        foreach ( $token_secret_array as $b ) {
            $b = ord($b);
            $tmp_sc = $b ^ $xor;
            $token_secret_result[] = dechex($tmp_sc);
        }
        $token_secret_cal = implode($token_secret_result);
        $result = $request_id_rev . $token_cal . $token_secret_cal;
        return base64_encode(md5($result));
    }
    
    /**
     * get current time second
     * @return number
     */
    public final static function microtime_float() {
        list ($usec, $sec) = explode(" ", microtime());
        return (floatval($usec) + floatval($sec));
    }
    
    public final static function getCurrentTimeMilli() {
        return floor(self::microtime_float() * 1000);
    }
    
    public final static function lock($name = 'phpsimplelock.tmp', $simple = true) {
        if ($simple) {
            if (isset(self::$CACHE['lockfile']) && file_exists(self::$CACHE['lockfile'])) {
                self::$CACHE['lockerror'] = 'You has lock the current processor by ' . self::$CACHE['lockfile'];
                return false;
            }
            $filepath = self::getTempFilePath();
            self::$CACHE['lockfile'] = $filepath . $name;
            if (file_exists(self::$CACHE['lockfile'])) {
                self::$CACHE['lockerror'] = 'Get Lock failed, the lock has existed: ' . self::$CACHE['lockfile'];
                return false;
            }
            $size = file_put_contents(self::$CACHE['lockfile'], 'This is a file for simple lock');
            if (!$size) {
                self::$CACHE['lockerror'] = 'Get Lock failed, Create file ' . self::$CACHE['lockfile'] . ' error';
                return false;
            }
            return true;
        }
        return false;
    }
    
    public final static function unlock($name = 'phpsimplelock.tmp', $simple = true) {
        $r = false;
        if ($simple && isset(self::$CACHE['lockfile']) && file_exists(self::$CACHE['lockfile'])) {
            $r = unlink(self::$CACHE['lockfile']);
            if (!$r) {
                self::$CACHE['lockerror'] = 'Can not delete lock file ' . self::$CACHE['lockfile'];
            }
        }
        return $r;
    }
    
    public final static function hasLockError() {
        return isset(self::$CACHE['lockerror']);
    }
    
    public final static function getLockErrorInfo() {
        return isset(self::$CACHE['lockerror']) ? self::$CACHE['lockerror'] : 'unknow lock error';
    }
    
    /**
     * convert integer to a reversible charactor
     * @param unknown_type $num
     * @param unknown_type $len
     * @param unknown_type $secret
     * @return number|string
     */
    public final static function int2Char($num, $len = 8, $secret = ')!C&#$%^') {
        /* 生成62个字符*/
        $basicNum = range(0, 9);
        $basiclittle = range('a', 'z');
        $basicLittle = range('A', 'Z');
        $basic = array_merge($basicNum, $basiclittle, $basicLittle);
        if ($len >= 62 || !is_numeric($num) || $num < 0) {
            return 0;
        }
        $flag = 1;
        $md5 = md5(substr(md5(substr($num, 1, 6) . $secret), 2, 5));
        $mod = array ();
        while ( $flag ) {
            $num = floatval($num);
            $int = floor($num / 62); //取得整数部分
            $mod[] = fmod($num, 62); //取得余数部分
            if ($int <= 0) { //当被除数为0时候结束
                $flag = 0;
            }
            //echo $num.'整数部分为: ',$int,' 余数为:',$mod[$i];
            //输出算法
            $num = $int;
        }
        $numarray = array_reverse($mod); //反转数组，因为余数是反过来的
        

        $shortUrl = array ();
        foreach ( $numarray as $k => $v ) {
            $shortUrl[$k] = $basic[$v]; //62位数字对应basic62个数据，转换数字为字母
        }
        $count = count($shortUrl);
        $shortUrl = $basic[$count] . implode('', $shortUrl) . substr($md5, 2, $len - 1 - $count);
        
        return $shortUrl;
    }
    
    /**
     * convert charactor to a reversible integer
     * @param unknown_type $chars
     * @param unknown_type $len
     * @param unknown_type $secret
     * @return number
     */
    public final static function char2Int($chars, $len = 8, $secret = ')!C&#$%^') {
        if ($len >= 62) {
            return 0;
        }
        $j = 0;
        for($asc = 48; $asc < 58; $asc++) {
            $basicNum[$j] = chr($asc);
            $j++;
        }
        $j = 0;
        for($asc = 97; $asc < 123; $asc++) {
            $basiclittle[$j] = chr($asc);
            $j++;
        }
        $j = 0;
        for($asc = 65; $asc < 91; $asc++) {
            $basicLittle[$j] = chr($asc);
            $j++;
        }
        /* 生成62个字符*/
        $basic = array_merge($basicNum, $basiclittle, $basicLittle);
        $count = $chars{0};
        
        $count = array_search($count, $basic);
        
        if ($count == false || !is_numeric($count)) {
            return 0;
        }
        
        $shortUrllist = str_split(substr($chars, 1, $count)); //分割字符串
        foreach ( $shortUrllist as $k => $v ) {
            $shortArray[$k] = array_search($v, $basic); //将字符串转化为数字 利用$v里面的值找出$basic的键名就是要的数字
        }
        $shortArray = array_reverse($shortArray); //反转数组，为了要键名
        $total = 0;
        foreach ( $shortArray as $k => $v ) {
            $total += ($v * (pow(62, $k)));
        }
        
        $md5 = md5(substr(md5(substr($total, 1, 6) . $secret), 2, 5));
        if (strrpos($chars, substr($md5, 2, $len - 1 - $count)) !== $count + 1) {
            return 0;
        }
        //从右至左的位数为N，择取每位的n-1次方相加  }
        //echo '<br />输出ID号为：',$total;
        return $total;
    }
    
    /**
     * check whether version1 is less than the version2
     * @param unknown_type $version1
     * @param unknown_type $version2
     * @return boolean
     */
    public final static function versionLess($version1, $version2) {
        $c_vers = explode('.', $version1);
        $lc_vers = explode('.', $version2);
        foreach ( $c_vers as $key => $value ) {
            if (!is_numeric($value)) {
                return true;
            }
            $lc_v = isset($lc_vers[$key]) ? intval($lc_vers[$key]) : 0;
            $c_v = intval($value);
            
            if ($c_v < $lc_v) {
                return true;
            } else if ($c_v > $lc_v) {
                return false;
            }
        }
        return false;
    }
    
    public final static function getClientIP() {
        if (!empty($_SERVER["HTTP_CLIENT_IP"]))
            $cip = $_SERVER["HTTP_CLIENT_IP"];
        else if (!empty($_SERVER["HTTP_X_FORWARDED_FOR"]))
            $cip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        else if (!empty($_SERVER["REMOTE_ADDR"]))
            $cip = $_SERVER["REMOTE_ADDR"];
        else
            $cip = "unkownIp";
        return $cip;
    }
    
    public final static function startXhprof() {
        if (function_exists('xhprof_enable')) {
            xhprof_enable();
        }
    }
    
    public final static function endXhprof($source) {
        if (function_exists('xhprof_disable')) {
            $xhprof_data = xhprof_disable();
            // display raw xhprof data for the profiler run
            //print_r($xhprof_data);
            

            include_once "lib/xhprof_lib/utils/xhprof_lib.php";
            include_once "lib/xhprof_lib/utils/xhprof_runs.php";
            
            // save raw data for this profiler run using default
            // implementation of iXHProfRuns.
            $xhprof_runs = new XHProfRuns_Default();
            
            // save the run under a namespace "xhprof_foo"
            $run_id = $xhprof_runs->save_run($xhprof_data, $source);
            return $run_id;
        }
        
        return null;
    
    }
    
    /**
     * get hash code of string(the same to java object's hashCode method)
     * @param unknown_type $string
     * @throws Exception
     * @return number
     */
    public final static function getStringHashCode($string) {
        if (is_null($string)) {
            throw new Exception("NullPointer Exception");
        }
        $hash = 0;
        $stringLength = strlen($string);
        for($i = 0; $i < $stringLength; $i++) {
            $hash = 31 * $hash + $string[$i];
        }
        return $hash;
    }
    
    /**
     * Check array1's elements whether equal array2's elements
     * @param unknown_type $array1
     * @param unknown_type $array2
     * @param bool $care_order array元素的顺序是否作为比较因素，也就是说，此参数为true的情况下 即使array中元素的顺序不一致也将返回false
     * @return bool 
     */
    public final static function arrayEqual($array1, $array2, $care_order = false) {
        // TODO
        throw new Exception("Not support operation");
    }
    
    /**
     * Get singleton by config
     * @param unknown_type $config
     * @param unknown_type $key_class_path
     * @param unknown_type $key_class_name
     * @throws Exception
     * @return Ambigous <>|unknown
     */
    public final static function getInstanceByConfig($config, $key_class_path = 'class_path', $key_class_name = 'class_name') {
        if(!isset($config[$key_class_path]) || !isset($config[$key_class_name])) {
            throw new Exception("Illegal config, not found class_path or class_name");
        }
        static $_instance_cache_ = array();
        if(isset($_instance_cache_[$config[$key_class_name]])) {
            return $_instance_cache_[$config[$key_class_name]];     
        }
        require_once $config[$key_class_path];
        $_instance_cache_[$config[$key_class_name]] = new $config[$key_class_name]();
        return $_instance_cache_[$config[$key_class_name]];
    }
    
    private final static function getTempFilePath() {
        $os = strtoupper(PHP_OS);
        if ($os === 'WINNT') {
            return 'C:\\Windows\\Temp\\';
        } else if ($os === 'LINUX') {
            return '/tmp/';
        }
        return null;
    }
    
    private static function printStrAscii($string) {
        $str_a = str_split($string);
        foreach ( $str_a as $s ) {
            echo ord($s) . ' ';
        }
        echo "\n";
    }
    
    /**
     * custome date to local
     * @param  string $date
     * @return boolean|string
     */
    public final static function customeDate2Local($date) {
        $rs_date = '';
        $timezone = array_pop(explode(' ', trim($date)));
        
        if (preg_match('/\w+(\/)?\w+([+-])?(\w+)(\d+)?/i', $timezone)) {
            $local_timezone = date_default_timezone_get();
            date_default_timezone_set($timezone);
            $rs_date = strtotime(date('Y-m-d H:i:s', strtotime($date)));
            $rs_date += strtotime(date('Y-m-d H:i:s'));
            date_default_timezone_set($local_timezone);
            $rs_date -= strtotime(date('Y-m-d H:i:s'));
            $rs_date = date('Y-m-d H:i:s', $rs_date);
        } else {
            return false;
        }
        
        return $rs_date;
    }

}
//echo CommonUtils::calculateURLTokenSig(47220353, '263f14bce1c49f48c2c80ac960283d50', '262ee59a6ff2c69dd66e26790a8e861f');
//CommonUtils::checkCommonSig('47220353' . '263f14bce1c49f48c2c80ac960283d50' . '1' . '1' . '1332216378', '111111111');
//echo $url = CommonUtils::int2Char(61);
//echo CommonUtils::char2Int('6HF1Vgjb');


?>
