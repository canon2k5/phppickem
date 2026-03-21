<?php
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) {
    exit('This file cannot be accessed directly...');
}

/**
 * Provides symmetric encryption and decryption with AES-256-CBC.
 */
class OpenSSLCrypto {
    /**
     * @var string The cipher method used for encryption/decryption.
     */
    private $cipher;
    
    /**
     * @var string The binary encryption key.
     */
    private $key;

    /**
     * Constructor.
     *
     * @param string|null $key A passphrase used to derive the encryption key.
     * @param string $cipher The cipher method to use (default is 'aes-256-cbc').
     */
    public function __construct($key = null, $cipher = 'aes-256-cbc') {
        if (!extension_loaded('openssl')) {
            die('The OpenSSL extension is not loaded and is required.');
        }
        $this->cipher = $cipher;
        if ($key === null) {
            $key = defined('CRYPTO_KEY') ? CRYPTO_KEY : '';
        }
        // Derive a 256-bit key from the passphrase.
        $this->key = hash('sha256', $key, true);
    }

    /**
     * Encrypts the given plaintext.
     *
     * @param string $data The plaintext data to encrypt.
     * @return string The Base64-encoded string containing the IV and ciphertext.
     */
    public function encrypt($data) {
        $ivLength = openssl_cipher_iv_length($this->cipher);
        $iv = openssl_random_pseudo_bytes($ivLength);
        $encrypted = openssl_encrypt($data, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv);
        // Prepend IV to the ciphertext and encode.
        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypts the given ciphertext.
     *
     * @param string $data The Base64-encoded string containing the IV and ciphertext.
     * @return string The decrypted plaintext.
     */
    public function decrypt($data) {
        $data = base64_decode($data);
        $ivLength = openssl_cipher_iv_length($this->cipher);
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);
        return openssl_decrypt($encrypted, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv);
    }
}

