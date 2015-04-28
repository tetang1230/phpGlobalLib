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
    
    
    public static function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {  
        // 动态密匙长度，相同的明文会生成不同密文就是依靠动态密匙  
        $ckey_length = 4;  
          
        // 密匙  
        $key = md5($key ? $key : 'YOUR_KEY');  
          
        // 密匙a会参与加解密  
        $keya = md5(substr($key, 0, 16));  
        // 密匙b会用来做数据完整性验证  
        $keyb = md5(substr($key, 16, 16));  
        // 密匙c用于变化生成的密文  
        $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';  
        // 参与运算的密匙  
        $cryptkey = $keya.md5($keya.$keyc);  
        $key_length = strlen($cryptkey);  
        // 明文，前10位用来保存时间戳，解密时验证数据有效性，10到26位用来保存$keyb(密匙b)，解密时会通过这个密匙验证数据完整性  
        // 如果是解码的话，会从第$ckey_length位开始，因为密文前$ckey_length位保存 动态密匙，以保证解密正确  
        $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;  
        $string_length = strlen($string);  
        $result = '';  
        $box = range(0, 255);  
        $rndkey = array();  
        // 产生密匙簿  
        for($i = 0; $i <= 255; $i++) {  
            $rndkey[$i] = ord($cryptkey[$i % $key_length]);  
        }  
        // 用固定的算法，打乱密匙簿，增加随机性，好像很复杂，实际上对并不会增加密文的强度  
        for($j = $i = 0; $i < 256; $i++) {  
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;  
            $tmp = $box[$i];  
            $box[$i] = $box[$j];  
            $box[$j] = $tmp;  
        }  
        // 核心加解密部分  
        for($a = $j = $i = 0; $i < $string_length; $i++) {  
            $a = ($a + 1) % 256;  
            $j = ($j + $box[$a]) % 256;  
            $tmp = $box[$a];  
            $box[$a] = $box[$j];  
            $box[$j] = $tmp;  
            // 从密匙簿得出密匙进行异或，再转成字符  
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));  
        }  
        if($operation == 'DECODE') {  
            // substr($result, 0, 10) == 0 验证数据有效性  
            // substr($result, 0, 10) - time() > 0 验证数据有效性  
            // substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16) 验证数据完整性  
            // 验证数据有效性，请看未加密明文的格式  
            if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {  
                return substr($result, 26);  
            } else {  
                return '';  
            }  
        } else {  
            // 把动态密匙保存在密文里，这也是为什么同样的明文，生产不同密文后能解密的原因  
            // 因为加密后的密文可能是一些特殊字符，复制过程可能会丢失，所以用base64编码  
            return $keyc.str_replace('=', '', base64_encode($result));  
        }  
     }
    
    /** 
    * @param mixed   $in      待处理字符串 
    * @param boolean $to_num  是否解密:true解密,false加密 
    * @param mixed   $pad_up  固定字符串的长度 
    * @param string  $passKey 密码加密 
    * 
    * @return mixed string or long 
    */

     public static function alphaID($in, $to_num = false, $pad_up = false, $passKey = null) {  
        $index = "abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";  
        if ($passKey !== null) {  
            // Although this function's purpose is to just make the  
            // ID short - and not so much secure,  
            // with this patch by Simon Franz (http://blog.snaky.org/)  
            // you can optionally supply a password to make it harder  
            // to calculate the corresponding numeric ID  
           
            for ($n = 0; $n<strlen($index); $n++) {  
                $i[] = substr( $index,$n ,1);  
            }  
           
            $passhash = hash('sha256',$passKey);  
            $passhash = (strlen($passhash) < strlen($index))  
              ? hash('sha512',$passKey)  
              : $passhash;  
           
            for ($n=0; $n < strlen($index); $n++) {  
                $p[] =  substr($passhash, $n ,1);  
            }  
           
            array_multisort($p,  SORT_DESC, $i);  
            $index = implode($i);  
        }  

        $base  = strlen($index);  
       
        if ($to_num) {  
            // Digital number  <<--  alphabet letter code  
            $in  = strrev($in);  
            $out = 0;  
            $len = strlen($in) - 1;  
            for ($t = 0; $t <= $len; $t++) {  
                $bcpow = bcpow($base, $len - $t);  
                $out   = $out + strpos($index, substr($in, $t, 1)) * $bcpow;  
            }  
           
            if (is_numeric($pad_up)) {  
                $pad_up--;  
                if ($pad_up > 0) {  
                    $out -= pow($base, $pad_up);  
                }  
            }  
            $out = sprintf('%F', $out);  
            $out = substr($out, 0, strpos($out, '.'));  
          } else {  
            // Digital number  -->>  alphabet letter code  
            if (is_numeric($pad_up)) {  
                $pad_up--;  
                if ($pad_up > 0) {  
                    $in += pow($base, $pad_up);  
                }  
            }  
           
            $out = "";  
            for ($t = floor(log($in, $base)); $t >= 0; $t--) {  
                  $bcp = bcpow($base, $t);  
                  $a   = floor($in / $bcp) % $base;  
                  $out = $out . substr($index, $a, 1);  
                  $in  = $in - ($a * $bcp);  
            }  
            $out = strrev($out); // reverse  
        }  
       
      return $out;  
    }
    
    /**
     * 
     * 获取文件扩展名
     * 
     */
     
    public static function fileext($filename) {
        return trim(substr(strrchr($filename, '.'), 1, 10));
    }

    /**
     * 检测日期的有效性
     */
    public static function datecheck($ymd, $sep='-') {
            if(!empty($ymd)) {
                    list($year, $month, $day) = explode($sep, $ymd);
                    return checkdate($month, $day, $year);
            } else {
                    return FALSE;
            }   
        }
    }

/*
 * 基于PHP没有安装 mb_substr 等扩展截取字符串，如果截取中文字则按2个字符计算
 * @param $string 要截取的字符串
 * @param $length 要截取的字符数
 * @param $dot 替换截掉部分的结尾字符串
 * @return 返回截取后的字符串
 */
    public function cutstr($string, $length, $charset = 'utf-8', $dot = '...') {
        // 如果字符串小于要截取的长度则直接返回
        // 此处使用strlen获取字符串长度有很大的弊病，比如对字符串“新年快乐”要截取4个中文字符，
        // 那么必须知道这4个中文字符的字节数，否则返回的字符串可能会是“新年快乐...”
        if (strlen($string) <= $length) {
            return $string;
        }
    
        // 转换原字符串中htmlspecialchars
        $pre = chr(1);
        $end = chr(1);
        $string = str_replace ( array ('&amp;', '&quot;', '&lt;', '&gt;' ), array ($pre . '&' . $end, $pre . '"' . $end, $pre . '<' . $end, $pre . '>' . $end ), $string );
    
        $strcut = ''; // 初始化返回值
    
        // 如果是utf-8编码(这个判断有点不全,有可能是utf8)
        if (strtolower ( $charset ) == 'utf-8') {
            // 初始连续循环指针$n,最后一个字位数$tn,截取的字符数$noc
            $n = $tn = $noc = 0;
            while ( $n < strlen ( $string ) ) {
                $t = ord ( $string [$n] );
    
                if ($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
                    // 如果是英语半角符号等,$n指针后移1位,$tn最后字是1位
                    $tn = 1;
                    $n++;
                    $noc++;
                } elseif (194 <= $t && $t <= 223) {
                    // 如果是二字节字符$n指针后移2位,$tn最后字是2位
                    $tn = 2;
                    $n += 2;
                    $noc += 2;
                } elseif (224 <= $t && $t <= 239) {
                    // 如果是三字节(可以理解为中字词),$n后移3位,$tn最后字是3位
                    $tn = 3;
                    $n += 3;
                    $noc += 2;
                } elseif (240 <= $t && $t <= 247) {
                    $tn = 4;
                    $n += 4;
                    $noc += 2;
                } elseif (248 <= $t && $t <= 251) {
                    $tn = 5;
                    $n += 5;
                    $noc += 2;
                } elseif ($t == 252 || $t == 253) {
                    $tn = 6;
                    $n += 6;
                    $noc += 2;
                } else {
                    $n++;
                }
    
                // 超过了要取的数就跳出连续循环
                if ($noc >= $length) {
                    break;
                }
            }
    
            // 这个地方是把最后一个字去掉,以备加$dot
            if ($noc > $length) {
                $n -= $tn;
            }
    
            $strcut = substr ( $string, 0, $n );
    
        } else {
            // 并非utf-8编码的全角就后移2位
            for ($i = 0; $i < $length; $i ++) {
                $strcut .= ord ( $string [$i] ) > 127 ? $string [$i] . $string [++ $i] : $string [$i];
            }
        }
    
        // 再还原最初的htmlspecialchars
        $strcut = str_replace( array ($pre . '&' . $end, $pre . '"' . $end, $pre . '<' . $end, $pre . '>' . $end ), array ('&amp;', '&quot;', '&lt;', '&gt;' ), $strcut );
    
        $pos = strrpos ( $strcut, chr ( 1 ) );
        if ($pos !== false) {
            $strcut = substr ( $strcut, 0, $pos );
        }
    
        return $strcut . $dot; // 最后把截取加上$dot输出
    }
    
    /**
    * 检查邮箱是否有效
    * @param $email 要检查的邮箱
    * @param 返回结果
    */
    public static function isemail($email) {
        return strlen($email) > 6 && preg_match("/^[\w\-\.]+@[\w\-\.]+(\.\w+)+$/", $email);
    }
    
    /**
    * 判断一个字符串是否在另一个字符串中存在
    * @param haystack 待查找的字符串
    * @param $needls 被查找的字符串
    * @return 是否存在
    */
    public static function strexists($haystack, $needle) {
        return !(strpos($haystack, $needle) === FALSE);
    }


    /**
     * 防注入 
     * 一般用在$_GET, $_POST, $_FILES
     */
     
    public static function caddslashes($string, $force = 1) {
    	if(is_array($string)) {
    		$keys = array_keys($string);
    		foreach($keys as $key) {
    			$val = $string[$key];
    			unset($string[$key]);
    			$string[addslashes($key)] = daddslashes($val, $force);
    		}
    	} else {
    		$string = addslashes($string);
    	}
    	return $string;
    }

    
}


//echo CommonUtils::alphaID("12983",false,8,"chester")."\n";
//echo CommonUtils::alphaID("jcG2222K",true,8,"chester")."\n";
//echo CommonUtils::calculateURLTokenSig(47220353, '263f14bce1c49f48c2c80ac960283d50', '262ee59a6ff2c69dd66e26790a8e861f');
//CommonUtils::checkCommonSig('47220353' . '263f14bce1c49f48c2c80ac960283d50' . '1' . '1' . '1332216378', '111111111');
//echo $url = CommonUtils::int2Char(61);
//echo CommonUtils::char2Int('6HF1Vgjb');


?>
