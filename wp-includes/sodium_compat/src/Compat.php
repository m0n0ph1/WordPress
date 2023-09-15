<?php

    if(class_exists('ParagonIE_Sodium_Compat', false))
    {
        return;
    }

    class Compat
    {
        public const LIBRARY_MAJOR_VERSION = 9;

        public const LIBRARY_MINOR_VERSION = 1;

        public const LIBRARY_VERSION_MAJOR = 9;

        public const LIBRARY_VERSION_MINOR = 1;

        public const VERSION_STRING = 'polyfill-1.0.8';

        public const BASE64_VARIANT_ORIGINAL = 1;

        public const BASE64_VARIANT_ORIGINAL_NO_PADDING = 3;

        // From libsodium
        public const BASE64_VARIANT_URLSAFE = 5;

        public const BASE64_VARIANT_URLSAFE_NO_PADDING = 7;

        public const CRYPTO_AEAD_AES256GCM_KEYBYTES = 32;

        public const CRYPTO_AEAD_AES256GCM_NSECBYTES = 0;

        public const CRYPTO_AEAD_AES256GCM_NPUBBYTES = 12;

        public const CRYPTO_AEAD_AES256GCM_ABYTES = 16;

        public const CRYPTO_AEAD_CHACHA20POLY1305_KEYBYTES = 32;

        public const CRYPTO_AEAD_CHACHA20POLY1305_NSECBYTES = 0;

        public const CRYPTO_AEAD_CHACHA20POLY1305_NPUBBYTES = 8;

        public const CRYPTO_AEAD_CHACHA20POLY1305_ABYTES = 16;

        public const CRYPTO_AEAD_CHACHA20POLY1305_IETF_KEYBYTES = 32;

        public const CRYPTO_AEAD_CHACHA20POLY1305_IETF_NSECBYTES = 0;

        public const CRYPTO_AEAD_CHACHA20POLY1305_IETF_NPUBBYTES = 12;

        public const CRYPTO_AEAD_CHACHA20POLY1305_IETF_ABYTES = 16;

        public const CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES = 32;

        public const CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NSECBYTES = 0;

        public const CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES = 24;

        public const CRYPTO_AEAD_XCHACHA20POLY1305_IETF_ABYTES = 16;

        public const CRYPTO_AUTH_BYTES = 32;

        public const CRYPTO_AUTH_KEYBYTES = 32;

        public const CRYPTO_BOX_SEALBYTES = 16;

        public const CRYPTO_BOX_SECRETKEYBYTES = 32;

        public const CRYPTO_BOX_PUBLICKEYBYTES = 32;

        public const CRYPTO_BOX_KEYPAIRBYTES = 64;

        public const CRYPTO_BOX_MACBYTES = 16;

        public const CRYPTO_BOX_NONCEBYTES = 24;

        public const CRYPTO_BOX_SEEDBYTES = 32;

        public const CRYPTO_CORE_RISTRETTO255_BYTES = 32;

        public const CRYPTO_CORE_RISTRETTO255_SCALARBYTES = 32;

        public const CRYPTO_CORE_RISTRETTO255_HASHBYTES = 64;

        public const CRYPTO_CORE_RISTRETTO255_NONREDUCEDSCALARBYTES = 64;

        public const CRYPTO_KDF_BYTES_MIN = 16;

        public const CRYPTO_KDF_BYTES_MAX = 64;

        public const CRYPTO_KDF_CONTEXTBYTES = 8;

        public const CRYPTO_KDF_KEYBYTES = 32;

        public const CRYPTO_KX_BYTES = 32;

        public const CRYPTO_KX_PRIMITIVE = 'x25519blake2b';

        public const CRYPTO_KX_SEEDBYTES = 32;

        public const CRYPTO_KX_KEYPAIRBYTES = 64;

        public const CRYPTO_KX_PUBLICKEYBYTES = 32;

        public const CRYPTO_KX_SECRETKEYBYTES = 32;

        public const CRYPTO_KX_SESSIONKEYBYTES = 32;

        public const CRYPTO_GENERICHASH_BYTES = 32;

        public const CRYPTO_GENERICHASH_BYTES_MIN = 16;

        public const CRYPTO_GENERICHASH_BYTES_MAX = 64;

        public const CRYPTO_GENERICHASH_KEYBYTES = 32;

        public const CRYPTO_GENERICHASH_KEYBYTES_MIN = 16;

        public const CRYPTO_GENERICHASH_KEYBYTES_MAX = 64;

        public const CRYPTO_PWHASH_SALTBYTES = 16;

        public const CRYPTO_PWHASH_STRPREFIX = '$argon2id$';

        public const CRYPTO_PWHASH_ALG_ARGON2I13 = 1;

        public const CRYPTO_PWHASH_ALG_ARGON2ID13 = 2;

        public const CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE = 33554432;

        public const CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE = 4;

        public const CRYPTO_PWHASH_MEMLIMIT_MODERATE = 134217728;

        public const CRYPTO_PWHASH_OPSLIMIT_MODERATE = 6;

        public const CRYPTO_PWHASH_MEMLIMIT_SENSITIVE = 536870912;

        public const CRYPTO_PWHASH_OPSLIMIT_SENSITIVE = 8;

        public const CRYPTO_PWHASH_SCRYPTSALSA208SHA256_SALTBYTES = 32;

        public const CRYPTO_PWHASH_SCRYPTSALSA208SHA256_STRPREFIX = '$7$';

        public const CRYPTO_PWHASH_SCRYPTSALSA208SHA256_OPSLIMIT_INTERACTIVE = 534288;

        public const CRYPTO_PWHASH_SCRYPTSALSA208SHA256_MEMLIMIT_INTERACTIVE = 16777216;

        public const CRYPTO_PWHASH_SCRYPTSALSA208SHA256_OPSLIMIT_SENSITIVE = 33554432;

        public const CRYPTO_PWHASH_SCRYPTSALSA208SHA256_MEMLIMIT_SENSITIVE = 1073741824;

        public const CRYPTO_SCALARMULT_BYTES = 32;

        public const CRYPTO_SCALARMULT_SCALARBYTES = 32;

        public const CRYPTO_SCALARMULT_RISTRETTO255_BYTES = 32;

        public const CRYPTO_SCALARMULT_RISTRETTO255_SCALARBYTES = 32;

        public const CRYPTO_SHORTHASH_BYTES = 8;

        public const CRYPTO_SHORTHASH_KEYBYTES = 16;

        public const CRYPTO_SECRETBOX_KEYBYTES = 32;

        public const CRYPTO_SECRETBOX_MACBYTES = 16;

        public const CRYPTO_SECRETBOX_NONCEBYTES = 24;

        public const CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_ABYTES = 17;

        public const CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES = 24;

        public const CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_KEYBYTES = 32;

        public const CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_PUSH = 0;

        public const CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_PULL = 1;

        public const CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_REKEY = 2;

        public const CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL = 3;

        public const CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_MESSAGEBYTES_MAX = 0x3fffffff80;

        public const CRYPTO_SIGN_BYTES = 64;

        public const CRYPTO_SIGN_SEEDBYTES = 32;

        public const CRYPTO_SIGN_PUBLICKEYBYTES = 32;

        public const CRYPTO_SIGN_SECRETKEYBYTES = 64;

        public const CRYPTO_SIGN_KEYPAIRBYTES = 96;

        public const CRYPTO_STREAM_KEYBYTES = 32;

        public const CRYPTO_STREAM_NONCEBYTES = 24;

        public const CRYPTO_STREAM_XCHACHA20_KEYBYTES = 32;

        public const CRYPTO_STREAM_XCHACHA20_NONCEBYTES = 24;

        public static $disableFallbackForUnitTests = false;

        public static $fastMult = false;

        public static function add(&$val, $addv)
        {
            $val_len = ParagonIE_Sodium_Core_Util::strlen($val);
            $addv_len = ParagonIE_Sodium_Core_Util::strlen($addv);
            if($val_len !== $addv_len)
            {
                throw new SodiumException('values must have the same length');
            }
            $A = ParagonIE_Sodium_Core_Util::stringToIntArray($val);
            $B = ParagonIE_Sodium_Core_Util::stringToIntArray($addv);

            $c = 0;
            for($i = 0; $i < $val_len; $i++)
            {
                $c += ($A[$i] + $B[$i]);
                $A[$i] = ($c & 0xff);
                $c >>= 8;
            }
            $val = ParagonIE_Sodium_Core_Util::intArrayToString($A);
        }

        public static function base642bin($encoded, $variant, $ignore = '')
        {
            /* Type checks: */
            ParagonIE_Sodium_Core_Util::declareScalarType($encoded, 'string', 1);

            $encoded = (string) $encoded;
            if(ParagonIE_Sodium_Core_Util::strlen($encoded) === 0)
            {
                return '';
            }

            // Just strip before decoding
            if(! empty($ignore))
            {
                $encoded = str_replace($ignore, '', $encoded);
            }

            try
            {
                switch($variant)
                {
                    case self::BASE64_VARIANT_ORIGINAL:
                        return ParagonIE_Sodium_Core_Base64_Original::decode($encoded, true);
                    case self::BASE64_VARIANT_ORIGINAL_NO_PADDING:
                        return ParagonIE_Sodium_Core_Base64_Original::decode($encoded, false);
                    case self::BASE64_VARIANT_URLSAFE:
                        return ParagonIE_Sodium_Core_Base64_UrlSafe::decode($encoded, true);
                    case self::BASE64_VARIANT_URLSAFE_NO_PADDING:
                        return ParagonIE_Sodium_Core_Base64_UrlSafe::decode($encoded, false);
                    default:
                        throw new SodiumException('invalid base64 variant identifier');
                }
            }
            catch(Exception $ex)
            {
                if($ex instanceof SodiumException)
                {
                    throw $ex;
                }
                throw new SodiumException('invalid base64 string');
            }
        }

        public static function bin2base64($decoded, $variant)
        {
            /* Type checks: */
            ParagonIE_Sodium_Core_Util::declareScalarType($decoded, 'string', 1);

            $decoded = (string) $decoded;
            if(ParagonIE_Sodium_Core_Util::strlen($decoded) === 0)
            {
                return '';
            }

            switch($variant)
            {
                case self::BASE64_VARIANT_ORIGINAL:
                    return ParagonIE_Sodium_Core_Base64_Original::encode($decoded);
                case self::BASE64_VARIANT_ORIGINAL_NO_PADDING:
                    return ParagonIE_Sodium_Core_Base64_Original::encodeUnpadded($decoded);
                case self::BASE64_VARIANT_URLSAFE:
                    return ParagonIE_Sodium_Core_Base64_UrlSafe::encode($decoded);
                case self::BASE64_VARIANT_URLSAFE_NO_PADDING:
                    return ParagonIE_Sodium_Core_Base64_UrlSafe::encodeUnpadded($decoded);
                default:
                    throw new SodiumException('invalid base64 variant identifier');
            }
        }

        public static function bin2hex($string)
        {
            /* Type checks: */
            ParagonIE_Sodium_Core_Util::declareScalarType($string, 'string', 1);

            if(self::useNewSodiumAPI())
            {
                return (string) sodium_bin2hex($string);
            }
            if(self::use_fallback('bin2hex'))
            {
                return (string) call_user_func('\\Sodium\\bin2hex', $string);
            }

            return ParagonIE_Sodium_Core_Util::bin2hex($string);
        }

        protected static function useNewSodiumAPI()
        {
            static $res = null;
            if($res === null)
            {
                $res = PHP_VERSION_ID >= 70000 && extension_loaded('sodium');
            }
            if(self::$disableFallbackForUnitTests)
            {
                // Don't fallback. Use the PHP implementation.
                return false;
            }

            return (bool) $res;
        }

        protected static function use_fallback($sodium_func_name = '')
        {
            static $res = null;
            if($res === null)
            {
                $res = extension_loaded('libsodium') && PHP_VERSION_ID >= 50300;
            }
            if($res === false || self::$disableFallbackForUnitTests)
            {
                // Don't fallback. Use the PHP implementation.
                return false;
            }
            if(! empty($sodium_func_name))
            {
                return is_callable('\\Sodium\\'.$sodium_func_name);
            }

            return true;
        }

        public static function compare($left, $right)
        {
            /* Type checks: */
            ParagonIE_Sodium_Core_Util::declareScalarType($left, 'string', 1);
            ParagonIE_Sodium_Core_Util::declareScalarType($right, 'string', 2);

            if(self::useNewSodiumAPI())
            {
                return (int) sodium_compare($left, $right);
            }
            if(self::use_fallback('compare'))
            {
                return (int) call_user_func('\\Sodium\\compare', $left, $right);
            }

            return ParagonIE_Sodium_Core_Util::compare($left, $right);
        }

        public static function crypto_aead_aes256gcm_decrypt(
            $ciphertext = '', $assocData = '', $nonce = '', $key = ''
        ) {
            if(! self::crypto_aead_aes256gcm_is_available())
            {
                throw new SodiumException('AES-256-GCM is not available');
            }
            ParagonIE_Sodium_Core_Util::declareScalarType($ciphertext, 'string', 1);
            ParagonIE_Sodium_Core_Util::declareScalarType($assocData, 'string', 2);
            ParagonIE_Sodium_Core_Util::declareScalarType($nonce, 'string', 3);
            ParagonIE_Sodium_Core_Util::declareScalarType($key, 'string', 4);

            /* Input validation: */
            if(ParagonIE_Sodium_Core_Util::strlen($nonce) !== self::CRYPTO_AEAD_AES256GCM_NPUBBYTES)
            {
                throw new SodiumException('Nonce must be CRYPTO_AEAD_AES256GCM_NPUBBYTES long');
            }
            if(ParagonIE_Sodium_Core_Util::strlen($key) !== self::CRYPTO_AEAD_AES256GCM_KEYBYTES)
            {
                throw new SodiumException('Key must be CRYPTO_AEAD_AES256GCM_KEYBYTES long');
            }
            if(ParagonIE_Sodium_Core_Util::strlen($ciphertext) < self::CRYPTO_AEAD_AES256GCM_ABYTES)
            {
                throw new SodiumException('Message must be at least CRYPTO_AEAD_AES256GCM_ABYTES long');
            }
            if(! is_callable('openssl_decrypt'))
            {
                throw new SodiumException('The OpenSSL extension is not installed, or openssl_decrypt() is not available');
            }

            $ctext = ParagonIE_Sodium_Core_Util::substr($ciphertext, 0, -self::CRYPTO_AEAD_AES256GCM_ABYTES);

            $authTag = ParagonIE_Sodium_Core_Util::substr($ciphertext, -self::CRYPTO_AEAD_AES256GCM_ABYTES, 16);

            return openssl_decrypt($ctext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $authTag, $assocData);
        }

        public static function crypto_aead_aes256gcm_is_available()
        {
            if(self::useNewSodiumAPI())
            {
                return sodium_crypto_aead_aes256gcm_is_available();
            }
            if(self::use_fallback('crypto_aead_aes256gcm_is_available'))
            {
                return call_user_func('\\Sodium\\crypto_aead_aes256gcm_is_available');
            }
            if(PHP_VERSION_ID < 70100 || ! is_callable('openssl_encrypt') || ! is_callable('openssl_decrypt'))
            {
                // OpenSSL isn't installed
                return false;
            }

            return (bool) in_array('aes-256-gcm', openssl_get_cipher_methods());
        }

        public static function crypto_aead_aes256gcm_encrypt(
            $plaintext = '', $assocData = '', $nonce = '', $key = ''
        ) {
            if(! self::crypto_aead_aes256gcm_is_available())
            {
                throw new SodiumException('AES-256-GCM is not available');
            }
            ParagonIE_Sodium_Core_Util::declareScalarType($plaintext, 'string', 1);
            ParagonIE_Sodium_Core_Util::declareScalarType($assocData, 'string', 2);
            ParagonIE_Sodium_Core_Util::declareScalarType($nonce, 'string', 3);
            ParagonIE_Sodium_Core_Util::declareScalarType($key, 'string', 4);

            /* Input validation: */
            if(ParagonIE_Sodium_Core_Util::strlen($nonce) !== self::CRYPTO_AEAD_AES256GCM_NPUBBYTES)
            {
                throw new SodiumException('Nonce must be CRYPTO_AEAD_AES256GCM_NPUBBYTES long');
            }
            if(ParagonIE_Sodium_Core_Util::strlen($key) !== self::CRYPTO_AEAD_AES256GCM_KEYBYTES)
            {
                throw new SodiumException('Key must be CRYPTO_AEAD_AES256GCM_KEYBYTES long');
            }

            if(! is_callable('openssl_encrypt'))
            {
                throw new SodiumException('The OpenSSL extension is not installed, or openssl_encrypt() is not available');
            }

            $authTag = '';
            $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $authTag, $assocData);

            return $ciphertext.$authTag;
        }

        public static function crypto_aead_aes256gcm_keygen()
        {
            return random_bytes(self::CRYPTO_AEAD_AES256GCM_KEYBYTES);
        }

        public static function crypto_aead_chacha20poly1305_decrypt(
            $ciphertext = '', $assocData = '', $nonce = '', $key = ''
        ) {
            /* Type checks: */
            ParagonIE_Sodium_Core_Util::declareScalarType($ciphertext, 'string', 1);
            ParagonIE_Sodium_Core_Util::declareScalarType($assocData, 'string', 2);
            ParagonIE_Sodium_Core_Util::declareScalarType($nonce, 'string', 3);
            ParagonIE_Sodium_Core_Util::declareScalarType($key, 'string', 4);

            /* Input validation: */
            if(ParagonIE_Sodium_Core_Util::strlen($nonce) !== self::CRYPTO_AEAD_CHACHA20POLY1305_NPUBBYTES)
            {
                throw new SodiumException('Nonce must be CRYPTO_AEAD_CHACHA20POLY1305_NPUBBYTES long');
            }
            if(ParagonIE_Sodium_Core_Util::strlen($key) !== self::CRYPTO_AEAD_CHACHA20POLY1305_KEYBYTES)
            {
                throw new SodiumException('Key must be CRYPTO_AEAD_CHACHA20POLY1305_KEYBYTES long');
            }
            if(ParagonIE_Sodium_Core_Util::strlen($ciphertext) < self::CRYPTO_AEAD_CHACHA20POLY1305_ABYTES)
            {
                throw new SodiumException('Message must be at least CRYPTO_AEAD_CHACHA20POLY1305_ABYTES long');
            }

            if(self::useNewSodiumAPI())
            {
                return sodium_crypto_aead_chacha20poly1305_decrypt($ciphertext, $assocData, $nonce, $key);
            }
            if(self::use_fallback('crypto_aead_chacha20poly1305_decrypt'))
            {
                return call_user_func('\\Sodium\\crypto_aead_chacha20poly1305_decrypt', $ciphertext, $assocData, $nonce, $key);
            }
            if(PHP_INT_SIZE === 4)
            {
                return ParagonIE_Sodium_Crypto32::aead_chacha20poly1305_decrypt($ciphertext, $assocData, $nonce, $key);
            }

            return ParagonIE_Sodium_Crypto::aead_chacha20poly1305_decrypt($ciphertext, $assocData, $nonce, $key);
        }

        public static function crypto_aead_chacha20poly1305_encrypt(
            $plaintext = '', $assocData = '', $nonce = '', $key = ''
        ) {
            /* Type checks: */
            ParagonIE_Sodium_Core_Util::declareScalarType($plaintext, 'string', 1);
            ParagonIE_Sodium_Core_Util::declareScalarType($assocData, 'string', 2);
            ParagonIE_Sodium_Core_Util::declareScalarType($nonce, 'string', 3);
            ParagonIE_Sodium_Core_Util::declareScalarType($key, 'string', 4);

            /* Input validation: */
            if(ParagonIE_Sodium_Core_Util::strlen($nonce) !== self::CRYPTO_AEAD_CHACHA20POLY1305_NPUBBYTES)
            {
                throw new SodiumException('Nonce must be CRYPTO_AEAD_CHACHA20POLY1305_NPUBBYTES long');
            }
            if(ParagonIE_Sodium_Core_Util::strlen($key) !== self::CRYPTO_AEAD_CHACHA20POLY1305_KEYBYTES)
            {
                throw new SodiumException('Key must be CRYPTO_AEAD_CHACHA20POLY1305_KEYBYTES long');
            }

            if(self::useNewSodiumAPI())
            {
                return (string) sodium_crypto_aead_chacha20poly1305_encrypt($plaintext, $assocData, $nonce, $key);
            }
            if(self::use_fallback('crypto_aead_chacha20poly1305_encrypt'))
            {
                return (string) call_user_func('\\Sodium\\crypto_aead_chacha20poly1305_encrypt', $plaintext, $assocData, $nonce, $key);
            }
            if(PHP_INT_SIZE === 4)
            {
                return ParagonIE_Sodium_Crypto32::aead_chacha20poly1305_encrypt($plaintext, $assocData, $nonce, $key);
            }

            return ParagonIE_Sodium_Crypto::aead_chacha20poly1305_encrypt($plaintext, $assocData, $nonce, $key);
        }

        public static function crypto_aead_chacha20poly1305_ietf_decrypt(
            $ciphertext = '', $assocData = '', $nonce = '', $key = ''
        ) {
            /* Type checks: */
            ParagonIE_Sodium_Core_Util::declareScalarType($ciphertext, 'string', 1);
            ParagonIE_Sodium_Core_Util::declareScalarType($assocData, 'string', 2);
            ParagonIE_Sodium_Core_Util::declareScalarType($nonce, 'string', 3);
            ParagonIE_Sodium_Core_Util::declareScalarType($key, 'string', 4);

            /* Input validation: */
            if(ParagonIE_Sodium_Core_Util::strlen($nonce) !== self::CRYPTO_AEAD_CHACHA20POLY1305_IETF_NPUBBYTES)
            {
                throw new SodiumException('Nonce must be CRYPTO_AEAD_CHACHA20POLY1305_IETF_NPUBBYTES long');
            }
            if(ParagonIE_Sodium_Core_Util::strlen($key) !== self::CRYPTO_AEAD_CHACHA20POLY1305_KEYBYTES)
            {
                throw new SodiumException('Key must be CRYPTO_AEAD_CHACHA20POLY1305_KEYBYTES long');
            }
            if(ParagonIE_Sodium_Core_Util::strlen($ciphertext) < self::CRYPTO_AEAD_CHACHA20POLY1305_ABYTES)
            {
                throw new SodiumException('Message must be at least CRYPTO_AEAD_CHACHA20POLY1305_ABYTES long');
            }

            if(self::useNewSodiumAPI())
            {
                return sodium_crypto_aead_chacha20poly1305_ietf_decrypt($ciphertext, $assocData, $nonce, $key);
            }
            if(self::use_fallback('crypto_aead_chacha20poly1305_ietf_decrypt'))
            {
                return call_user_func('\\Sodium\\crypto_aead_chacha20poly1305_ietf_decrypt', $ciphertext, $assocData, $nonce, $key);
            }
            if(PHP_INT_SIZE === 4)
            {
                return ParagonIE_Sodium_Crypto32::aead_chacha20poly1305_ietf_decrypt($ciphertext, $assocData, $nonce, $key);
            }

            return ParagonIE_Sodium_Crypto::aead_chacha20poly1305_ietf_decrypt($ciphertext, $assocData, $nonce, $key);
        }

        public static function crypto_aead_chacha20poly1305_keygen()
        {
            return random_bytes(self::CRYPTO_AEAD_CHACHA20POLY1305_KEYBYTES);
        }

        public static function crypto_aead_chacha20poly1305_ietf_encrypt(
            $plaintext = '', $assocData = '', $nonce = '', $key = ''
        ) {
            /* Type checks: */
            ParagonIE_Sodium_Core_Util::declareScalarType($plaintext, 'string', 1);
            if(! is_null($assocData))
            {
                ParagonIE_Sodium_Core_Util::declareScalarType($assocData, 'string', 2);
            }
            ParagonIE_Sodium_Core_Util::declareScalarType($nonce, 'string', 3);
            ParagonIE_Sodium_Core_Util::declareScalarType($key, 'string', 4);

            /* Input validation: */
            if(ParagonIE_Sodium_Core_Util::strlen($nonce) !== self::CRYPTO_AEAD_CHACHA20POLY1305_IETF_NPUBBYTES)
            {
                throw new SodiumException('Nonce must be CRYPTO_AEAD_CHACHA20POLY1305_IETF_NPUBBYTES long');
            }
            if(ParagonIE_Sodium_Core_Util::strlen($key) !== self::CRYPTO_AEAD_CHACHA20POLY1305_KEYBYTES)
            {
                throw new SodiumException('Key must be CRYPTO_AEAD_CHACHA20POLY1305_KEYBYTES long');
            }

            if(self::useNewSodiumAPI())
            {
                return (string) sodium_crypto_aead_chacha20poly1305_ietf_encrypt($plaintext, $assocData, $nonce, $key);
            }
            if(self::use_fallback('crypto_aead_chacha20poly1305_ietf_encrypt'))
            {
                return (string) call_user_func('\\Sodium\\crypto_aead_chacha20poly1305_ietf_encrypt', $plaintext, $assocData, $nonce, $key);
            }
            if(PHP_INT_SIZE === 4)
            {
                return ParagonIE_Sodium_Crypto32::aead_chacha20poly1305_ietf_encrypt($plaintext, $assocData, $nonce, $key);
            }

            return ParagonIE_Sodium_Crypto::aead_chacha20poly1305_ietf_encrypt($plaintext, $assocData, $nonce, $key);
        }

        public static function crypto_aead_chacha20poly1305_ietf_keygen()
        {
            return random_bytes(self::CRYPTO_AEAD_CHACHA20POLY1305_IETF_KEYBYTES);
        }

        public static function crypto_aead_xchacha20poly1305_ietf_decrypt(
            $ciphertext = '', $assocData = '', $nonce = '', $key = '', $dontFallback = false
        ) {
            /* Type checks: */
            ParagonIE_Sodium_Core_Util::declareScalarType($ciphertext, 'string', 1);
            if(is_null($assocData))
            {
                $assocData = '';
            }
            else
            {
                ParagonIE_Sodium_Core_Util::declareScalarType($assocData, 'string', 2);
            }
            ParagonIE_Sodium_Core_Util::declareScalarType($nonce, 'string', 3);
            ParagonIE_Sodium_Core_Util::declareScalarType($key, 'string', 4);

            /* Input validation: */
            if(ParagonIE_Sodium_Core_Util::strlen($nonce) !== self::CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES)
            {
                throw new SodiumException('Nonce must be CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES long');
            }
            if(ParagonIE_Sodium_Core_Util::strlen($key) !== self::CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES)
            {
                throw new SodiumException('Key must be CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES long');
            }
            if(ParagonIE_Sodium_Core_Util::strlen($ciphertext) < self::CRYPTO_AEAD_XCHACHA20POLY1305_IETF_ABYTES)
            {
                throw new SodiumException('Message must be at least CRYPTO_AEAD_XCHACHA20POLY1305_IETF_ABYTES long');
            }
            if(self::useNewSodiumAPI() && ! $dontFallback && is_callable('sodium_crypto_aead_xchacha20poly1305_ietf_decrypt'))
            {
                return sodium_crypto_aead_xchacha20poly1305_ietf_decrypt($ciphertext, $assocData, $nonce, $key);
            }

            if(PHP_INT_SIZE === 4)
            {
                return ParagonIE_Sodium_Crypto32::aead_xchacha20poly1305_ietf_decrypt($ciphertext, $assocData, $nonce, $key);
            }

            return ParagonIE_Sodium_Crypto::aead_xchacha20poly1305_ietf_decrypt($ciphertext, $assocData, $nonce, $key);
        }

        public static function crypto_aead_xchacha20poly1305_ietf_encrypt(
            $plaintext = '', $assocData = '', $nonce = '', $key = '', $dontFallback = false
        ) {
            /* Type checks: */
            ParagonIE_Sodium_Core_Util::declareScalarType($plaintext, 'string', 1);
            if(is_null($assocData))
            {
                $assocData = '';
            }
            else
            {
                ParagonIE_Sodium_Core_Util::declareScalarType($assocData, 'string', 2);
            }
            ParagonIE_Sodium_Core_Util::declareScalarType($nonce, 'string', 3);
            ParagonIE_Sodium_Core_Util::declareScalarType($key, 'string', 4);

            /* Input validation: */
            if(ParagonIE_Sodium_Core_Util::strlen($nonce) !== self::CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES)
            {
                throw new SodiumException('Nonce must be CRYPTO_AEAD_XCHACHA20POLY1305_NPUBBYTES long');
            }
            if(ParagonIE_Sodium_Core_Util::strlen($key) !== self::CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES)
            {
                throw new SodiumException('Key must be CRYPTO_AEAD_XCHACHA20POLY1305_KEYBYTES long');
            }
            if(self::useNewSodiumAPI() && ! $dontFallback && is_callable('sodium_crypto_aead_xchacha20poly1305_ietf_encrypt'))
            {
                return sodium_crypto_aead_xchacha20poly1305_ietf_encrypt($plaintext, $assocData, $nonce, $key);
            }

            if(PHP_INT_SIZE === 4)
            {
                return ParagonIE_Sodium_Crypto32::aead_xchacha20poly1305_ietf_encrypt($plaintext, $assocData, $nonce, $key);
            }

            return ParagonIE_Sodium_Crypto::aead_xchacha20poly1305_ietf_encrypt($plaintext, $assocData, $nonce, $key);
        }

        public static function crypto_aead_xchacha20poly1305_ietf_keygen()
        {
            return random_bytes(self::CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES);
        }

        public static function crypto_auth($message, $key)
        {
            /* Type checks: */
            ParagonIE_Sodium_Core_Util::declareScalarType($message, 'string', 1);
            ParagonIE_Sodium_Core_Util::declareScalarType($key, 'string', 2);

            /* Input validation: */
            if(ParagonIE_Sodium_Core_Util::strlen($key) !== self::CRYPTO_AUTH_KEYBYTES)
            {
                throw new SodiumException('Argument 2 must be CRYPTO_AUTH_KEYBYTES long.');
            }

            if(self::useNewSodiumAPI())
            {
                return (string) sodium_crypto_auth($message, $key);
            }
            if(self::use_fallback('crypto_auth'))
            {
                return (string) call_user_func('\\Sodium\\crypto_auth', $message, $key);
            }
            if(PHP_INT_SIZE === 4)
            {
                return ParagonIE_Sodium_Crypto32::auth($message, $key);
            }

            return ParagonIE_Sodium_Crypto::auth($message, $key);
        }

        public static function crypto_auth_keygen()
        {
            return random_bytes(self::CRYPTO_AUTH_KEYBYTES);
        }

        public static function crypto_auth_verify($mac, $message, $key)
        {
            /* Type checks: */
            ParagonIE_Sodium_Core_Util::declareScalarType($mac, 'string', 1);
            ParagonIE_Sodium_Core_Util::declareScalarType($message, 'string', 2);
            ParagonIE_Sodium_Core_Util::declareScalarType($key, 'string', 3);

            /* Input validation: */
            if(ParagonIE_Sodium_Core_Util::strlen($mac) !== self::CRYPTO_AUTH_BYTES)
            {
                throw new SodiumException('Argument 1 must be CRYPTO_AUTH_BYTES long.');
            }
            if(ParagonIE_Sodium_Core_Util::strlen($key) !== self::CRYPTO_AUTH_KEYBYTES)
            {
                throw new SodiumException('Argument 3 must be CRYPTO_AUTH_KEYBYTES long.');
            }

            if(self::useNewSodiumAPI())
            {
                return (bool) sodium_crypto_auth_verify($mac, $message, $key);
            }
            if(self::use_fallback('crypto_auth_verify'))
            {
                return (bool) call_user_func('\\Sodium\\crypto_auth_verify', $mac, $message, $key);
            }
            if(PHP_INT_SIZE === 4)
            {
                return ParagonIE_Sodium_Crypto32::auth_verify($mac, $message, $key);
            }

            return ParagonIE_Sodium_Crypto::auth_verify($mac, $message, $key);
        }

        public static function crypto_box($plaintext, $nonce, $keypair)
        {
            /* Type checks: */
            ParagonIE_Sodium_Core_Util::declareScalarType($plaintext, 'string', 1);
            ParagonIE_Sodium_Core_Util::declareScalarType($nonce, 'string', 2);
            ParagonIE_Sodium_Core_Util::declareScalarType($keypair, 'string', 3);

            /* Input validation: */
            if(ParagonIE_Sodium_Core_Util::strlen($nonce) !== self::CRYPTO_BOX_NONCEBYTES)
            {
                throw new SodiumException('Argument 2 must be CRYPTO_BOX_NONCEBYTES long.');
            }
            if(ParagonIE_Sodium_Core_Util::strlen($keypair) !== self::CRYPTO_BOX_KEYPAIRBYTES)
            {
                throw new SodiumException('Argument 3 must be CRYPTO_BOX_KEYPAIRBYTES long.');
            }

            if(self::useNewSodiumAPI())
            {
                return (string) sodium_crypto_box($plaintext, $nonce, $keypair);
            }
            if(self::use_fallback('crypto_box'))
            {
                return (string) call_user_func('\\Sodium\\crypto_box', $plaintext, $nonce, $keypair);
            }
            if(PHP_INT_SIZE === 4)
            {
                return ParagonIE_Sodium_Crypto32::box($plaintext, $nonce, $keypair);
            }

            return ParagonIE_Sodium_Crypto::box($plaintext, $nonce, $keypair);
        }

        public static function crypto_box_seal($plaintext, $publicKey)
        {
            /* Type checks: */
            ParagonIE_Sodium_Core_Util::declareScalarType($plaintext, 'string', 1);
            ParagonIE_Sodium_Core_Util::declareScalarType($publicKey, 'string', 2);

            /* Input validation: */
            if(ParagonIE_Sodium_Core_Util::strlen($publicKey) !== self::CRYPTO_BOX_PUBLICKEYBYTES)
            {
                throw new SodiumException('Argument 2 must be CRYPTO_BOX_PUBLICKEYBYTES long.');
            }

            if(self::useNewSodiumAPI())
            {
                return (string) sodium_crypto_box_seal($plaintext, $publicKey);
            }
            if(self::use_fallback('crypto_box_seal'))
            {
                return (string) call_user_func('\\Sodium\\crypto_box_seal', $plaintext, $publicKey);
            }
            if(PHP_INT_SIZE === 4)
            {
                return ParagonIE_Sodium_Crypto32::box_seal($plaintext, $publicKey);
            }

            return ParagonIE_Sodium_Crypto::box_seal($plaintext, $publicKey);
        }

        public static function crypto_box_seal_open($ciphertext, $keypair)
        {
            /* Type checks: */
            ParagonIE_Sodium_Core_Util::declareScalarType($ciphertext, 'string', 1);
            ParagonIE_Sodium_Core_Util::declareScalarType($keypair, 'string', 2);

            /* Input validation: */
            if(ParagonIE_Sodium_Core_Util::strlen($keypair) !== self::CRYPTO_BOX_KEYPAIRBYTES)
            {
                throw new SodiumException('Argument 2 must be CRYPTO_BOX_KEYPAIRBYTES long.');
            }

            if(self::useNewSodiumAPI())
            {
                return sodium_crypto_box_seal_open($ciphertext, $keypair);
            }
            if(self::use_fallback('crypto_box_seal_open'))
            {
                return call_user_func('\\Sodium\\crypto_box_seal_open', $ciphertext, $keypair);
            }
            if(PHP_INT_SIZE === 4)
            {
                return ParagonIE_Sodium_Crypto32::box_seal_open($ciphertext, $keypair);
            }

            return ParagonIE_Sodium_Crypto::box_seal_open($ciphertext, $keypair);
        }

        public static function crypto_box_keypair()
        {
            if(self::useNewSodiumAPI())
            {
                return (string) sodium_crypto_box_keypair();
            }
            if(self::use_fallback('crypto_box_keypair'))
            {
                return (string) call_user_func('\\Sodium\\crypto_box_keypair');
            }
            if(PHP_INT_SIZE === 4)
            {
                return ParagonIE_Sodium_Crypto32::box_keypair();
            }

            return ParagonIE_Sodium_Crypto::box_keypair();
        }

        public static function crypto_box_keypair_from_secretkey_and_publickey($secretKey, $publicKey)
        {
            /* Type checks: */
            ParagonIE_Sodium_Core_Util::declareScalarType($secretKey, 'string', 1);
            ParagonIE_Sodium_Core_Util::declareScalarType($publicKey, 'string', 2);

            /* Input validation: */
            if(ParagonIE_Sodium_Core_Util::strlen($secretKey) !== self::CRYPTO_BOX_SECRETKEYBYTES)
            {
                throw new SodiumException('Argument 1 must be CRYPTO_BOX_SECRETKEYBYTES long.');
            }
            if(ParagonIE_Sodium_Core_Util::strlen($publicKey) !== self::CRYPTO_BOX_PUBLICKEYBYTES)
            {
                throw new SodiumException('Argument 2 must be CRYPTO_BOX_PUBLICKEYBYTES long.');
            }

            if(self::useNewSodiumAPI())
            {
                return (string) sodium_crypto_box_keypair_from_secretkey_and_publickey($secretKey, $publicKey);
            }
            if(self::use_fallback('crypto_box_keypair_from_secretkey_and_publickey'))
            {
                return (string) call_user_func('\\Sodium\\crypto_box_keypair_from_secretkey_and_publickey', $secretKey, $publicKey);
            }
            if(PHP_INT_SIZE === 4)
            {
                return ParagonIE_Sodium_Crypto32::box_keypair_from_secretkey_and_publickey($secretKey, $publicKey);
            }

            return ParagonIE_Sodium_Crypto::box_keypair_from_secretkey_and_publickey($secretKey, $publicKey);
        }

        public static function crypto_box_open($ciphertext, $nonce, $keypair)
        {
            /* Type checks: */
            ParagonIE_Sodium_Core_Util::declareScalarType($ciphertext, 'string', 1);
            ParagonIE_Sodium_Core_Util::declareScalarType($nonce, 'string', 2);
            ParagonIE_Sodium_Core_Util::declareScalarType($keypair, 'string', 3);

            /* Input validation: */
            if(ParagonIE_Sodium_Core_Util::strlen($ciphertext) < self::CRYPTO_BOX_MACBYTES)
            {
                throw new SodiumException('Argument 1 must be at least CRYPTO_BOX_MACBYTES long.');
            }
            if(ParagonIE_Sodium_Core_Util::strlen($nonce) !== self::CRYPTO_BOX_NONCEBYTES)
            {
                throw new SodiumException('Argument 2 must be CRYPTO_BOX_NONCEBYTES long.');
            }
            if(ParagonIE_Sodium_Core_Util::strlen($keypair) !== self::CRYPTO_BOX_KEYPAIRBYTES)
            {
                throw new SodiumException('Argument 3 must be CRYPTO_BOX_KEYPAIRBYTES long.');
            }

            if(self::useNewSodiumAPI())
            {
                return sodium_crypto_box_open($ciphertext, $nonce, $keypair);
            }
            if(self::use_fallback('crypto_box_open'))
            {
                return call_user_func('\\Sodium\\crypto_box_open', $ciphertext, $nonce, $keypair);
            }
            if(PHP_INT_SIZE === 4)
            {
                return ParagonIE_Sodium_Crypto32::box_open($ciphertext, $nonce, $keypair);
            }

            return ParagonIE_Sodium_Crypto::box_open($ciphertext, $nonce, $keypair);
        }

        public static function crypto_box_publickey($keypair)
        {
            /* Type checks: */
            ParagonIE_Sodium_Core_Util::declareScalarType($keypair, 'string', 1);

            /* Input validation: */
            if(ParagonIE_Sodium_Core_Util::strlen($keypair) !== self::CRYPTO_BOX_KEYPAIRBYTES)
            {
                throw new SodiumException('Argument 1 must be CRYPTO_BOX_KEYPAIRBYTES long.');
            }

            if(self::useNewSodiumAPI())
            {
                return (string) sodium_crypto_box_publickey($keypair);
            }
            if(self::use_fallback('crypto_box_publickey'))
            {
                return (string) call_user_func('\\Sodium\\crypto_box_publickey', $keypair);
            }
            if(PHP_INT_SIZE === 4)
            {
                return ParagonIE_Sodium_Crypto32::box_publickey($keypair);
            }

            return ParagonIE_Sodium_Crypto::box_publickey($keypair);
        }

        public static function crypto_box_publickey_from_secretkey($secretKey)
        {
            /* Type checks: */
            ParagonIE_Sodium_Core_Util::declareScalarType($secretKey, 'string', 1);

            /* Input validation: */
            if(ParagonIE_Sodium_Core_Util::strlen($secretKey) !== self::CRYPTO_BOX_SECRETKEYBYTES)
            {
                throw new SodiumException('Argument 1 must be CRYPTO_BOX_SECRETKEYBYTES long.');
            }

            if(self::useNewSodiumAPI())
            {
                return (string) sodium_crypto_box_publickey_from_secretkey($secretKey);
            }
            if(self::use_fallback('crypto_box_publickey_from_secretkey'))
            {
                return (string) call_user_func('\\Sodium\\crypto_box_publickey_from_secretkey', $secretKey);
            }
            if(PHP_INT_SIZE === 4)
            {
                return ParagonIE_Sodium_Crypto32::box_publickey_from_secretkey($secretKey);
            }

            return ParagonIE_Sodium_Crypto::box_publickey_from_secretkey($secretKey);
        }

        public static function crypto_box_secretkey($keypair)
        {
            /* Type checks: */
            ParagonIE_Sodium_Core_Util::declareScalarType($keypair, 'string', 1);

            /* Input validation: */
            if(ParagonIE_Sodium_Core_Util::strlen($keypair) !== self::CRYPTO_BOX_KEYPAIRBYTES)
            {
                throw new SodiumException('Argument 1 must be CRYPTO_BOX_KEYPAIRBYTES long.');
            }

            if(self::useNewSodiumAPI())
            {
                return (string) sodium_crypto_box_secretkey($keypair);
            }
            if(self::use_fallback('crypto_box_secretkey'))
            {
                return (string) call_user_func('\\Sodium\\crypto_box_secretkey', $keypair);
            }
            if(PHP_INT_SIZE === 4)
            {
                return ParagonIE_Sodium_Crypto32::box_secretkey($keypair);
            }

            return ParagonIE_Sodium_Crypto::box_secretkey($keypair);
        }

        public static function crypto_box_seed_keypair($seed)
        {
            /* Type checks: */
            ParagonIE_Sodium_Core_Util::declareScalarType($seed, 'string', 1);

            if(self::useNewSodiumAPI())
            {
                return (string) sodium_crypto_box_seed_keypair($seed);
            }
            if(self::use_fallback('crypto_box_seed_keypair'))
            {
                return (string) call_user_func('\\Sodium\\crypto_box_seed_keypair', $seed);
            }
            if(PHP_INT_SIZE === 4)
            {
                return ParagonIE_Sodium_Crypto32::box_seed_keypair($seed);
            }

            return ParagonIE_Sodium_Crypto::box_seed_keypair($seed);
        }

        public static function crypto_generichash_keygen()
        {
            return random_bytes(self::CRYPTO_GENERICHASH_KEYBYTES);
        }

        public static function crypto_kdf_derive_from_key(
            $subkey_len, $subkey_id, $context, $key
        ) {
            ParagonIE_Sodium_Core_Util::declareScalarType($subkey_len, 'int', 1);
            ParagonIE_Sodium_Core_Util::declareScalarType($subkey_id, 'int', 2);
            ParagonIE_Sodium_Core_Util::declareScalarType($context, 'string', 3);
            ParagonIE_Sodium_Core_Util::declareScalarType($key, 'string', 4);
            $subkey_id = (int) $subkey_id;
            $subkey_len = (int) $subkey_len;
            $context = (string) $context;
            $key = (string) $key;

            if($subkey_len < self::CRYPTO_KDF_BYTES_MIN)
            {
                throw new SodiumException('subkey cannot be smaller than SODIUM_CRYPTO_KDF_BYTES_MIN');
            }
            if($subkey_len > self::CRYPTO_KDF_BYTES_MAX)
            {
                throw new SodiumException('subkey cannot be larger than SODIUM_CRYPTO_KDF_BYTES_MAX');
            }
            if($subkey_id < 0)
            {
                throw new SodiumException('subkey_id cannot be negative');
            }
            if(ParagonIE_Sodium_Core_Util::strlen($context) !== self::CRYPTO_KDF_CONTEXTBYTES)
            {
                throw new SodiumException('context should be SODIUM_CRYPTO_KDF_CONTEXTBYTES bytes');
            }
            if(ParagonIE_Sodium_Core_Util::strlen($key) !== self::CRYPTO_KDF_KEYBYTES)
            {
                throw new SodiumException('key should be SODIUM_CRYPTO_KDF_KEYBYTES bytes');
            }

            $salt = ParagonIE_Sodium_Core_Util::store64_le($subkey_id);
            $state = self::crypto_generichash_init_salt_personal($key, $subkey_len, $salt, $context);

            return self::crypto_generichash_final($state, $subkey_len);
        }

        public static function crypto_generichash_init_salt_personal(
            $key = '', $length = self::CRYPTO_GENERICHASH_BYTES, $salt = '', $personal = ''
        ) {
            /* Type checks: */
            if(is_null($key))
            {
                $key = '';
            }
            ParagonIE_Sodium_Core_Util::declareScalarType($key, 'string', 1);
            ParagonIE_Sodium_Core_Util::declareScalarType($length, 'int', 2);
            ParagonIE_Sodium_Core_Util::declareScalarType($salt, 'string', 3);
            ParagonIE_Sodium_Core_Util::declareScalarType($personal, 'string', 4);
            $salt = str_pad($salt, 16, "\0", STR_PAD_RIGHT);
            $personal = str_pad($personal, 16, "\0", STR_PAD_RIGHT);

            /* Input validation: */
            if(! empty($key) && ParagonIE_Sodium_Core_Util::strlen($key) > self::CRYPTO_GENERICHASH_KEYBYTES_MAX)
            {
                throw new SodiumException('Unsupported key size. Must be at most CRYPTO_GENERICHASH_KEYBYTES_MAX bytes long.');
            }
            if(PHP_INT_SIZE === 4)
            {
                return ParagonIE_Sodium_Crypto32::generichash_init_salt_personal($key, $length, $salt, $personal);
            }

            return ParagonIE_Sodium_Crypto::generichash_init_salt_personal($key, $length, $salt, $personal);
        }

        public static function crypto_generichash_final(&$ctx, $length = self::CRYPTO_GENERICHASH_BYTES)
        {
            /* Type checks: */
            ParagonIE_Sodium_Core_Util::declareScalarType($ctx, 'string', 1);
            ParagonIE_Sodium_Core_Util::declareScalarType($length, 'int', 2);

            if(self::useNewSodiumAPI())
            {
                return sodium_crypto_generichash_final($ctx, $length);
            }
            if(self::use_fallback('crypto_generichash_final'))
            {
                $func = '\\Sodium\\crypto_generichash_final';

                return (string) $func($ctx, $length);
            }
            if($length < 1)
            {
                try
                {
                    self::memzero($ctx);
                }
                catch(SodiumException $ex)
                {
                    unset($ctx);
                }

                return '';
            }
            if(PHP_INT_SIZE === 4)
            {
                $result = ParagonIE_Sodium_Crypto32::generichash_final($ctx, $length);
            }
            else
            {
                $result = ParagonIE_Sodium_Crypto::generichash_final($ctx, $length);
            }
            try
            {
                self::memzero($ctx);
            }
            catch(SodiumException $ex)
            {
                unset($ctx);
            }

            return $result;
        }

        public static function memzero(&$var)
        {
            /* Type checks: */
            ParagonIE_Sodium_Core_Util::declareScalarType($var, 'string', 1);

            if(self::useNewSodiumAPI())
            {
                sodium_memzero($var);

                return;
            }
            if(self::use_fallback('memzero'))
            {
                $func = '\\Sodium\\memzero';
                $func($var);
                if($var === null)
                {
                    return;
                }
            }
            // This is the best we can do.
            throw new SodiumException('This is not implemented in sodium_compat, as it is not possible to securely wipe memory from PHP. '.'To fix this error, make sure libsodium is installed and the PHP extension is enabled.');
        }

        public static function crypto_kdf_keygen()
        {
            return random_bytes(self::CRYPTO_KDF_KEYBYTES);
        }

        public static function crypto_kx(
            $my_secret, $their_public, $client_public, $server_public, $dontFallback = false
        ) {
            /* Type checks: */
            ParagonIE_Sodium_Core_Util::declareScalarType($my_secret, 'string', 1);
            ParagonIE_Sodium_Core_Util::declareScalarType($their_public, 'string', 2);
            ParagonIE_Sodium_Core_Util::declareScalarType($client_public, 'string', 3);
            ParagonIE_Sodium_Core_Util::declareScalarType($server_public, 'string', 4);

            /* Input validation: */
            if(ParagonIE_Sodium_Core_Util::strlen($my_secret) !== self::CRYPTO_BOX_SECRETKEYBYTES)
            {
                throw new SodiumException('Argument 1 must be CRYPTO_BOX_SECRETKEYBYTES long.');
            }
            if(ParagonIE_Sodium_Core_Util::strlen($their_public) !== self::CRYPTO_BOX_PUBLICKEYBYTES)
            {
                throw new SodiumException('Argument 2 must be CRYPTO_BOX_PUBLICKEYBYTES long.');
            }
            if(ParagonIE_Sodium_Core_Util::strlen($client_public) !== self::CRYPTO_BOX_PUBLICKEYBYTES)
            {
                throw new SodiumException('Argument 3 must be CRYPTO_BOX_PUBLICKEYBYTES long.');
            }
            if(ParagonIE_Sodium_Core_Util::strlen($server_public) !== self::CRYPTO_BOX_PUBLICKEYBYTES)
            {
                throw new SodiumException('Argument 4 must be CRYPTO_BOX_PUBLICKEYBYTES long.');
            }

            if(self::useNewSodiumAPI() && ! $dontFallback && is_callable('sodium_crypto_kx'))
            {
                return (string) sodium_crypto_kx($my_secret, $their_public, $client_public, $server_public);
            }
            if(self::use_fallback('crypto_kx'))
            {
                return (string) call_user_func('\\Sodium\\crypto_kx', $my_secret, $their_public, $client_public, $server_public);
            }
            if(PHP_INT_SIZE === 4)
            {
                return ParagonIE_Sodium_Crypto32::keyExchange($my_secret, $their_public, $client_public, $server_public);
            }

            return ParagonIE_Sodium_Crypto::keyExchange($my_secret, $their_public, $client_public, $server_public);
        }

        public static function crypto_kx_seed_keypair($seed)
        {
            ParagonIE_Sodium_Core_Util::declareScalarType($seed, 'string', 1);

            $seed = (string) $seed;

            if(ParagonIE_Sodium_Core_Util::strlen($seed) !== self::CRYPTO_KX_SEEDBYTES)
            {
                throw new SodiumException('seed must be SODIUM_CRYPTO_KX_SEEDBYTES bytes');
            }

            $sk = self::crypto_generichash($seed, '', self::CRYPTO_KX_SECRETKEYBYTES);
            $pk = self::crypto_scalarmult_base($sk);

            return $sk.$pk;
        }

        public static function crypto_generichash($message, $key = '', $length = self::CRYPTO_GENERICHASH_BYTES)
        {
            /* Type checks: */
            ParagonIE_Sodium_Core_Util::declareScalarType($message, 'string', 1);
            if(is_null($key))
            {
                $key = '';
            }
            ParagonIE_Sodium_Core_Util::declareScalarType($key, 'string', 2);
            ParagonIE_Sodium_Core_Util::declareScalarType($length, 'int', 3);

            /* Input validation: */
            if(! empty($key))
            {
                if(ParagonIE_Sodium_Core_Util::strlen($key) < self::CRYPTO_GENERICHASH_KEYBYTES_MIN)
                {
                    throw new SodiumException('Unsupported key size. Must be at least CRYPTO_GENERICHASH_KEYBYTES_MIN bytes long.');
                }
                if(ParagonIE_Sodium_Core_Util::strlen($key) > self::CRYPTO_GENERICHASH_KEYBYTES_MAX)
                {
                    throw new SodiumException('Unsupported key size. Must be at most CRYPTO_GENERICHASH_KEYBYTES_MAX bytes long.');
                }
            }

            if(self::useNewSodiumAPI())
            {
                return (string) sodium_crypto_generichash($message, $key, $length);
            }
            if(self::use_fallback('crypto_generichash'))
            {
                return (string) call_user_func('\\Sodium\\crypto_generichash', $message, $key, $length);
            }
            if(PHP_INT_SIZE === 4)
            {
                return ParagonIE_Sodium_Crypto32::generichash($message, $key, $length);
            }

            return ParagonIE_Sodium_Crypto::generichash($message, $key, $length);
        }

        public static function crypto_scalarmult_base($secretKey)
        {
            /* Type checks: */
            ParagonIE_Sodium_Core_Util::declareScalarType($secretKey, 'string', 1);

            /* Input validation: */
            if(ParagonIE_Sodium_Core_Util::strlen($secretKey) !== self::CRYPTO_BOX_SECRETKEYBYTES)
            {
                throw new SodiumException('Argument 1 must be CRYPTO_BOX_SECRETKEYBYTES long.');
            }

            if(self::useNewSodiumAPI())
            {
                return sodium_crypto_scalarmult_base($secretKey);
            }
            if(self::use_fallback('crypto_scalarmult_base'))
            {
                return (string) call_user_func('\\Sodium\\crypto_scalarmult_base', $secretKey);
            }
            if(ParagonIE_Sodium_Core_Util::hashEquals($secretKey, str_repeat("\0", self::CRYPTO_BOX_SECRETKEYBYTES)))
            {
                throw new SodiumException('Zero secret key is not allowed');
            }
            if(PHP_INT_SIZE === 4)
            {
                return ParagonIE_Sodium_Crypto32::scalarmult_base($secretKey);
            }

            return ParagonIE_Sodium_Crypto::scalarmult_base($secretKey);
        }

        public static function crypto_kx_keypair()
        {
            $sk = self::randombytes_buf(self::CRYPTO_KX_SECRETKEYBYTES);
            $pk = self::crypto_scalarmult_base($sk);

            return $sk.$pk;
        }

        public static function randombytes_buf($numBytes)
        {
            /* Type checks: */
            if(! is_int($numBytes))
            {
                if(is_numeric($numBytes))
                {
                    $numBytes = (int) $numBytes;
                }
                else
                {
                    throw new TypeError('Argument 1 must be an integer, '.gettype($numBytes).' given.');
                }
            }

            if(self::use_fallback('randombytes_buf'))
            {
                return (string) call_user_func('\\Sodium\\randombytes_buf', $numBytes);
            }
            if($numBytes < 0)
            {
                throw new SodiumException("Number of bytes must be a positive integer");
            }

            return random_bytes($numBytes);
        }

        public static function crypto_kx_client_session_keys($keypair, $serverPublicKey)
        {
            ParagonIE_Sodium_Core_Util::declareScalarType($keypair, 'string', 1);
            ParagonIE_Sodium_Core_Util::declareScalarType($serverPublicKey, 'string', 2);

            $keypair = (string) $keypair;
            $serverPublicKey = (string) $serverPublicKey;

            if(ParagonIE_Sodium_Core_Util::strlen($keypair) !== self::CRYPTO_KX_KEYPAIRBYTES)
            {
                throw new SodiumException('keypair should be SODIUM_CRYPTO_KX_KEYPAIRBYTES bytes');
            }
            if(ParagonIE_Sodium_Core_Util::strlen($serverPublicKey) !== self::CRYPTO_KX_PUBLICKEYBYTES)
            {
                throw new SodiumException('public keys must be SODIUM_CRYPTO_KX_PUBLICKEYBYTES bytes');
            }

            $sk = self::crypto_kx_secretkey($keypair);
            $pk = self::crypto_kx_publickey($keypair);
            $h = self::crypto_generichash_init(null, self::CRYPTO_KX_SESSIONKEYBYTES * 2);
            self::crypto_generichash_update($h, self::crypto_scalarmult($sk, $serverPublicKey));
            self::crypto_generichash_update($h, $pk);
            self::crypto_generichash_update($h, $serverPublicKey);
            $sessionKeys = self::crypto_generichash_final($h, self::CRYPTO_KX_SESSIONKEYBYTES * 2);

            return [
                ParagonIE_Sodium_Core_Util::substr($sessionKeys, 0, self::CRYPTO_KX_SESSIONKEYBYTES),
                ParagonIE_Sodium_Core_Util::substr($sessionKeys, self::CRYPTO_KX_SESSIONKEYBYTES, self::CRYPTO_KX_SESSIONKEYBYTES)
            ];
        }

        public static function crypto_kx_secretkey($kp)
        {
            return ParagonIE_Sodium_Core_Util::substr($kp, 0, self::CRYPTO_KX_SECRETKEYBYTES);
        }

        public static function crypto_kx_publickey($kp)
        {
            return ParagonIE_Sodium_Core_Util::substr($kp, self::CRYPTO_KX_SECRETKEYBYTES, self::CRYPTO_KX_PUBLICKEYBYTES);
        }

        public static function crypto_generichash_init($key = '', $length = self::CRYPTO_GENERICHASH_BYTES)
        {
            /* Type checks: */
            if(is_null($key))
            {
                $key = '';
            }
            ParagonIE_Sodium_Core_Util::declareScalarType($key, 'string', 1);
            ParagonIE_Sodium_Core_Util::declareScalarType($length, 'int', 2);

            /* Input validation: */
            if(! empty($key))
            {
                if(ParagonIE_Sodium_Core_Util::strlen($key) < self::CRYPTO_GENERICHASH_KEYBYTES_MIN)
                {
                    throw new SodiumException('Unsupported key size. Must be at least CRYPTO_GENERICHASH_KEYBYTES_MIN bytes long.');
                }
                if(ParagonIE_Sodium_Core_Util::strlen($key) > self::CRYPTO_GENERICHASH_KEYBYTES_MAX)
                {
                    throw new SodiumException('Unsupported key size. Must be at most CRYPTO_GENERICHASH_KEYBYTES_MAX bytes long.');
                }
            }

            if(self::useNewSodiumAPI())
            {
                return sodium_crypto_generichash_init($key, $length);
            }
            if(self::use_fallback('crypto_generichash_init'))
            {
                return (string) call_user_func('\\Sodium\\crypto_generichash_init', $key, $length);
            }
            if(PHP_INT_SIZE === 4)
            {
                return ParagonIE_Sodium_Crypto32::generichash_init($key, $length);
            }

            return ParagonIE_Sodium_Crypto::generichash_init($key, $length);
        }

        public static function crypto_generichash_update(&$ctx, $message)
        {
            /* Type checks: */
            ParagonIE_Sodium_Core_Util::declareScalarType($ctx, 'string', 1);
            ParagonIE_Sodium_Core_Util::declareScalarType($message, 'string', 2);

            if(self::useNewSodiumAPI())
            {
                sodium_crypto_generichash_update($ctx, $message);

                return;
            }
            if(self::use_fallback('crypto_generichash_update'))
            {
                $func = '\\Sodium\\crypto_generichash_update';
                $func($ctx, $message);

                return;
            }
            if(PHP_INT_SIZE === 4)
            {
                $ctx = ParagonIE_Sodium_Crypto32::generichash_update($ctx, $message);
            }
            else
            {
                $ctx = ParagonIE_Sodium_Crypto::generichash_update($ctx, $message);
            }
        }

        public static function crypto_scalarmult($secretKey, $publicKey)
        {
            /* Type checks: */
            ParagonIE_Sodium_Core_Util::declareScalarType($secretKey, 'string', 1);
            ParagonIE_Sodium_Core_Util::declareScalarType($publicKey, 'string', 2);

            /* Input validation: */
            if(ParagonIE_Sodium_Core_Util::strlen($secretKey) !== self::CRYPTO_BOX_SECRETKEYBYTES)
            {
                throw new SodiumException('Argument 1 must be CRYPTO_BOX_SECRETKEYBYTES long.');
            }
            if(ParagonIE_Sodium_Core_Util::strlen($publicKey) !== self::CRYPTO_BOX_PUBLICKEYBYTES)
            {
                throw new SodiumException('Argument 2 must be CRYPTO_BOX_PUBLICKEYBYTES long.');
            }

            if(self::useNewSodiumAPI())
            {
                return sodium_crypto_scalarmult($secretKey, $publicKey);
            }
            if(self::use_fallback('crypto_scalarmult'))
            {
                return (string) call_user_func('\\Sodium\\crypto_scalarmult', $secretKey, $publicKey);
            }

            /* Output validation: Forbid all-zero keys */
            if(ParagonIE_Sodium_Core_Util::hashEquals($secretKey, str_repeat("\0", self::CRYPTO_BOX_SECRETKEYBYTES)))
            {
                throw new SodiumException('Zero secret key is not allowed');
            }
            if(ParagonIE_Sodium_Core_Util::hashEquals($publicKey, str_repeat("\0", self::CRYPTO_BOX_PUBLICKEYBYTES)))
            {
                throw new SodiumException('Zero public key is not allowed');
            }
            if(PHP_INT_SIZE === 4)
            {
                return ParagonIE_Sodium_Crypto32::scalarmult($secretKey, $publicKey);
            }

            return ParagonIE_Sodium_Crypto::scalarmult($secretKey, $publicKey);
        }

        public static function crypto_kx_server_session_keys($keypair, $clientPublicKey)
        {
            ParagonIE_Sodium_Core_Util::declareScalarType($keypair, 'string', 1);
            ParagonIE_Sodium_Core_Util::declareScalarType($clientPublicKey, 'string', 2);

            $keypair = (string) $keypair;
            $clientPublicKey = (string) $clientPublicKey;

            if(ParagonIE_Sodium_Core_Util::strlen($keypair) !== self::CRYPTO_KX_KEYPAIRBYTES)
            {
                throw new SodiumException('keypair should be SODIUM_CRYPTO_KX_KEYPAIRBYTES bytes');
            }
            if(ParagonIE_Sodium_Core_Util::strlen($clientPublicKey) !== self::CRYPTO_KX_PUBLICKEYBYTES)
            {
                throw new SodiumException('public keys must be SODIUM_CRYPTO_KX_PUBLICKEYBYTES bytes');
            }

            $sk = self::crypto_kx_secretkey($keypair);
            $pk = self::crypto_kx_publickey($keypair);
            $h = self::crypto_generichash_init(null, self::CRYPTO_KX_SESSIONKEYBYTES * 2);
            self::crypto_generichash_update($h, self::crypto_scalarmult($sk, $clientPublicKey));
            self::crypto_generichash_update($h, $clientPublicKey);
            self::crypto_generichash_update($h, $pk);
            $sessionKeys = self::crypto_generichash_final($h, self::CRYPTO_KX_SESSIONKEYBYTES * 2);

            return [
                ParagonIE_Sodium_Core_Util::substr($sessionKeys, self::CRYPTO_KX_SESSIONKEYBYTES, self::CRYPTO_KX_SESSIONKEYBYTES),
                ParagonIE_Sodium_Core_Util::substr($sessionKeys, 0, self::CRYPTO_KX_SESSIONKEYBYTES)
            ];
        }

        public static function crypto_pwhash($outlen, $passwd, $salt, $opslimit, $memlimit, $alg = null)
        {
            ParagonIE_Sodium_Core_Util::declareScalarType($outlen, 'int', 1);
            ParagonIE_Sodium_Core_Util::declareScalarType($passwd, 'string', 2);
            ParagonIE_Sodium_Core_Util::declareScalarType($salt, 'string', 3);
            ParagonIE_Sodium_Core_Util::declareScalarType($opslimit, 'int', 4);
            ParagonIE_Sodium_Core_Util::declareScalarType($memlimit, 'int', 5);

            if(self::useNewSodiumAPI())
            {
                if(! is_null($alg))
                {
                    ParagonIE_Sodium_Core_Util::declareScalarType($alg, 'int', 6);

                    return sodium_crypto_pwhash($outlen, $passwd, $salt, $opslimit, $memlimit, $alg);
                }

                return sodium_crypto_pwhash($outlen, $passwd, $salt, $opslimit, $memlimit);
            }
            if(self::use_fallback('crypto_pwhash'))
            {
                return (string) call_user_func('\\Sodium\\crypto_pwhash', $outlen, $passwd, $salt, $opslimit, $memlimit);
            }
            // This is the best we can do.
            throw new SodiumException('This is not implemented, as it is not possible to implement Argon2i with acceptable performance in pure-PHP');
        }

        public static function crypto_pwhash_is_available()
        {
            return self::useNewSodiumAPI() || self::use_fallback('crypto_pwhash');
        }

        public static function crypto_pwhash_str($passwd, $opslimit, $memlimit)
        {
            ParagonIE_Sodium_Core_Util::declareScalarType($passwd, 'string', 1);
            ParagonIE_Sodium_Core_Util::declareScalarType($opslimit, 'int', 2);
            ParagonIE_Sodium_Core_Util::declareScalarType($memlimit, 'int', 3);

            if(self::useNewSodiumAPI())
            {
                return sodium_crypto_pwhash_str($passwd, $opslimit, $memlimit);
            }
            if(self::use_fallback('crypto_pwhash_str'))
            {
                return (string) call_user_func('\\Sodium\\crypto_pwhash_str', $passwd, $opslimit, $memlimit);
            }
            // This is the best we can do.
            throw new SodiumException('This is not implemented, as it is not possible to implement Argon2i with acceptable performance in pure-PHP');
        }

        public static function crypto_pwhash_str_needs_rehash($hash, $opslimit, $memlimit)
        {
            ParagonIE_Sodium_Core_Util::declareScalarType($hash, 'string', 1);
            ParagonIE_Sodium_Core_Util::declareScalarType($opslimit, 'int', 2);
            ParagonIE_Sodium_Core_Util::declareScalarType($memlimit, 'int', 3);

            // Just grab the first 4 pieces.
            $pieces = explode('$', (string) $hash);
            $prefix = implode('$', array_slice($pieces, 0, 4));

            // Rebuild the expected header.

            $ops = (int) $opslimit;

            $mem = (int) $memlimit >> 10;
            $encoded = self::CRYPTO_PWHASH_STRPREFIX.'v=19$m='.$mem.',t='.$ops.',p=1';

            // Do they match? If so, we don't need to rehash, so return false.
            return ! ParagonIE_Sodium_Core_Util::hashEquals($encoded, $prefix);
        }

        public static function crypto_pwhash_str_verify($passwd, $hash)
        {
            ParagonIE_Sodium_Core_Util::declareScalarType($passwd, 'string', 1);
            ParagonIE_Sodium_Core_Util::declareScalarType($hash, 'string', 2);

            if(self::useNewSodiumAPI())
            {
                return (bool) sodium_crypto_pwhash_str_verify($passwd, $hash);
            }
            if(self::use_fallback('crypto_pwhash_str_verify'))
            {
                return (bool) call_user_func('\\Sodium\\crypto_pwhash_str_verify', $passwd, $hash);
            }
            // This is the best we can do.
            throw new SodiumException('This is not implemented, as it is not possible to implement Argon2i with acceptable performance in pure-PHP');
        }

        public static function crypto_pwhash_scryptsalsa208sha256($outlen, $passwd, $salt, $opslimit, $memlimit)
        {
            ParagonIE_Sodium_Core_Util::declareScalarType($outlen, 'int', 1);
            ParagonIE_Sodium_Core_Util::declareScalarType($passwd, 'string', 2);
            ParagonIE_Sodium_Core_Util::declareScalarType($salt, 'string', 3);
            ParagonIE_Sodium_Core_Util::declareScalarType($opslimit, 'int', 4);
            ParagonIE_Sodium_Core_Util::declareScalarType($memlimit, 'int', 5);

            if(self::useNewSodiumAPI())
            {
                return (string) sodium_crypto_pwhash_scryptsalsa208sha256((int) $outlen, (string) $passwd, (string) $salt, (int) $opslimit, (int) $memlimit);
            }
            if(self::use_fallback('crypto_pwhash_scryptsalsa208sha256'))
            {
                return (string) call_user_func('\\Sodium\\crypto_pwhash_scryptsalsa208sha256', (int) $outlen, (string) $passwd, (string) $salt, (int) $opslimit, (int) $memlimit);
            }
            // This is the best we can do.
            throw new SodiumException('This is not implemented, as it is not possible to implement Scrypt with acceptable performance in pure-PHP');
        }

        public static function crypto_pwhash_scryptsalsa208sha256_is_available()
        {
            return self::useNewSodiumAPI() || self::use_fallback('crypto_pwhash_scryptsalsa208sha256');
        }

        public static function crypto_pwhash_scryptsalsa208sha256_str($passwd, $opslimit, $memlimit)
        {
            ParagonIE_Sodium_Core_Util::declareScalarType($passwd, 'string', 1);
            ParagonIE_Sodium_Core_Util::declareScalarType($opslimit, 'int', 2);
            ParagonIE_Sodium_Core_Util::declareScalarType($memlimit, 'int', 3);

            if(self::useNewSodiumAPI())
            {
                return (string) sodium_crypto_pwhash_scryptsalsa208sha256_str((string) $passwd, (int) $opslimit, (int) $memlimit);
            }
            if(self::use_fallback('crypto_pwhash_scryptsalsa208sha256_str'))
            {
                return (string) call_user_func('\\Sodium\\crypto_pwhash_scryptsalsa208sha256_str', (string) $passwd, (int) $opslimit, (int) $memlimit);
            }
            // This is the best we can do.
            throw new SodiumException('This is not implemented, as it is not possible to implement Scrypt with acceptable performance in pure-PHP');
        }

        public static function crypto_pwhash_scryptsalsa208sha256_str_verify($passwd, $hash)
        {
            ParagonIE_Sodium_Core_Util::declareScalarType($passwd, 'string', 1);
            ParagonIE_Sodium_Core_Util::declareScalarType($hash, 'string', 2);

            if(self::useNewSodiumAPI())
            {
                return (bool) sodium_crypto_pwhash_scryptsalsa208sha256_str_verify((string) $passwd, (string) $hash);
            }
            if(self::use_fallback('crypto_pwhash_scryptsalsa208sha256_str_verify'))
            {
                return (bool) call_user_func('\\Sodium\\crypto_pwhash_scryptsalsa208sha256_str_verify', (string) $passwd, (string) $hash);
            }
            // This is the best we can do.
            throw new SodiumException('This is not implemented, as it is not possible to implement Scrypt with acceptable performance in pure-PHP');
        }

        public static function crypto_secretbox($plaintext, $nonce, $key)
        {
            /* Type checks: */
            ParagonIE_Sodium_Core_Util::declareScalarType($plaintext, 'string', 1);
            ParagonIE_Sodium_Core_Util::declareScalarType($nonce, 'string', 2);
            ParagonIE_Sodium_Core_Util::declareScalarType($key, 'string', 3);

            /* Input validation: */
            if(ParagonIE_Sodium_Core_Util::strlen($nonce) !== self::CRYPTO_SECRETBOX_NONCEBYTES)
            {
                throw new SodiumException('Argument 2 must be CRYPTO_SECRETBOX_NONCEBYTES long.');
            }
            if(ParagonIE_Sodium_Core_Util::strlen($key) !== self::CRYPTO_SECRETBOX_KEYBYTES)
            {
                throw new SodiumException('Argument 3 must be CRYPTO_SECRETBOX_KEYBYTES long.');
            }

            if(self::useNewSodiumAPI())
            {
                return sodium_crypto_secretbox($plaintext, $nonce, $key);
            }
            if(self::use_fallback('crypto_secretbox'))
            {
                return (string) call_user_func('\\Sodium\\crypto_secretbox', $plaintext, $nonce, $key);
            }
            if(PHP_INT_SIZE === 4)
            {
                return ParagonIE_Sodium_Crypto32::secretbox($plaintext, $nonce, $key);
            }

            return ParagonIE_Sodium_Crypto::secretbox($plaintext, $nonce, $key);
        }

        public static function crypto_secretbox_open($ciphertext, $nonce, $key)
        {
            /* Type checks: */
            ParagonIE_Sodium_Core_Util::declareScalarType($ciphertext, 'string', 1);
            ParagonIE_Sodium_Core_Util::declareScalarType($nonce, 'string', 2);
            ParagonIE_Sodium_Core_Util::declareScalarType($key, 'string', 3);

            /* Input validation: */
            if(ParagonIE_Sodium_Core_Util::strlen($nonce) !== self::CRYPTO_SECRETBOX_NONCEBYTES)
            {
                throw new SodiumException('Argument 2 must be CRYPTO_SECRETBOX_NONCEBYTES long.');
            }
            if(ParagonIE_Sodium_Core_Util::strlen($key) !== self::CRYPTO_SECRETBOX_KEYBYTES)
            {
                throw new SodiumException('Argument 3 must be CRYPTO_SECRETBOX_KEYBYTES long.');
            }

            if(self::useNewSodiumAPI())
            {
                return sodium_crypto_secretbox_open($ciphertext, $nonce, $key);
            }
            if(self::use_fallback('crypto_secretbox_open'))
            {
                return call_user_func('\\Sodium\\crypto_secretbox_open', $ciphertext, $nonce, $key);
            }
            if(PHP_INT_SIZE === 4)
            {
                return ParagonIE_Sodium_Crypto32::secretbox_open($ciphertext, $nonce, $key);
            }

            return ParagonIE_Sodium_Crypto::secretbox_open($ciphertext, $nonce, $key);
        }

        public static function crypto_secretbox_keygen()
        {
            return random_bytes(self::CRYPTO_SECRETBOX_KEYBYTES);
        }

        public static function crypto_secretbox_xchacha20poly1305($plaintext, $nonce, $key)
        {
            /* Type checks: */
            ParagonIE_Sodium_Core_Util::declareScalarType($plaintext, 'string', 1);
            ParagonIE_Sodium_Core_Util::declareScalarType($nonce, 'string', 2);
            ParagonIE_Sodium_Core_Util::declareScalarType($key, 'string', 3);

            /* Input validation: */
            if(ParagonIE_Sodium_Core_Util::strlen($nonce) !== self::CRYPTO_SECRETBOX_NONCEBYTES)
            {
                throw new SodiumException('Argument 2 must be CRYPTO_SECRETBOX_NONCEBYTES long.');
            }
            if(ParagonIE_Sodium_Core_Util::strlen($key) !== self::CRYPTO_SECRETBOX_KEYBYTES)
            {
                throw new SodiumException('Argument 3 must be CRYPTO_SECRETBOX_KEYBYTES long.');
            }
            if(PHP_INT_SIZE === 4)
            {
                return ParagonIE_Sodium_Crypto32::secretbox_xchacha20poly1305($plaintext, $nonce, $key);
            }

            return ParagonIE_Sodium_Crypto::secretbox_xchacha20poly1305($plaintext, $nonce, $key);
        }

        public static function crypto_secretbox_xchacha20poly1305_open($ciphertext, $nonce, $key)
        {
            /* Type checks: */
            ParagonIE_Sodium_Core_Util::declareScalarType($ciphertext, 'string', 1);
            ParagonIE_Sodium_Core_Util::declareScalarType($nonce, 'string', 2);
            ParagonIE_Sodium_Core_Util::declareScalarType($key, 'string', 3);

            /* Input validation: */
            if(ParagonIE_Sodium_Core_Util::strlen($nonce) !== self::CRYPTO_SECRETBOX_NONCEBYTES)
            {
                throw new SodiumException('Argument 2 must be CRYPTO_SECRETBOX_NONCEBYTES long.');
            }
            if(ParagonIE_Sodium_Core_Util::strlen($key) !== self::CRYPTO_SECRETBOX_KEYBYTES)
            {
                throw new SodiumException('Argument 3 must be CRYPTO_SECRETBOX_KEYBYTES long.');
            }

            if(PHP_INT_SIZE === 4)
            {
                return ParagonIE_Sodium_Crypto32::secretbox_xchacha20poly1305_open($ciphertext, $nonce, $key);
            }

            return ParagonIE_Sodium_Crypto::secretbox_xchacha20poly1305_open($ciphertext, $nonce, $key);
        }

        public static function crypto_secretstream_xchacha20poly1305_init_push($key)
        {
            if(PHP_INT_SIZE === 4)
            {
                return ParagonIE_Sodium_Crypto32::secretstream_xchacha20poly1305_init_push($key);
            }

            return ParagonIE_Sodium_Crypto::secretstream_xchacha20poly1305_init_push($key);
        }

        public static function crypto_secretstream_xchacha20poly1305_init_pull($header, $key)
        {
            if(ParagonIE_Sodium_Core_Util::strlen($header) < self::CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES)
            {
                throw new SodiumException('header size should be SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES bytes');
            }
            if(PHP_INT_SIZE === 4)
            {
                return ParagonIE_Sodium_Crypto32::secretstream_xchacha20poly1305_init_pull($key, $header);
            }

            return ParagonIE_Sodium_Crypto::secretstream_xchacha20poly1305_init_pull($key, $header);
        }

        public static function crypto_secretstream_xchacha20poly1305_push(&$state, $msg, $aad = '', $tag = 0)
        {
            if(PHP_INT_SIZE === 4)
            {
                return ParagonIE_Sodium_Crypto32::secretstream_xchacha20poly1305_push($state, $msg, $aad, $tag);
            }

            return ParagonIE_Sodium_Crypto::secretstream_xchacha20poly1305_push($state, $msg, $aad, $tag);
        }

        public static function crypto_secretstream_xchacha20poly1305_pull(&$state, $msg, $aad = '')
        {
            if(PHP_INT_SIZE === 4)
            {
                return ParagonIE_Sodium_Crypto32::secretstream_xchacha20poly1305_pull($state, $msg, $aad);
            }

            return ParagonIE_Sodium_Crypto::secretstream_xchacha20poly1305_pull($state, $msg, $aad);
        }

        public static function crypto_secretstream_xchacha20poly1305_keygen()
        {
            return random_bytes(self::CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_KEYBYTES);
        }

        public static function crypto_secretstream_xchacha20poly1305_rekey(&$state)
        {
            if(PHP_INT_SIZE === 4)
            {
                ParagonIE_Sodium_Crypto32::secretstream_xchacha20poly1305_rekey($state);
            }
            else
            {
                ParagonIE_Sodium_Crypto::secretstream_xchacha20poly1305_rekey($state);
            }
        }

        public static function crypto_shorthash($message, $key)
        {
            /* Type checks: */
            ParagonIE_Sodium_Core_Util::declareScalarType($message, 'string', 1);
            ParagonIE_Sodium_Core_Util::declareScalarType($key, 'string', 2);

            /* Input validation: */
            if(ParagonIE_Sodium_Core_Util::strlen($key) !== self::CRYPTO_SHORTHASH_KEYBYTES)
            {
                throw new SodiumException('Argument 2 must be CRYPTO_SHORTHASH_KEYBYTES long.');
            }

            if(self::useNewSodiumAPI())
            {
                return sodium_crypto_shorthash($message, $key);
            }
            if(self::use_fallback('crypto_shorthash'))
            {
                return (string) call_user_func('\\Sodium\\crypto_shorthash', $message, $key);
            }
            if(PHP_INT_SIZE === 4)
            {
                return ParagonIE_Sodium_Core32_SipHash::sipHash24($message, $key);
            }

            return ParagonIE_Sodium_Core_SipHash::sipHash24($message, $key);
        }

        public static function crypto_shorthash_keygen()
        {
            return random_bytes(self::CRYPTO_SHORTHASH_KEYBYTES);
        }

        public static function crypto_sign($message, $secretKey)
        {
            /* Type checks: */
            ParagonIE_Sodium_Core_Util::declareScalarType($message, 'string', 1);
            ParagonIE_Sodium_Core_Util::declareScalarType($secretKey, 'string', 2);

            /* Input validation: */
            if(ParagonIE_Sodium_Core_Util::strlen($secretKey) !== self::CRYPTO_SIGN_SECRETKEYBYTES)
            {
                throw new SodiumException('Argument 2 must be CRYPTO_SIGN_SECRETKEYBYTES long.');
            }

            if(self::useNewSodiumAPI())
            {
                return sodium_crypto_sign($message, $secretKey);
            }
            if(self::use_fallback('crypto_sign'))
            {
                return (string) call_user_func('\\Sodium\\crypto_sign', $message, $secretKey);
            }
            if(PHP_INT_SIZE === 4)
            {
                return ParagonIE_Sodium_Crypto32::sign($message, $secretKey);
            }

            return ParagonIE_Sodium_Crypto::sign($message, $secretKey);
        }

        public static function crypto_sign_open($signedMessage, $publicKey)
        {
            /* Type checks: */
            ParagonIE_Sodium_Core_Util::declareScalarType($signedMessage, 'string', 1);
            ParagonIE_Sodium_Core_Util::declareScalarType($publicKey, 'string', 2);

            /* Input validation: */
            if(ParagonIE_Sodium_Core_Util::strlen($signedMessage) < self::CRYPTO_SIGN_BYTES)
            {
                throw new SodiumException('Argument 1 must be at least CRYPTO_SIGN_BYTES long.');
            }
            if(ParagonIE_Sodium_Core_Util::strlen($publicKey) !== self::CRYPTO_SIGN_PUBLICKEYBYTES)
            {
                throw new SodiumException('Argument 2 must be CRYPTO_SIGN_PUBLICKEYBYTES long.');
            }

            if(self::useNewSodiumAPI())
            {
                return sodium_crypto_sign_open($signedMessage, $publicKey);
            }
            if(self::use_fallback('crypto_sign_open'))
            {
                return call_user_func('\\Sodium\\crypto_sign_open', $signedMessage, $publicKey);
            }
            if(PHP_INT_SIZE === 4)
            {
                return ParagonIE_Sodium_Crypto32::sign_open($signedMessage, $publicKey);
            }

            return ParagonIE_Sodium_Crypto::sign_open($signedMessage, $publicKey);
        }

        public static function crypto_sign_keypair()
        {
            if(self::useNewSodiumAPI())
            {
                return sodium_crypto_sign_keypair();
            }
            if(self::use_fallback('crypto_sign_keypair'))
            {
                return (string) call_user_func('\\Sodium\\crypto_sign_keypair');
            }
            if(PHP_INT_SIZE === 4)
            {
                return ParagonIE_Sodium_Core32_Ed25519::keypair();
            }

            return ParagonIE_Sodium_Core_Ed25519::keypair();
        }

        public static function crypto_sign_keypair_from_secretkey_and_publickey($sk, $pk)
        {
            ParagonIE_Sodium_Core_Util::declareScalarType($sk, 'string', 1);
            ParagonIE_Sodium_Core_Util::declareScalarType($pk, 'string', 1);
            $sk = (string) $sk;
            $pk = (string) $pk;

            if(ParagonIE_Sodium_Core_Util::strlen($sk) !== self::CRYPTO_SIGN_SECRETKEYBYTES)
            {
                throw new SodiumException('secretkey should be SODIUM_CRYPTO_SIGN_SECRETKEYBYTES bytes');
            }
            if(ParagonIE_Sodium_Core_Util::strlen($pk) !== self::CRYPTO_SIGN_PUBLICKEYBYTES)
            {
                throw new SodiumException('publickey should be SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES bytes');
            }

            if(self::useNewSodiumAPI())
            {
                return sodium_crypto_sign_keypair_from_secretkey_and_publickey($sk, $pk);
            }

            return $sk.$pk;
        }

        public static function crypto_sign_seed_keypair($seed)
        {
            ParagonIE_Sodium_Core_Util::declareScalarType($seed, 'string', 1);

            if(self::useNewSodiumAPI())
            {
                return sodium_crypto_sign_seed_keypair($seed);
            }
            if(self::use_fallback('crypto_sign_keypair'))
            {
                return (string) call_user_func('\\Sodium\\crypto_sign_seed_keypair', $seed);
            }
            $publicKey = '';
            $secretKey = '';
            if(PHP_INT_SIZE === 4)
            {
                ParagonIE_Sodium_Core32_Ed25519::seed_keypair($publicKey, $secretKey, $seed);
            }
            else
            {
                ParagonIE_Sodium_Core_Ed25519::seed_keypair($publicKey, $secretKey, $seed);
            }

            return $secretKey.$publicKey;
        }

        public static function crypto_sign_publickey($keypair)
        {
            /* Type checks: */
            ParagonIE_Sodium_Core_Util::declareScalarType($keypair, 'string', 1);

            /* Input validation: */
            if(ParagonIE_Sodium_Core_Util::strlen($keypair) !== self::CRYPTO_SIGN_KEYPAIRBYTES)
            {
                throw new SodiumException('Argument 1 must be CRYPTO_SIGN_KEYPAIRBYTES long.');
            }

            if(self::useNewSodiumAPI())
            {
                return sodium_crypto_sign_publickey($keypair);
            }
            if(self::use_fallback('crypto_sign_publickey'))
            {
                return (string) call_user_func('\\Sodium\\crypto_sign_publickey', $keypair);
            }
            if(PHP_INT_SIZE === 4)
            {
                return ParagonIE_Sodium_Core32_Ed25519::publickey($keypair);
            }

            return ParagonIE_Sodium_Core_Ed25519::publickey($keypair);
        }

        public static function crypto_sign_publickey_from_secretkey($secretKey)
        {
            /* Type checks: */
            ParagonIE_Sodium_Core_Util::declareScalarType($secretKey, 'string', 1);

            /* Input validation: */
            if(ParagonIE_Sodium_Core_Util::strlen($secretKey) !== self::CRYPTO_SIGN_SECRETKEYBYTES)
            {
                throw new SodiumException('Argument 1 must be CRYPTO_SIGN_SECRETKEYBYTES long.');
            }

            if(self::useNewSodiumAPI())
            {
                return sodium_crypto_sign_publickey_from_secretkey($secretKey);
            }
            if(self::use_fallback('crypto_sign_publickey_from_secretkey'))
            {
                return (string) call_user_func('\\Sodium\\crypto_sign_publickey_from_secretkey', $secretKey);
            }
            if(PHP_INT_SIZE === 4)
            {
                return ParagonIE_Sodium_Core32_Ed25519::publickey_from_secretkey($secretKey);
            }

            return ParagonIE_Sodium_Core_Ed25519::publickey_from_secretkey($secretKey);
        }

        public static function crypto_sign_secretkey($keypair)
        {
            /* Type checks: */
            ParagonIE_Sodium_Core_Util::declareScalarType($keypair, 'string', 1);

            /* Input validation: */
            if(ParagonIE_Sodium_Core_Util::strlen($keypair) !== self::CRYPTO_SIGN_KEYPAIRBYTES)
            {
                throw new SodiumException('Argument 1 must be CRYPTO_SIGN_KEYPAIRBYTES long.');
            }

            if(self::useNewSodiumAPI())
            {
                return sodium_crypto_sign_secretkey($keypair);
            }
            if(self::use_fallback('crypto_sign_secretkey'))
            {
                return (string) call_user_func('\\Sodium\\crypto_sign_secretkey', $keypair);
            }
            if(PHP_INT_SIZE === 4)
            {
                return ParagonIE_Sodium_Core32_Ed25519::secretkey($keypair);
            }

            return ParagonIE_Sodium_Core_Ed25519::secretkey($keypair);
        }

        public static function crypto_sign_detached($message, $secretKey)
        {
            /* Type checks: */
            ParagonIE_Sodium_Core_Util::declareScalarType($message, 'string', 1);
            ParagonIE_Sodium_Core_Util::declareScalarType($secretKey, 'string', 2);

            /* Input validation: */
            if(ParagonIE_Sodium_Core_Util::strlen($secretKey) !== self::CRYPTO_SIGN_SECRETKEYBYTES)
            {
                throw new SodiumException('Argument 2 must be CRYPTO_SIGN_SECRETKEYBYTES long.');
            }

            if(self::useNewSodiumAPI())
            {
                return sodium_crypto_sign_detached($message, $secretKey);
            }
            if(self::use_fallback('crypto_sign_detached'))
            {
                return (string) call_user_func('\\Sodium\\crypto_sign_detached', $message, $secretKey);
            }
            if(PHP_INT_SIZE === 4)
            {
                return ParagonIE_Sodium_Crypto32::sign_detached($message, $secretKey);
            }

            return ParagonIE_Sodium_Crypto::sign_detached($message, $secretKey);
        }

        public static function crypto_sign_verify_detached($signature, $message, $publicKey)
        {
            /* Type checks: */
            ParagonIE_Sodium_Core_Util::declareScalarType($signature, 'string', 1);
            ParagonIE_Sodium_Core_Util::declareScalarType($message, 'string', 2);
            ParagonIE_Sodium_Core_Util::declareScalarType($publicKey, 'string', 3);

            /* Input validation: */
            if(ParagonIE_Sodium_Core_Util::strlen($signature) !== self::CRYPTO_SIGN_BYTES)
            {
                throw new SodiumException('Argument 1 must be CRYPTO_SIGN_BYTES long.');
            }
            if(ParagonIE_Sodium_Core_Util::strlen($publicKey) !== self::CRYPTO_SIGN_PUBLICKEYBYTES)
            {
                throw new SodiumException('Argument 3 must be CRYPTO_SIGN_PUBLICKEYBYTES long.');
            }

            if(self::useNewSodiumAPI())
            {
                return sodium_crypto_sign_verify_detached($signature, $message, $publicKey);
            }
            if(self::use_fallback('crypto_sign_verify_detached'))
            {
                return (bool) call_user_func('\\Sodium\\crypto_sign_verify_detached', $signature, $message, $publicKey);
            }
            if(PHP_INT_SIZE === 4)
            {
                return ParagonIE_Sodium_Crypto32::sign_verify_detached($signature, $message, $publicKey);
            }

            return ParagonIE_Sodium_Crypto::sign_verify_detached($signature, $message, $publicKey);
        }

        public static function crypto_sign_ed25519_pk_to_curve25519($pk)
        {
            /* Type checks: */
            ParagonIE_Sodium_Core_Util::declareScalarType($pk, 'string', 1);

            /* Input validation: */
            if(ParagonIE_Sodium_Core_Util::strlen($pk) < self::CRYPTO_SIGN_PUBLICKEYBYTES)
            {
                throw new SodiumException('Argument 1 must be at least CRYPTO_SIGN_PUBLICKEYBYTES long.');
            }
            if(self::useNewSodiumAPI() && is_callable('crypto_sign_ed25519_pk_to_curve25519'))
            {
                return (string) sodium_crypto_sign_ed25519_pk_to_curve25519($pk);
            }
            if(self::use_fallback('crypto_sign_ed25519_pk_to_curve25519'))
            {
                return (string) call_user_func('\\Sodium\\crypto_sign_ed25519_pk_to_curve25519', $pk);
            }
            if(PHP_INT_SIZE === 4)
            {
                return ParagonIE_Sodium_Core32_Ed25519::pk_to_curve25519($pk);
            }

            return ParagonIE_Sodium_Core_Ed25519::pk_to_curve25519($pk);
        }

        public static function crypto_sign_ed25519_sk_to_curve25519($sk)
        {
            /* Type checks: */
            ParagonIE_Sodium_Core_Util::declareScalarType($sk, 'string', 1);

            /* Input validation: */
            if(ParagonIE_Sodium_Core_Util::strlen($sk) < self::CRYPTO_SIGN_SEEDBYTES)
            {
                throw new SodiumException('Argument 1 must be at least CRYPTO_SIGN_SEEDBYTES long.');
            }
            if(self::useNewSodiumAPI() && is_callable('crypto_sign_ed25519_sk_to_curve25519'))
            {
                return sodium_crypto_sign_ed25519_sk_to_curve25519($sk);
            }
            if(self::use_fallback('crypto_sign_ed25519_sk_to_curve25519'))
            {
                return (string) call_user_func('\\Sodium\\crypto_sign_ed25519_sk_to_curve25519', $sk);
            }

            $h = hash('sha512', ParagonIE_Sodium_Core_Util::substr($sk, 0, 32), true);
            $h[0] = ParagonIE_Sodium_Core_Util::intToChr(ParagonIE_Sodium_Core_Util::chrToInt($h[0]) & 248);
            $h[31] = ParagonIE_Sodium_Core_Util::intToChr((ParagonIE_Sodium_Core_Util::chrToInt($h[31]) & 127) | 64);

            return ParagonIE_Sodium_Core_Util::substr($h, 0, 32);
        }

        public static function crypto_stream($len, $nonce, $key)
        {
            /* Type checks: */
            ParagonIE_Sodium_Core_Util::declareScalarType($len, 'int', 1);
            ParagonIE_Sodium_Core_Util::declareScalarType($nonce, 'string', 2);
            ParagonIE_Sodium_Core_Util::declareScalarType($key, 'string', 3);

            /* Input validation: */
            if(ParagonIE_Sodium_Core_Util::strlen($nonce) !== self::CRYPTO_STREAM_NONCEBYTES)
            {
                throw new SodiumException('Argument 2 must be CRYPTO_SECRETBOX_NONCEBYTES long.');
            }
            if(ParagonIE_Sodium_Core_Util::strlen($key) !== self::CRYPTO_STREAM_KEYBYTES)
            {
                throw new SodiumException('Argument 3 must be CRYPTO_STREAM_KEYBYTES long.');
            }

            if(self::useNewSodiumAPI())
            {
                return sodium_crypto_stream($len, $nonce, $key);
            }
            if(self::use_fallback('crypto_stream'))
            {
                return (string) call_user_func('\\Sodium\\crypto_stream', $len, $nonce, $key);
            }
            if(PHP_INT_SIZE === 4)
            {
                return ParagonIE_Sodium_Core32_XSalsa20::xsalsa20($len, $nonce, $key);
            }

            return ParagonIE_Sodium_Core_XSalsa20::xsalsa20($len, $nonce, $key);
        }

        public static function crypto_stream_xor($message, $nonce, $key)
        {
            /* Type checks: */
            ParagonIE_Sodium_Core_Util::declareScalarType($message, 'string', 1);
            ParagonIE_Sodium_Core_Util::declareScalarType($nonce, 'string', 2);
            ParagonIE_Sodium_Core_Util::declareScalarType($key, 'string', 3);

            /* Input validation: */
            if(ParagonIE_Sodium_Core_Util::strlen($nonce) !== self::CRYPTO_STREAM_NONCEBYTES)
            {
                throw new SodiumException('Argument 2 must be CRYPTO_SECRETBOX_NONCEBYTES long.');
            }
            if(ParagonIE_Sodium_Core_Util::strlen($key) !== self::CRYPTO_STREAM_KEYBYTES)
            {
                throw new SodiumException('Argument 3 must be CRYPTO_SECRETBOX_KEYBYTES long.');
            }

            if(self::useNewSodiumAPI())
            {
                return sodium_crypto_stream_xor($message, $nonce, $key);
            }
            if(self::use_fallback('crypto_stream_xor'))
            {
                return (string) call_user_func('\\Sodium\\crypto_stream_xor', $message, $nonce, $key);
            }
            if(PHP_INT_SIZE === 4)
            {
                return ParagonIE_Sodium_Core32_XSalsa20::xsalsa20_xor($message, $nonce, $key);
            }

            return ParagonIE_Sodium_Core_XSalsa20::xsalsa20_xor($message, $nonce, $key);
        }

        public static function crypto_stream_keygen()
        {
            return random_bytes(self::CRYPTO_STREAM_KEYBYTES);
        }

        public static function crypto_stream_xchacha20($len, $nonce, $key, $dontFallback = false)
        {
            /* Type checks: */
            ParagonIE_Sodium_Core_Util::declareScalarType($len, 'int', 1);
            ParagonIE_Sodium_Core_Util::declareScalarType($nonce, 'string', 2);
            ParagonIE_Sodium_Core_Util::declareScalarType($key, 'string', 3);

            /* Input validation: */
            if(ParagonIE_Sodium_Core_Util::strlen($nonce) !== self::CRYPTO_STREAM_XCHACHA20_NONCEBYTES)
            {
                throw new SodiumException('Argument 2 must be CRYPTO_SECRETBOX_XCHACHA20_NONCEBYTES long.');
            }
            if(ParagonIE_Sodium_Core_Util::strlen($key) !== self::CRYPTO_STREAM_XCHACHA20_KEYBYTES)
            {
                throw new SodiumException('Argument 3 must be CRYPTO_STREAM_XCHACHA20_KEYBYTES long.');
            }

            if(self::useNewSodiumAPI() && ! $dontFallback)
            {
                return sodium_crypto_stream_xchacha20($len, $nonce, $key);
            }
            if(PHP_INT_SIZE === 4)
            {
                return ParagonIE_Sodium_Core32_XChaCha20::stream($len, $nonce, $key);
            }

            return ParagonIE_Sodium_Core_XChaCha20::stream($len, $nonce, $key);
        }

        public static function crypto_stream_xchacha20_xor($message, $nonce, $key, $dontFallback = false)
        {
            /* Type checks: */
            ParagonIE_Sodium_Core_Util::declareScalarType($message, 'string', 1);
            ParagonIE_Sodium_Core_Util::declareScalarType($nonce, 'string', 2);
            ParagonIE_Sodium_Core_Util::declareScalarType($key, 'string', 3);

            /* Input validation: */
            if(ParagonIE_Sodium_Core_Util::strlen($nonce) !== self::CRYPTO_STREAM_XCHACHA20_NONCEBYTES)
            {
                throw new SodiumException('Argument 2 must be CRYPTO_SECRETBOX_XCHACHA20_NONCEBYTES long.');
            }
            if(ParagonIE_Sodium_Core_Util::strlen($key) !== self::CRYPTO_STREAM_XCHACHA20_KEYBYTES)
            {
                throw new SodiumException('Argument 3 must be CRYPTO_SECRETBOX_XCHACHA20_KEYBYTES long.');
            }

            if(self::useNewSodiumAPI() && ! $dontFallback)
            {
                return sodium_crypto_stream_xchacha20_xor($message, $nonce, $key);
            }
            if(PHP_INT_SIZE === 4)
            {
                return ParagonIE_Sodium_Core32_XChaCha20::streamXorIc($message, $nonce, $key);
            }

            return ParagonIE_Sodium_Core_XChaCha20::streamXorIc($message, $nonce, $key);
        }

        public static function crypto_stream_xchacha20_xor_ic($message, $nonce, $counter, $key, $dontFallback = false)
        {
            /* Type checks: */
            ParagonIE_Sodium_Core_Util::declareScalarType($message, 'string', 1);
            ParagonIE_Sodium_Core_Util::declareScalarType($nonce, 'string', 2);
            ParagonIE_Sodium_Core_Util::declareScalarType($counter, 'int', 3);
            ParagonIE_Sodium_Core_Util::declareScalarType($key, 'string', 4);

            /* Input validation: */
            if(ParagonIE_Sodium_Core_Util::strlen($nonce) !== self::CRYPTO_STREAM_XCHACHA20_NONCEBYTES)
            {
                throw new SodiumException('Argument 2 must be CRYPTO_SECRETBOX_XCHACHA20_NONCEBYTES long.');
            }
            if(ParagonIE_Sodium_Core_Util::strlen($key) !== self::CRYPTO_STREAM_XCHACHA20_KEYBYTES)
            {
                throw new SodiumException('Argument 3 must be CRYPTO_SECRETBOX_XCHACHA20_KEYBYTES long.');
            }

            if(is_callable('sodium_crypto_stream_xchacha20_xor_ic') && ! $dontFallback)
            {
                return sodium_crypto_stream_xchacha20_xor_ic($message, $nonce, $counter, $key);
            }

            $ic = ParagonIE_Sodium_Core_Util::store64_le($counter);
            if(PHP_INT_SIZE === 4)
            {
                return ParagonIE_Sodium_Core32_XChaCha20::streamXorIc($message, $nonce, $key, $ic);
            }

            return ParagonIE_Sodium_Core_XChaCha20::streamXorIc($message, $nonce, $key, $ic);
        }

        public static function crypto_stream_xchacha20_keygen()
        {
            return random_bytes(self::CRYPTO_STREAM_XCHACHA20_KEYBYTES);
        }

        public static function hex2bin($string, $ignore = '')
        {
            /* Type checks: */
            ParagonIE_Sodium_Core_Util::declareScalarType($string, 'string', 1);
            ParagonIE_Sodium_Core_Util::declareScalarType($ignore, 'string', 2);

            if(self::useNewSodiumAPI() && is_callable('sodium_hex2bin'))
            {
                return (string) sodium_hex2bin($string, $ignore);
            }
            if(self::use_fallback('hex2bin'))
            {
                return (string) call_user_func('\\Sodium\\hex2bin', $string, $ignore);
            }

            return ParagonIE_Sodium_Core_Util::hex2bin($string, $ignore);
        }

        public static function increment(&$var)
        {
            /* Type checks: */
            ParagonIE_Sodium_Core_Util::declareScalarType($var, 'string', 1);

            if(self::useNewSodiumAPI())
            {
                sodium_increment($var);

                return;
            }
            if(self::use_fallback('increment'))
            {
                $func = '\\Sodium\\increment';
                $func($var);

                return;
            }

            $len = ParagonIE_Sodium_Core_Util::strlen($var);
            $c = 1;
            $copy = '';
            for($i = 0; $i < $len; ++$i)
            {
                $c += ParagonIE_Sodium_Core_Util::chrToInt(ParagonIE_Sodium_Core_Util::substr($var, $i, 1));
                $copy .= ParagonIE_Sodium_Core_Util::intToChr($c);
                $c >>= 8;
            }
            $var = $copy;
        }

        public static function is_zero($str)
        {
            $d = 0;
            for($i = 0; $i < 32; ++$i)
            {
                $d |= ParagonIE_Sodium_Core_Util::chrToInt($str[$i]);
            }

            return ((($d - 1) >> 31) & 1) === 1;
        }

        public static function library_version_major()
        {
            if(self::useNewSodiumAPI() && defined('SODIUM_LIBRARY_MAJOR_VERSION'))
            {
                return SODIUM_LIBRARY_MAJOR_VERSION;
            }
            if(self::use_fallback('library_version_major'))
            {
                return (int) call_user_func('\\Sodium\\library_version_major');
            }

            return self::LIBRARY_VERSION_MAJOR;
        }

        public static function library_version_minor()
        {
            if(self::useNewSodiumAPI() && defined('SODIUM_LIBRARY_MINOR_VERSION'))
            {
                return SODIUM_LIBRARY_MINOR_VERSION;
            }
            if(self::use_fallback('library_version_minor'))
            {
                return (int) call_user_func('\\Sodium\\library_version_minor');
            }

            return self::LIBRARY_VERSION_MINOR;
        }

        public static function memcmp($left, $right)
        {
            /* Type checks: */
            ParagonIE_Sodium_Core_Util::declareScalarType($left, 'string', 1);
            ParagonIE_Sodium_Core_Util::declareScalarType($right, 'string', 2);

            if(self::useNewSodiumAPI())
            {
                return sodium_memcmp($left, $right);
            }
            if(self::use_fallback('memcmp'))
            {
                return (int) call_user_func('\\Sodium\\memcmp', $left, $right);
            }

            return ParagonIE_Sodium_Core_Util::memcmp($left, $right);
        }

        public static function pad($unpadded, $blockSize, $dontFallback = false)
        {
            /* Type checks: */
            ParagonIE_Sodium_Core_Util::declareScalarType($unpadded, 'string', 1);
            ParagonIE_Sodium_Core_Util::declareScalarType($blockSize, 'int', 2);

            $unpadded = (string) $unpadded;
            $blockSize = (int) $blockSize;

            if(self::useNewSodiumAPI() && ! $dontFallback)
            {
                return (string) sodium_pad($unpadded, $blockSize);
            }

            if($blockSize <= 0)
            {
                throw new SodiumException('block size cannot be less than 1');
            }
            $unpadded_len = ParagonIE_Sodium_Core_Util::strlen($unpadded);
            $xpadlen = ($blockSize - 1);
            if(($blockSize & ($blockSize - 1)) === 0)
            {
                $xpadlen -= $unpadded_len & ($blockSize - 1);
            }
            else
            {
                $xpadlen -= $unpadded_len % $blockSize;
            }

            $xpadded_len = $unpadded_len + $xpadlen;
            $padded = str_repeat("\0", $xpadded_len - 1);
            if($unpadded_len > 0)
            {
                $st = 1;
                $i = 0;
                $k = $unpadded_len;
                for($j = 0; $j <= $xpadded_len; ++$j)
                {
                    $i = (int) $i;
                    $k = (int) $k;
                    $st = (int) $st;
                    if($j >= $unpadded_len)
                    {
                        $padded[$j] = "\0";
                    }
                    else
                    {
                        $padded[$j] = $unpadded[$j];
                    }

                    $k -= $st;
                    $st = (int) (~(((($k >> 48) | ($k >> 32) | ($k >> 16) | $k) - 1) >> 16)) & 1;
                    $i += $st;
                }
            }

            $mask = 0;
            $tail = $xpadded_len;
            for($i = 0; $i < $blockSize; ++$i)
            {
                # barrier_mask = (unsigned char)
                #     (((i ^ xpadlen) - 1U) >> ((sizeof(size_t) - 1U) * CHAR_BIT));
                $barrier_mask = (($i ^ $xpadlen) - 1) >> ((PHP_INT_SIZE << 3) - 1);
                # tail[-i] = (tail[-i] & mask) | (0x80 & barrier_mask);
                $padded[$tail - $i] = ParagonIE_Sodium_Core_Util::intToChr((ParagonIE_Sodium_Core_Util::chrToInt($padded[$tail - $i]) & $mask) | (0x80 & $barrier_mask));
                # mask |= barrier_mask;
                $mask |= $barrier_mask;
            }

            return $padded;
        }

        public static function unpad($padded, $blockSize, $dontFallback = false)
        {
            /* Type checks: */
            ParagonIE_Sodium_Core_Util::declareScalarType($padded, 'string', 1);
            ParagonIE_Sodium_Core_Util::declareScalarType($blockSize, 'int', 2);

            $padded = (string) $padded;
            $blockSize = (int) $blockSize;

            if(self::useNewSodiumAPI() && ! $dontFallback)
            {
                return (string) sodium_unpad($padded, $blockSize);
            }
            if($blockSize <= 0)
            {
                throw new SodiumException('block size cannot be less than 1');
            }
            $padded_len = ParagonIE_Sodium_Core_Util::strlen($padded);
            if($padded_len < $blockSize)
            {
                throw new SodiumException('invalid padding');
            }

            # tail = &padded[padded_len - 1U];
            $tail = $padded_len - 1;

            $acc = 0;
            $valid = 0;
            $pad_len = 0;

            $found = 0;
            for($i = 0; $i < $blockSize; ++$i)
            {
                # c = tail[-i];
                $c = ParagonIE_Sodium_Core_Util::chrToInt($padded[$tail - $i]);

                # is_barrier =
                #     (( (acc - 1U) & (pad_len - 1U) & ((c ^ 0x80) - 1U) ) >> 8) & 1U;
                $is_barrier = ((($acc - 1) & ($pad_len - 1) & (($c ^ 80) - 1)) >> 7) & 1;
                $is_barrier &= ~$found;
                $found |= $is_barrier;

                # acc |= c;
                $acc |= $c;

                # pad_len |= i & (1U + ~is_barrier);
                $pad_len |= $i & (1 + ~$is_barrier);

                # valid |= (unsigned char) is_barrier;
                $valid |= ($is_barrier & 0xff);
            }
            # unpadded_len = padded_len - 1U - pad_len;
            $unpadded_len = $padded_len - 1 - $pad_len;
            if($valid !== 1)
            {
                throw new SodiumException('invalid padding');
            }

            return ParagonIE_Sodium_Core_Util::substr($padded, 0, $unpadded_len);
        }

        public static function randombytes_uniform($range)
        {
            /* Type checks: */
            if(! is_int($range))
            {
                if(is_numeric($range))
                {
                    $range = (int) $range;
                }
                else
                {
                    throw new TypeError('Argument 1 must be an integer, '.gettype($range).' given.');
                }
            }
            if(self::use_fallback('randombytes_uniform'))
            {
                return (int) call_user_func('\\Sodium\\randombytes_uniform', $range);
            }

            return random_int(0, $range - 1);
        }

        public static function randombytes_random16()
        {
            if(self::use_fallback('randombytes_random16'))
            {
                return (int) call_user_func('\\Sodium\\randombytes_random16');
            }

            return random_int(0, 65535);
        }

        public static function ristretto255_is_valid_point($p, $dontFallback = false)
        {
            if(self::useNewSodiumAPI() && ! $dontFallback)
            {
                return sodium_crypto_core_ristretto255_is_valid_point($p);
            }
            try
            {
                $r = ParagonIE_Sodium_Core_Ristretto255::ristretto255_frombytes($p);

                return $r['res'] === 0 && ParagonIE_Sodium_Core_Ristretto255::ristretto255_point_is_canonical($p) === 1;
            }
            catch(SodiumException $ex)
            {
                if($ex->getMessage() === 'S is not canonical')
                {
                    return false;
                }
                throw $ex;
            }
        }

        public static function ristretto255_add($p, $q, $dontFallback = false)
        {
            if(self::useNewSodiumAPI() && ! $dontFallback)
            {
                return sodium_crypto_core_ristretto255_add($p, $q);
            }

            return ParagonIE_Sodium_Core_Ristretto255::ristretto255_add($p, $q);
        }

        public static function ristretto255_sub($p, $q, $dontFallback = false)
        {
            if(self::useNewSodiumAPI() && ! $dontFallback)
            {
                return sodium_crypto_core_ristretto255_sub($p, $q);
            }

            return ParagonIE_Sodium_Core_Ristretto255::ristretto255_sub($p, $q);
        }

        public static function ristretto255_from_hash($r, $dontFallback = false)
        {
            if(self::useNewSodiumAPI() && ! $dontFallback)
            {
                return sodium_crypto_core_ristretto255_from_hash($r);
            }

            return ParagonIE_Sodium_Core_Ristretto255::ristretto255_from_hash($r);
        }

        public static function ristretto255_random($dontFallback = false)
        {
            if(self::useNewSodiumAPI() && ! $dontFallback)
            {
                return sodium_crypto_core_ristretto255_random();
            }

            return ParagonIE_Sodium_Core_Ristretto255::ristretto255_random();
        }

        public static function ristretto255_scalar_random($dontFallback = false)
        {
            if(self::useNewSodiumAPI() && ! $dontFallback)
            {
                return sodium_crypto_core_ristretto255_scalar_random();
            }

            return ParagonIE_Sodium_Core_Ristretto255::ristretto255_scalar_random();
        }

        public static function ristretto255_scalar_invert($s, $dontFallback = false)
        {
            if(self::useNewSodiumAPI() && ! $dontFallback)
            {
                return sodium_crypto_core_ristretto255_scalar_invert($s);
            }

            return ParagonIE_Sodium_Core_Ristretto255::ristretto255_scalar_invert($s);
        }

        public static function ristretto255_scalar_negate($s, $dontFallback = false)
        {
            if(self::useNewSodiumAPI() && ! $dontFallback)
            {
                return sodium_crypto_core_ristretto255_scalar_negate($s);
            }

            return ParagonIE_Sodium_Core_Ristretto255::ristretto255_scalar_negate($s);
        }

        public static function ristretto255_scalar_complement($s, $dontFallback = false)
        {
            if(self::useNewSodiumAPI() && ! $dontFallback)
            {
                return sodium_crypto_core_ristretto255_scalar_complement($s);
            }

            return ParagonIE_Sodium_Core_Ristretto255::ristretto255_scalar_complement($s);
        }

        public static function ristretto255_scalar_add($x, $y, $dontFallback = false)
        {
            if(self::useNewSodiumAPI() && ! $dontFallback)
            {
                return sodium_crypto_core_ristretto255_scalar_add($x, $y);
            }

            return ParagonIE_Sodium_Core_Ristretto255::ristretto255_scalar_add($x, $y);
        }

        public static function ristretto255_scalar_sub($x, $y, $dontFallback = false)
        {
            if(self::useNewSodiumAPI() && ! $dontFallback)
            {
                return sodium_crypto_core_ristretto255_scalar_sub($x, $y);
            }

            return ParagonIE_Sodium_Core_Ristretto255::ristretto255_scalar_sub($x, $y);
        }

        public static function ristretto255_scalar_mul($x, $y, $dontFallback = false)
        {
            if(self::useNewSodiumAPI() && ! $dontFallback)
            {
                return sodium_crypto_core_ristretto255_scalar_mul($x, $y);
            }

            return ParagonIE_Sodium_Core_Ristretto255::ristretto255_scalar_mul($x, $y);
        }

        public static function scalarmult_ristretto255($n, $p, $dontFallback = false)
        {
            if(self::useNewSodiumAPI() && ! $dontFallback)
            {
                return sodium_crypto_scalarmult_ristretto255($n, $p);
            }

            return ParagonIE_Sodium_Core_Ristretto255::scalarmult_ristretto255($n, $p);
        }

        public static function scalarmult_ristretto255_base($n, $dontFallback = false)
        {
            if(self::useNewSodiumAPI() && ! $dontFallback)
            {
                return sodium_crypto_scalarmult_ristretto255_base($n);
            }

            return ParagonIE_Sodium_Core_Ristretto255::scalarmult_ristretto255_base($n);
        }

        public static function ristretto255_scalar_reduce($s, $dontFallback = false)
        {
            if(self::useNewSodiumAPI() && ! $dontFallback)
            {
                return sodium_crypto_core_ristretto255_scalar_reduce($s);
            }

            return ParagonIE_Sodium_Core_Ristretto255::sc_reduce($s);
        }

        public static function runtime_speed_test($iterations, $maxTimeout)
        {
            if(self::polyfill_is_fast())
            {
                return true;
            }

            $end = 0.0;

            $start = microtime(true);

            $a = ParagonIE_Sodium_Core32_Int64::fromInt(random_int(3, 1 << 16));
            for($i = 0; $i < $iterations; ++$i)
            {
                $b = ParagonIE_Sodium_Core32_Int64::fromInt(random_int(3, 1 << 16));
                $a->mulInt64($b);
            }

            $end = microtime(true);

            $diff = (int) ceil(($end - $start) * 1000);

            return $diff < $maxTimeout;
        }

        public static function polyfill_is_fast()
        {
            if(extension_loaded('sodium') || extension_loaded('libsodium'))
            {
                return true;
            }

            return PHP_INT_SIZE === 8;
        }

        public static function sub(&$val, $addv)
        {
            $val_len = ParagonIE_Sodium_Core_Util::strlen($val);
            $addv_len = ParagonIE_Sodium_Core_Util::strlen($addv);
            if($val_len !== $addv_len)
            {
                throw new SodiumException('values must have the same length');
            }
            $A = ParagonIE_Sodium_Core_Util::stringToIntArray($val);
            $B = ParagonIE_Sodium_Core_Util::stringToIntArray($addv);

            $c = 0;
            for($i = 0; $i < $val_len; $i++)
            {
                $c = ($A[$i] - $B[$i] - $c);
                $A[$i] = ($c & 0xff);
                $c = ($c >> 8) & 1;
            }
            $val = ParagonIE_Sodium_Core_Util::intArrayToString($A);
        }

        public static function version_string()
        {
            if(self::useNewSodiumAPI())
            {
                return (string) sodium_version_string();
            }
            if(self::use_fallback('version_string'))
            {
                return (string) call_user_func('\\Sodium\\version_string');
            }

            return (string) self::VERSION_STRING;
        }
    }
