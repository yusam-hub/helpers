<?php

namespace YusamHub\Helper;

/**
 * !!!Important!!! если ключи создаются не в php, то их нужно переконвертировать
 */
class OpenSsl
{
    const PUBLIC_KEY_STRING_BEGIN = '-----BEGIN PUBLIC KEY-----';
    const PUBLIC_KEY_STRING_END = '-----END PUBLIC KEY-----';

    const LINUX_EOL = PHP_EOL;
    const WINDOWS_EOL = "\r\n";

    /**
     * @var string|null
     */
    private ?string $privateKey;

    /**
     * @var string|null
     */
    private ?string $publicKey;


    /**
     * @param string $publicKey
     * @return bool|null
     */
    public static function isPublicKeyLinuxEOL(string $publicKey): ?bool
    {
        if (
            (strpos($publicKey, OpenSsl::PUBLIC_KEY_STRING_BEGIN . static::LINUX_EOL) === 0)
            &&
            strstr($publicKey, OpenSsl::PUBLIC_KEY_STRING_END . static::LINUX_EOL)
        ) {
            return true;
        } elseif (
            (strpos($publicKey, OpenSsl::PUBLIC_KEY_STRING_BEGIN . static::WINDOWS_EOL) === 0)
            &&
            strstr($publicKey, OpenSsl::PUBLIC_KEY_STRING_END . static::WINDOWS_EOL)
        ) {
            return false;
        }

        return null;
    }

    /**
     * @param bool $isLinuxPublicKeyEOL
     * @param string $publicKey
     * @return string
     */
    public static function clearPublicKey(bool $isLinuxPublicKeyEOL, string $publicKey): string
    {
        $eol = static::LINUX_EOL;
        if (!$isLinuxPublicKeyEOL) {
            $eol = static::WINDOWS_EOL;
        }

        $f = strlen(self::PUBLIC_KEY_STRING_BEGIN . $eol);
        return self::PUBLIC_KEY_STRING_BEGIN  . $eol . substr(
            $publicKey,
            $f,
            strpos($publicKey, $eol . self::PUBLIC_KEY_STRING_END . $eol) - $f
        ) . $eol . self::PUBLIC_KEY_STRING_END . $eol;
    }

    /**
     * @param string|null $privateKey
     * @param string|null $publicKey
     */
    public function __construct(
        ?string $privateKey = null,
        ?string $publicKey = null
    )
    {
        $this->privateKey = $privateKey;
        $this->publicKey = $publicKey;
    }

    /**
     * @param string $privateKeyFile
     * @param string $publicKeyFile
     * @param int $private_key_bits
     * @param int $private_key_type
     * @return void
     */
    public static function generateNewPrivatePublicKeys(
        string $privateKeyFile,
        string $publicKeyFile,
        int $private_key_bits = 1024,
        int $private_key_type = OPENSSL_KEYTYPE_RSA
    )
    {
        $res = openssl_pkey_new(array(
            'private_key_bits' => $private_key_bits,
            'private_key_type' => $private_key_type,
        ));
        openssl_pkey_export_to_file($res, $privateKeyFile);
        $details = openssl_pkey_get_details($res);
        file_put_contents($publicKeyFile, $details['key']);
        openssl_free_key($res);
    }

    /**
     * @param string $value
     * @param bool $encodeBase64
     * @return string
     * @throws \Exception
     */
    public function encryptWithPrivate(string $value, bool $encodeBase64 = true): string
    {
        if (empty($this->privateKey)) {
            throw new \Exception('Open ssl error : private key empty');
        }
        $res = openssl_pkey_get_private($this->privateKey);
        if ($res === false) {
            $errors = [];
            while ($msg = openssl_error_string()) {
                $errors[] = $msg;
            };
            throw new \Exception('Open ssl error : ' . implode("\n",$errors));
        }
        $resEncrypt = openssl_private_encrypt($value, $result, $res);
        openssl_free_key($res);
        if (!$resEncrypt) {
            throw new \Exception('Open ssl error : ' . openssl_error_string());
        }
        return $encodeBase64 ? base64_encode($result) : $result;
    }

    /**
     * @param string $value
     * @param bool $encodeBase64
     * @return string
     * @throws \Exception
     */
    public function encryptWithPublic(string $value, bool $encodeBase64 = true): string
    {
        if (empty($this->publicKey)) {
            throw new \Exception('Open ssl error : public key empty');
        }
        $res = openssl_pkey_get_public($this->publicKey);

        if ($res === false) {
            $errors = [];
            while ($msg = openssl_error_string()) {
                $errors[] = $msg;
            };
            throw new \Exception( 'Open ssl error : ' . implode("\n",$errors));
        }
        $resEncrypt = openssl_public_encrypt($value, $result, $res);
        openssl_free_key($res);
        if (!$resEncrypt) {
            throw new \Exception( 'Open ssl error : ' . openssl_error_string());
        }
        return $encodeBase64 ? base64_encode($result) : $result;
    }

    /**
     * @param string $value
     * @param bool $decodeBase64
     * @return string
     * @throws \Exception
     */
    public function decryptWithPublic(string $value, bool $decodeBase64 = true): ?string
    {
        if (empty($this->publicKey)) {
            throw new \Exception('Open ssl error : public key empty');
        }
        $value = $decodeBase64 ? base64_decode($value) : $value;
        $res = openssl_get_publickey($this->publicKey);
        if ($res === false) {
            throw new \Exception('Open ssl error : ' . openssl_error_string());
        }
        $resDecrypt = openssl_public_decrypt($value, $result, $res);
        openssl_free_key($res);
        if (!$resDecrypt) {
            throw new \Exception('Open ssl error : ' . openssl_error_string());
        }
        return $result;
    }

    /**
     * @param string $value
     * @param bool $decodeBase64
     * @return string
     * @throws \Exception
     */
    public function decryptWithPrivate(string $value, bool $decodeBase64 = true): ?string
    {
        if (empty($this->privateKey)) {
            throw new \Exception('Open ssl error : private key empty');
        }
        $value = $decodeBase64 ? base64_decode($value) : $value;
        $res = openssl_get_privatekey($this->privateKey);
        if ($res === false) {
            throw new \Exception('Open ssl error : ' . openssl_error_string());
        }
        $resDecrypt = openssl_private_decrypt($value, $result, $res);
        openssl_free_key($res);

        if (!$resDecrypt) {
            throw new \Exception('Open ssl error : ' . openssl_error_string());
        }

        return $result;
    }
}