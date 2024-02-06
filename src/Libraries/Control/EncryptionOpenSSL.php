<?php
namespace Sygecon\AdminBundle\Libraries\Control;

/**
 * Cryption OpenSSL library
 */
class EncryptionOpenSSL
{
    /**
     * HMAC digest to use
     */
    protected $digest = 'SHA512';
    /**
     * Cipher to use
     */
    protected $cipher = 'AES-256-CTR';
    /**
     * List of supported HMAC algorithms
     */
    protected array $digestSize = [
        'SHA224' => 28,
        'SHA256' => 32,
        'SHA384' => 48,
        'SHA512' => 64,
    ];
    /**
     * Starter key
     */
    protected $key = '';
    /**
     * Encryption key info.
     */
    protected string $encryptKey = '';
    /**
     * Authentication key info.
     */
    protected string $authKey = 'MeNewKeyPass';
    
    /**
     * Constructor
     */
    public function __construct(?string $key = null, ?string $salt = null)
    {
        if (! isset($key) || ! $key) { throw lang('Encryption.starterKeyNeeded'); }
        if (! isset($salt) || ! $salt) {
            $this->encryptKey = base64_encode(substr(hash($this->digest, $salt) , 0, 16));
        }
        $this->key = bin2hex(\hash_hkdf($this->digest, $key));
    }

    /**
     * {@inheritDoc}
     */
    public function encrypt($data)
    {
        // derive a secret key
        $encryptKey = \hash_hkdf($this->digest, $this->key, 0, $this->encryptKey);
        // basic encryption
        $iv = ($ivSize = \openssl_cipher_iv_length($this->cipher)) ? \openssl_random_pseudo_bytes($ivSize) : null;
        $data = \openssl_encrypt($data, $this->cipher, $encryptKey, OPENSSL_RAW_DATA, $iv);
        if ($data === false) { throw lang('Encryption.encryptionFailed'); }
        
        $result = base64_encode($iv . $data);
        // derive a secret key
        $authKey = \hash_hkdf($this->digest, $this->key, 0, $this->authKey);
        $hmacKey = \hash_hmac($this->digest, $result, $authKey, false);
        return $hmacKey . $result;
    }

    /**
     * {@inheritDoc}
     */
    public function decrypt($data)
    {
        // derive a secret key
        $authKey = \hash_hkdf($this->digest, $this->key, 0, $this->authKey);
        $hmacLength = $this->digestSize[$this->digest] * 2;
        $hmacKey  = self::substr($data, 0, $hmacLength);
        $data     = self::substr($data, $hmacLength);
        $hmacCalc = \hash_hmac($this->digest, $data, $authKey, false);
        if (! hash_equals($hmacKey, $hmacCalc)) { throw lang('Encryption.authenticationFailed'); }
        
        $data = base64_decode($data, true);
        if ($ivSize = \openssl_cipher_iv_length($this->cipher)) {
            $iv   = self::substr($data, 0, $ivSize);
            $data = self::substr($data, $ivSize);
        } else {
            $iv = null;
        }
        // derive a secret key
        $encryptKey = \hash_hkdf($this->digest, $this->key, 0, $this->encryptKey);
        return \openssl_decrypt($data, $this->cipher, $encryptKey, OPENSSL_RAW_DATA, $iv);
    }

    /**
     * Byte-safe substr()
     * @return string
     */
    protected static function substr($str, $start, $length = null): string
    {
        return mb_substr($str, $start, $length, '8bit');
    }

    public function __get($key)
    {
        if ($this->__isset($key)) { return $this->{$key}; }
        return null;
    }

    public function __isset($key): bool
    {
        return property_exists($this, $key);
    }
}
