<?php

    namespace Sodium;

    require_once dirname(dirname(__FILE__)).'/autoload.php';

    use ParagonIE_Sodium_Compat;

    if(! is_callable('\\Sodium\\bin2hex'))
    {
        function bin2hex($string)
        {
            return ParagonIE_Sodium_Compat::bin2hex($string);
        }
    }
    if(! is_callable('\\Sodium\\compare'))
    {
        function compare($a, $b)
        {
            return ParagonIE_Sodium_Compat::compare($a, $b);
        }
    }
    if(! is_callable('\\Sodium\\crypto_aead_aes256gcm_decrypt'))
    {
        function crypto_aead_aes256gcm_decrypt($message, $assocData, $nonce, $key)
        {
            try
            {
                return ParagonIE_Sodium_Compat::crypto_aead_aes256gcm_decrypt($message, $assocData, $nonce, $key);
            }
            catch(\TypeError $ex)
            {
                return false;
            }
            catch(\SodiumException $ex)
            {
                return false;
            }
        }
    }
    if(! is_callable('\\Sodium\\crypto_aead_aes256gcm_encrypt'))
    {
        function crypto_aead_aes256gcm_encrypt($message, $assocData, $nonce, $key)
        {
            return ParagonIE_Sodium_Compat::crypto_aead_aes256gcm_encrypt($message, $assocData, $nonce, $key);
        }
    }
    if(! is_callable('\\Sodium\\crypto_aead_aes256gcm_is_available'))
    {
        function crypto_aead_aes256gcm_is_available()
        {
            return ParagonIE_Sodium_Compat::crypto_aead_aes256gcm_is_available();
        }
    }
    if(! is_callable('\\Sodium\\crypto_aead_chacha20poly1305_decrypt'))
    {
        function crypto_aead_chacha20poly1305_decrypt($message, $assocData, $nonce, $key)
        {
            try
            {
                return ParagonIE_Sodium_Compat::crypto_aead_chacha20poly1305_decrypt($message, $assocData, $nonce, $key);
            }
            catch(\TypeError $ex)
            {
                return false;
            }
            catch(\SodiumException $ex)
            {
                return false;
            }
        }
    }
    if(! is_callable('\\Sodium\\crypto_aead_chacha20poly1305_encrypt'))
    {
        function crypto_aead_chacha20poly1305_encrypt($message, $assocData, $nonce, $key)
        {
            return ParagonIE_Sodium_Compat::crypto_aead_chacha20poly1305_encrypt($message, $assocData, $nonce, $key);
        }
    }
    if(! is_callable('\\Sodium\\crypto_aead_chacha20poly1305_ietf_decrypt'))
    {
        function crypto_aead_chacha20poly1305_ietf_decrypt($message, $assocData, $nonce, $key)
        {
            try
            {
                return ParagonIE_Sodium_Compat::crypto_aead_chacha20poly1305_ietf_decrypt($message, $assocData, $nonce, $key);
            }
            catch(\TypeError $ex)
            {
                return false;
            }
            catch(\SodiumException $ex)
            {
                return false;
            }
        }
    }
    if(! is_callable('\\Sodium\\crypto_aead_chacha20poly1305_ietf_encrypt'))
    {
        function crypto_aead_chacha20poly1305_ietf_encrypt($message, $assocData, $nonce, $key)
        {
            return ParagonIE_Sodium_Compat::crypto_aead_chacha20poly1305_ietf_encrypt($message, $assocData, $nonce, $key);
        }
    }
    if(! is_callable('\\Sodium\\crypto_auth'))
    {
        function crypto_auth($message, $key)
        {
            return ParagonIE_Sodium_Compat::crypto_auth($message, $key);
        }
    }
    if(! is_callable('\\Sodium\\crypto_auth_verify'))
    {
        function crypto_auth_verify($mac, $message, $key)
        {
            return ParagonIE_Sodium_Compat::crypto_auth_verify($mac, $message, $key);
        }
    }
    if(! is_callable('\\Sodium\\crypto_box'))
    {
        function crypto_box($message, $nonce, $kp)
        {
            return ParagonIE_Sodium_Compat::crypto_box($message, $nonce, $kp);
        }
    }
    if(! is_callable('\\Sodium\\crypto_box_keypair'))
    {
        function crypto_box_keypair()
        {
            return ParagonIE_Sodium_Compat::crypto_box_keypair();
        }
    }
    if(! is_callable('\\Sodium\\crypto_box_keypair_from_secretkey_and_publickey'))
    {
        function crypto_box_keypair_from_secretkey_and_publickey($sk, $pk)
        {
            return ParagonIE_Sodium_Compat::crypto_box_keypair_from_secretkey_and_publickey($sk, $pk);
        }
    }
    if(! is_callable('\\Sodium\\crypto_box_open'))
    {
        function crypto_box_open($message, $nonce, $kp)
        {
            try
            {
                return ParagonIE_Sodium_Compat::crypto_box_open($message, $nonce, $kp);
            }
            catch(\TypeError $ex)
            {
                return false;
            }
            catch(\SodiumException $ex)
            {
                return false;
            }
        }
    }
    if(! is_callable('\\Sodium\\crypto_box_publickey'))
    {
        function crypto_box_publickey($keypair)
        {
            return ParagonIE_Sodium_Compat::crypto_box_publickey($keypair);
        }
    }
    if(! is_callable('\\Sodium\\crypto_box_publickey_from_secretkey'))
    {
        function crypto_box_publickey_from_secretkey($sk)
        {
            return ParagonIE_Sodium_Compat::crypto_box_publickey_from_secretkey($sk);
        }
    }
    if(! is_callable('\\Sodium\\crypto_box_seal'))
    {
        function crypto_box_seal($message, $publicKey)
        {
            return ParagonIE_Sodium_Compat::crypto_box_seal($message, $publicKey);
        }
    }
    if(! is_callable('\\Sodium\\crypto_box_seal_open'))
    {
        function crypto_box_seal_open($message, $kp)
        {
            try
            {
                return ParagonIE_Sodium_Compat::crypto_box_seal_open($message, $kp);
            }
            catch(\TypeError $ex)
            {
                return false;
            }
            catch(\SodiumException $ex)
            {
                return false;
            }
        }
    }
    if(! is_callable('\\Sodium\\crypto_box_secretkey'))
    {
        function crypto_box_secretkey($keypair)
        {
            return ParagonIE_Sodium_Compat::crypto_box_secretkey($keypair);
        }
    }
    if(! is_callable('\\Sodium\\crypto_generichash'))
    {
        function crypto_generichash($message, $key = null, $outLen = 32)
        {
            return ParagonIE_Sodium_Compat::crypto_generichash($message, $key, $outLen);
        }
    }
    if(! is_callable('\\Sodium\\crypto_generichash_final'))
    {
        function crypto_generichash_final(&$ctx, $outputLength = 32)
        {
            return ParagonIE_Sodium_Compat::crypto_generichash_final($ctx, $outputLength);
        }
    }
    if(! is_callable('\\Sodium\\crypto_generichash_init'))
    {
        function crypto_generichash_init($key = null, $outLen = 32)
        {
            return ParagonIE_Sodium_Compat::crypto_generichash_init($key, $outLen);
        }
    }
    if(! is_callable('\\Sodium\\crypto_generichash_update'))
    {
        function crypto_generichash_update(&$ctx, $message = '')
        {
            ParagonIE_Sodium_Compat::crypto_generichash_update($ctx, $message);
        }
    }
    if(! is_callable('\\Sodium\\crypto_kx'))
    {
        function crypto_kx($my_secret, $their_public, $client_public, $server_public)
        {
            return ParagonIE_Sodium_Compat::crypto_kx($my_secret, $their_public, $client_public, $server_public, true);
        }
    }
    if(! is_callable('\\Sodium\\crypto_pwhash'))
    {
        function crypto_pwhash($outlen, $passwd, $salt, $opslimit, $memlimit)
        {
            return ParagonIE_Sodium_Compat::crypto_pwhash($outlen, $passwd, $salt, $opslimit, $memlimit);
        }
    }
    if(! is_callable('\\Sodium\\crypto_pwhash_str'))
    {
        function crypto_pwhash_str($passwd, $opslimit, $memlimit)
        {
            return ParagonIE_Sodium_Compat::crypto_pwhash_str($passwd, $opslimit, $memlimit);
        }
    }
    if(! is_callable('\\Sodium\\crypto_pwhash_str_verify'))
    {
        function crypto_pwhash_str_verify($passwd, $hash)
        {
            return ParagonIE_Sodium_Compat::crypto_pwhash_str_verify($passwd, $hash);
        }
    }
    if(! is_callable('\\Sodium\\crypto_pwhash_scryptsalsa208sha256'))
    {
        function crypto_pwhash_scryptsalsa208sha256($outlen, $passwd, $salt, $opslimit, $memlimit)
        {
            return ParagonIE_Sodium_Compat::crypto_pwhash_scryptsalsa208sha256($outlen, $passwd, $salt, $opslimit, $memlimit);
        }
    }
    if(! is_callable('\\Sodium\\crypto_pwhash_scryptsalsa208sha256_str'))
    {
        function crypto_pwhash_scryptsalsa208sha256_str($passwd, $opslimit, $memlimit)
        {
            return ParagonIE_Sodium_Compat::crypto_pwhash_scryptsalsa208sha256_str($passwd, $opslimit, $memlimit);
        }
    }
    if(! is_callable('\\Sodium\\crypto_pwhash_scryptsalsa208sha256_str_verify'))
    {
        function crypto_pwhash_scryptsalsa208sha256_str_verify($passwd, $hash)
        {
            return ParagonIE_Sodium_Compat::crypto_pwhash_scryptsalsa208sha256_str_verify($passwd, $hash);
        }
    }
    if(! is_callable('\\Sodium\\crypto_scalarmult'))
    {
        function crypto_scalarmult($n, $p)
        {
            return ParagonIE_Sodium_Compat::crypto_scalarmult($n, $p);
        }
    }
    if(! is_callable('\\Sodium\\crypto_scalarmult_base'))
    {
        function crypto_scalarmult_base($n)
        {
            return ParagonIE_Sodium_Compat::crypto_scalarmult_base($n);
        }
    }
    if(! is_callable('\\Sodium\\crypto_secretbox'))
    {
        function crypto_secretbox($message, $nonce, $key)
        {
            return ParagonIE_Sodium_Compat::crypto_secretbox($message, $nonce, $key);
        }
    }
    if(! is_callable('\\Sodium\\crypto_secretbox_open'))
    {
        function crypto_secretbox_open($message, $nonce, $key)
        {
            try
            {
                return ParagonIE_Sodium_Compat::crypto_secretbox_open($message, $nonce, $key);
            }
            catch(\TypeError $ex)
            {
                return false;
            }
            catch(\SodiumException $ex)
            {
                return false;
            }
        }
    }
    if(! is_callable('\\Sodium\\crypto_shorthash'))
    {
        function crypto_shorthash($message, $key = '')
        {
            return ParagonIE_Sodium_Compat::crypto_shorthash($message, $key);
        }
    }
    if(! is_callable('\\Sodium\\crypto_sign'))
    {
        function crypto_sign($message, $sk)
        {
            return ParagonIE_Sodium_Compat::crypto_sign($message, $sk);
        }
    }
    if(! is_callable('\\Sodium\\crypto_sign_detached'))
    {
        function crypto_sign_detached($message, $sk)
        {
            return ParagonIE_Sodium_Compat::crypto_sign_detached($message, $sk);
        }
    }
    if(! is_callable('\\Sodium\\crypto_sign_keypair'))
    {
        function crypto_sign_keypair()
        {
            return ParagonIE_Sodium_Compat::crypto_sign_keypair();
        }
    }
    if(! is_callable('\\Sodium\\crypto_sign_open'))
    {
        function crypto_sign_open($signedMessage, $pk)
        {
            try
            {
                return ParagonIE_Sodium_Compat::crypto_sign_open($signedMessage, $pk);
            }
            catch(\TypeError $ex)
            {
                return false;
            }
            catch(\SodiumException $ex)
            {
                return false;
            }
        }
    }
    if(! is_callable('\\Sodium\\crypto_sign_publickey'))
    {
        function crypto_sign_publickey($keypair)
        {
            return ParagonIE_Sodium_Compat::crypto_sign_publickey($keypair);
        }
    }
    if(! is_callable('\\Sodium\\crypto_sign_publickey_from_secretkey'))
    {
        function crypto_sign_publickey_from_secretkey($sk)
        {
            return ParagonIE_Sodium_Compat::crypto_sign_publickey_from_secretkey($sk);
        }
    }
    if(! is_callable('\\Sodium\\crypto_sign_secretkey'))
    {
        function crypto_sign_secretkey($keypair)
        {
            return ParagonIE_Sodium_Compat::crypto_sign_secretkey($keypair);
        }
    }
    if(! is_callable('\\Sodium\\crypto_sign_seed_keypair'))
    {
        function crypto_sign_seed_keypair($seed)
        {
            return ParagonIE_Sodium_Compat::crypto_sign_seed_keypair($seed);
        }
    }
    if(! is_callable('\\Sodium\\crypto_sign_verify_detached'))
    {
        function crypto_sign_verify_detached($signature, $message, $pk)
        {
            return ParagonIE_Sodium_Compat::crypto_sign_verify_detached($signature, $message, $pk);
        }
    }
    if(! is_callable('\\Sodium\\crypto_sign_ed25519_pk_to_curve25519'))
    {
        function crypto_sign_ed25519_pk_to_curve25519($pk)
        {
            return ParagonIE_Sodium_Compat::crypto_sign_ed25519_pk_to_curve25519($pk);
        }
    }
    if(! is_callable('\\Sodium\\crypto_sign_ed25519_sk_to_curve25519'))
    {
        function crypto_sign_ed25519_sk_to_curve25519($sk)
        {
            return ParagonIE_Sodium_Compat::crypto_sign_ed25519_sk_to_curve25519($sk);
        }
    }
    if(! is_callable('\\Sodium\\crypto_stream'))
    {
        function crypto_stream($len, $nonce, $key)
        {
            return ParagonIE_Sodium_Compat::crypto_stream($len, $nonce, $key);
        }
    }
    if(! is_callable('\\Sodium\\crypto_stream_xor'))
    {
        function crypto_stream_xor($message, $nonce, $key)
        {
            return ParagonIE_Sodium_Compat::crypto_stream_xor($message, $nonce, $key);
        }
    }
    if(! is_callable('\\Sodium\\hex2bin'))
    {
        function hex2bin($string)
        {
            return ParagonIE_Sodium_Compat::hex2bin($string);
        }
    }
    if(! is_callable('\\Sodium\\memcmp'))
    {
        function memcmp($a, $b)
        {
            return ParagonIE_Sodium_Compat::memcmp($a, $b);
        }
    }
    if(! is_callable('\\Sodium\\memzero'))
    {
        function memzero(&$str)
        {
            ParagonIE_Sodium_Compat::memzero($str);
        }
    }
    if(! is_callable('\\Sodium\\randombytes_buf'))
    {
        function randombytes_buf($amount)
        {
            return ParagonIE_Sodium_Compat::randombytes_buf($amount);
        }
    }

    if(! is_callable('\\Sodium\\randombytes_uniform'))
    {
        function randombytes_uniform($upperLimit)
        {
            return ParagonIE_Sodium_Compat::randombytes_uniform($upperLimit);
        }
    }

    if(! is_callable('\\Sodium\\randombytes_random16'))
    {
        function randombytes_random16()
        {
            return ParagonIE_Sodium_Compat::randombytes_random16();
        }
    }

    if(! defined('\\Sodium\\CRYPTO_AUTH_BYTES'))
    {
        require_once dirname(__FILE__).'/constants.php';
    }
