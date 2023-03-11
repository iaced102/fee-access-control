<?php

namespace Library;

class Auth
{
    private static $tenantId = '';
    private static $ignoreTokenInfo = false;
    private static $oldAuth = true;
    private static $representative = "";

    //
    public static function Hash($password)
    {
        return hash_hmac('sha256', $password, PRIVATE_KEY);
    }

    public static function sign($data)
    {
        if (self::$oldAuth) {
            $privatePEMKey = file_get_contents(DIR . '/Crypt/private.pem');
        } else {
            $privatePEMKey = file_get_contents(DIR . '/Crypt/private1.pem');
        }
        $privatePEMKey = openssl_get_privatekey($privatePEMKey, 'Symper@@');
        $signature = '';
        openssl_sign($data, $signature, $privatePEMKey, OPENSSL_ALGO_SHA256);
        if (self::$oldAuth) {
            $signature = bin2hex($signature);
        }
        return $signature;
    }
    public static function verifySign($data, $signature)
    {
        if (self::$oldAuth) {
            $publicPEMKey = file_get_contents(DIR . '/Crypt/public.pem');
            $publicPEMKey = openssl_get_publickey($publicPEMKey);
            $r = openssl_verify($data, hex2bin($signature), $publicPEMKey, OPENSSL_ALGO_SHA256);
        } else {
            $publicPEMKey = file_get_contents(DIR . '/Crypt/public1.pem');
            $publicPEMKey = openssl_get_publickey($publicPEMKey);
            $r = openssl_verify($data, $signature, $publicPEMKey, OPENSSL_ALGO_SHA256);
        }
        if ($r == 1) {
            return true;
        }
        return false;
    }
    public static function getJwtData($token)
    {
        if (self::$ignoreTokenInfo === true) {
            return false;
        }

        $dataFromCache = CacheService::getMemoryCache($token);
        if ($dataFromCache != false) {
            return $dataFromCache;
        } else {
            $dataFromToken = explode(".", $token);
            $header = $dataFromToken[0];
            $payload = $dataFromToken[1];
            $signature = $dataFromToken[2];
            if (self::verifyJwt($header, $payload, $signature)) {
                if (self::$oldAuth) {
                    $jsonData = base64_decode($payload);
                } else {
                    $jsonData = self::base64UrlDecode($payload);
                }
                $data = json_decode($jsonData, true);
                CacheService::setMemoryCache($token, $data);
                return $data;
            } else {
                return false;
            }
        }
    }
    private static function base64UrlEncode($data)
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }
    private static function base64UrlDecode($data)
    {
        return base64_decode(str_replace(['-', '_', ''], ['+', '/', '='], $data));
    }
    public static function verifyJwt($header, $payload, $signature)
    {
        if (self::$oldAuth) {
            $signature = base64_decode($signature);
        } else {
            $signature = self::base64UrlDecode($signature);
        }
        $dataToVerify = "$header.$payload";
        return self::verifySign($dataToVerify, $signature);
    }
    public static function getJwtToken($data, $exp = false, $isFingerPrint = true)
    {
        $data = (array)$data;
        $header = self::getJwtHeader();
        if (isset($data['tenantId']) || isset($data['tenant_id'])) {
            $data['tenant'] = [
                'id'    => isset($data['tenantId']) ? $data['tenantId'] : $data['tenant_id']
            ];
        }
        $data['is_cloud'] = isset($GLOBALS['env']['is_cloud']) ? $GLOBALS['env']['is_cloud'] : true;
        $data['tenant_domain'] = isset($GLOBALS['env']['tenant_domain']) ? $GLOBALS['env']['tenant_domain'] : '';
        // Token's timeout default is 3600s
        if (self::checkNewAuth()) {
            if (isset($data["id"]) && $data["id"] == 18) {
                $GLOBALS["isKha"] = true;
                $data['exp'] = time() + 300;
            } else {
                $data['exp'] = $exp == false ? time() + 3600 : time() + $exp;
            }
        } else {
            $data['exp'] = "0";
        }

        $data['iat'] = time();
        $payload = self::getJwtPayload($data);
        $signature = self::getJwtSignature($header, $payload, $isFingerPrint);
        $jwtToken = "$header.$payload.$signature";
        return $jwtToken;
    }

    public static function getSignBydata($data)
    {
        $data = (array)$data;
        $header = self::getJwtHeader();
        $payload = self::getJwtPayload($data);
        $signature = self::getJwtSignature($header, $payload, false);
        $signature = str_replace("new_symper_authen_!", "", $signature);
        $jwtToken = "$header.$payload.$signature";
        return $jwtToken;
    }
    public static function getJwtRefreshToken($exp = false, $iat = false)
    {
        $data = ["u" => Str::createUUID()];
        $header = self::getJwtHeader();
        if (isset($data['tenantId']) || isset($data['tenant_id'])) {
            $data['tenant'] = [
                'id'    => isset($data['tenantId']) ? $data['tenantId'] : $data['tenant_id']
            ];
        }
        $data['is_cloud'] = isset($GLOBALS['env']['is_cloud']) ? $GLOBALS['env']['is_cloud'] : true;
        // Token's timeout default is 2600000s
        if (isset($GLOBALS["isKha"]) && $GLOBALS["isKha"]) {
            $data['exp'] = time() + 600;
            $data['isKha'] = 1;
        } else {
            $data['exp'] = $exp == false ? time() + 2600000 : $exp;
        }
        if ($iat != false) {
            $data['iat'] = time();
        }
        $payload = self::getJwtPayload($data);
        $signature = self::getJwtSignature($header, $payload);
        $jwtToken = "$header.$payload.$signature";
        return $jwtToken;
    }
    public static function getJwtHeader()
    {
        $header = [
            'alg'   => "RS256",
            'typ'   => 'JWT',
        ];
        if (self::$oldAuth) {
            return base64_encode(json_encode($header));
        } else {
            return self::base64UrlEncode(json_encode($header));
        }
    }
    public static function getJwtPayload($data)
    {
        if (self::$oldAuth) {
            return base64_encode(json_encode($data));
        } else {
            return self::base64UrlEncode(json_encode($data));
        }
    }
    public static function getJwtSignature($header, $payload, $isFingerPrint = true)
    {
        if (self::$oldAuth) {
            return base64_encode(self::sign("$header.$payload"));
        } else {
            if ($isFingerPrint) {
                if (empty($GLOBALS['randomHash'])) {
                    $GLOBALS['randomStr'] = Str::createUUID();
                    $GLOBALS['randomHash'] = self::Hash($GLOBALS['randomStr'] . self::getCurrentUserAgent());
                }
                $GLOBALS['fingerPrintCookie'] = $GLOBALS['randomStr'];
                return $GLOBALS['randomHash'] . "::" . self::base64UrlEncode(self::sign("$header.$payload")) . "new_symper_authen_!";
            } else {
                return self::base64UrlEncode(self::sign("$header.$payload")) . "new_symper_authen_!";
            }
        }
    }
    public static function getAuthorizationHeader()
    {
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        if (strpos($headers, "new_symper_authen_!")) {
            $headers = str_replace("new_symper_authen_!", "", $headers);
            self::setNewAuth();
        }
        if (self::$representative == "") {
            self::getRepresentativeToken($headers);
            if (self::$representative == "") {
                return $headers == 'Bearer false' ? null : $headers;
            }
        }
        return self::$representative;
    }
    public static function getRepresentativeToken($headers)
    {
        $representativeKey = self::getRepresentativeKey();
        if ($representativeKey != false) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                $newHeader = $matches[1];
            }
            $res = Request::request("https://dev-account.symper.vn/auth/representative", ["hashKey" => $representativeKey], 'POST', $newHeader);
            if (!empty($res) && !empty($res['data'])) {

                $newToken = str_replace("new_symper_authen_!", "", $res['data']);

                self::$representative = 'Bearer ' . $newToken;
            }
        }
        return self::$representative;
    }
    public static function getBearerToken()
    {
        $headers = self::getAuthorizationHeader();
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }
    public static function encryptRSA($plainData)
    {
        if (self::$oldAuth) {
            $publicPEMKey   = file_get_contents(DIR . '/Crypt/public.pem');
        } else {
            $publicPEMKey   = file_get_contents(DIR . '/Crypt/public1.pem');
        }
        $publicPEMKey   = openssl_get_publickey($publicPEMKey);
        $encrypted      = '';
        $plainData      = str_split($plainData, 200);
        foreach ($plainData as $chunk) {
            $partialEncrypted   = '';
            $encryptionOk       = openssl_public_encrypt($chunk, $partialEncrypted, $publicPEMKey, OPENSSL_PKCS1_PADDING);
            if ($encryptionOk === false) {
                return false;
            }
            $encrypted .= $partialEncrypted;
        }
        if (self::$oldAuth) {
            return base64_encode($encrypted);
        } else {
            return self::base64UrlEncode($encrypted);
        }
    }
    public static function decryptRSA($data)
    {
        $decrypted      = '';
        if (self::$oldAuth) {
            $privatePEMKey  = file_get_contents(DIR . '/Crypt/private.pem');
            $privatePEMKey  = openssl_get_privatekey($privatePEMKey, 'Symper@@');
            $data  = str_split(base64_decode($data), 256); //2048 bit
        } else {
            $privatePEMKey  = file_get_contents(DIR . '/Crypt/private1.pem');
            $privatePEMKey  = openssl_get_privatekey($privatePEMKey, 'Symper@@');
            $data  = str_split(self::base64UrlDecode($data), 256); //2048 bit
        }
        foreach ($data as $chunk) {
            $partial = '';
            $decryptionOK = openssl_private_decrypt($chunk, $partial, $privatePEMKey, OPENSSL_PKCS1_PADDING);
            if ($decryptionOK === false) {
                return false;
            }
            $decrypted .= $partial;
        }
        return $decrypted;
    }
    public static function getDataToken()
    {

        if (self::$ignoreTokenInfo === true) {
            return null;
        }

        $dataLogin = CacheService::getMemoryCache('JwtDataLoginCache');
        if ($dataLogin == false) {
            $token = Auth::getBearerToken();
            if (!empty($token)) {
                $dataLogin = self::getJwtData($token);
                CacheService::setMemoryCache('JwtDataLoginCache', $dataLogin);
            }
        }
        return $dataLogin;
    }

    public static function getTenantId()
    {
        $dataLogin = self::getDataToken();
        if (!empty($dataLogin)) {
            if (isset($dataLogin['tenant'])) {
                return $dataLogin['tenant']['id'];
            } else if (isset($dataLogin['userDelegate']) && isset($dataLogin['userDelegate']['tenantId'])) {
                return $dataLogin['userDelegate']['tenantId'];
            }
        } else {
            return self::$tenantId;
        }
        return '';
    }

    public static function getCurrentRole()
    {
        return self::getTokenInfo('role');
    }

    public static function isBa()
    {
        $dataLogin = self::getDataToken();
        if (!empty($dataLogin)) {
            if (isset($dataLogin['type']) && $dataLogin['type'] == 'ba') {
                return true;
            }
        }
        return false;
    }
    public static function getCurrentUserId()
    {
        $dataLogin = self::getDataToken();
        if (!empty($dataLogin)) {
            if (isset($dataLogin['type']) && $dataLogin['type'] == 'ba' && isset($dataLogin['userDelegate']) && isset($dataLogin['userDelegate']['id'])) {

                return $dataLogin['userDelegate']['id'];
            } else if (isset($dataLogin['id'])) {
                return $dataLogin['id'];
            }
        }
        return false;
    }
    public static function getCurrentSupporterId()
    {
        $token = Auth::getBearerToken();
        if (!empty($token)) {
            $dataLogin = Auth::getJwtData($token);
            if (!empty($dataLogin)) {
                if (isset($dataLogin['id']) && isset($dataLogin['type']) && $dataLogin['type'] == 'ba') {
                    return $dataLogin['id'];
                }
            }
        }
        return false;
    }
    public static function getCurrentIP()
    {
        $ipaddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP']))
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if (isset($_SERVER['HTTP_X_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        else if (isset($_SERVER['HTTP_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        else if (isset($_SERVER['HTTP_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        else if (isset($_SERVER['REMOTE_ADDR']))
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        else
            $ipaddress = 'UNKNOWN';
        return $ipaddress;
    }
    public static function getCurrentUserAgent()
    {
        return $_SERVER['HTTP_USER_AGENT'];
    }
    private static function getTokenInfo($key)
    {
        $dataLogin = self::getDataToken();
        if (!empty($dataLogin)) {
            if (isset($dataLogin['type']) && $dataLogin['type'] == 'ba' && isset($dataLogin['userDelegate']) && isset($dataLogin['userDelegate'][$key])) {

                return $dataLogin['userDelegate'][$key];
            } else if (isset($dataLogin[$key])) {
                return $dataLogin[$key];
            }
        }
        return '';
    }

    public static function getTenantInfo()
    {
        $dataLogin = self::getDataToken();
        if (!empty($dataLogin)) {
            if (isset($dataLogin['tenant'])) {
                return $dataLogin['tenant'];
            }
        }
        return [];
    }

    public static function getTokenIp()
    {
        return self::getTokenInfo('ip');
    }
    public static function getTokenUserAgent()
    {
        return self::getTokenInfo('userAgent');
    }
    public static function getTokenLocation()
    {
        return self::getTokenInfo('location');
    }

    public static function getIsCloud()
    {
        return self::getTokenInfo('is_cloud');
    }

    public static function getTenantDomain()
    {
        return self::getTokenInfo('tenant_domain');
    }

    public static function setTenantId($tenantId)
    {
        self::$tenantId = $tenantId;
    }

    public static function getCurrentBaEmail()
    {
        $token = Auth::getBearerToken();
        if (!empty($token)) {
            $dataLogin = Auth::getJwtData($token);
            $baEmail = (!empty($dataLogin['email'])) ? $dataLogin['email'] : "";
            return $baEmail;
        }
        return "";
    }

    public static function ignoreTokenInfo()
    {
        self::$ignoreTokenInfo = true;
    }

    public static function restoreTokenInfo()
    {
        self::$ignoreTokenInfo = false;
    }
    public static function setNewAuth()
    {
        self::$oldAuth = false;
    }
    public static function checkNewAuth()
    {
        return !self::$oldAuth;
    }
    private static function getRepresentativeKey()
    {
        $key = false;
        if (isset($_SERVER['S-Representative'])) {
            $key = trim($_SERVER["S-Representative"]);
        } else if (isset($_SERVER['HTTP_S_REPRESENTATIVE'])) {
            $key = trim($_SERVER["HTTP_S_REPRESENTATIVE"]);
        }
        unset($_SERVER["S-Representative"]);
        unset($_SERVER["HTTP_S_REPRESENTATIVE"]);
        return $key;
    }
}
