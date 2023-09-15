<?php

    require_once dirname(dirname(__FILE__)).'/autoload.php';

    foreach(
        [
            'BASE64_VARIANT_ORIGINAL',
            'BASE64_VARIANT_ORIGINAL_NO_PADDING',
            'BASE64_VARIANT_URLSAFE',
            'BASE64_VARIANT_URLSAFE_NO_PADDING',
            'CRYPTO_AEAD_CHACHA20POLY1305_KEYBYTES',
            'CRYPTO_AEAD_CHACHA20POLY1305_NSECBYTES',
            'CRYPTO_AEAD_CHACHA20POLY1305_NPUBBYTES',
            'CRYPTO_AEAD_CHACHA20POLY1305_ABYTES',
            'CRYPTO_AEAD_AES256GCM_KEYBYTES',
            'CRYPTO_AEAD_AES256GCM_NSECBYTES',
            'CRYPTO_AEAD_AES256GCM_NPUBBYTES',
            'CRYPTO_AEAD_AES256GCM_ABYTES',
            'CRYPTO_AEAD_CHACHA20POLY1305_IETF_KEYBYTES',
            'CRYPTO_AEAD_CHACHA20POLY1305_IETF_NSECBYTES',
            'CRYPTO_AEAD_CHACHA20POLY1305_IETF_NPUBBYTES',
            'CRYPTO_AEAD_CHACHA20POLY1305_IETF_ABYTES',
            'CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES',
            'CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NSECBYTES',
            'CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES',
            'CRYPTO_AEAD_XCHACHA20POLY1305_IETF_ABYTES',
            'CRYPTO_AUTH_BYTES',
            'CRYPTO_AUTH_KEYBYTES',
            'CRYPTO_BOX_SEALBYTES',
            'CRYPTO_BOX_SECRETKEYBYTES',
            'CRYPTO_BOX_PUBLICKEYBYTES',
            'CRYPTO_BOX_KEYPAIRBYTES',
            'CRYPTO_BOX_MACBYTES',
            'CRYPTO_BOX_NONCEBYTES',
            'CRYPTO_BOX_SEEDBYTES',
            'CRYPTO_KDF_BYTES_MIN',
            'CRYPTO_KDF_BYTES_MAX',
            'CRYPTO_KDF_CONTEXTBYTES',
            'CRYPTO_KDF_KEYBYTES',
            'CRYPTO_KX_BYTES',
            'CRYPTO_KX_KEYPAIRBYTES',
            'CRYPTO_KX_PRIMITIVE',
            'CRYPTO_KX_SEEDBYTES',
            'CRYPTO_KX_PUBLICKEYBYTES',
            'CRYPTO_KX_SECRETKEYBYTES',
            'CRYPTO_KX_SESSIONKEYBYTES',
            'CRYPTO_GENERICHASH_BYTES',
            'CRYPTO_GENERICHASH_BYTES_MIN',
            'CRYPTO_GENERICHASH_BYTES_MAX',
            'CRYPTO_GENERICHASH_KEYBYTES',
            'CRYPTO_GENERICHASH_KEYBYTES_MIN',
            'CRYPTO_GENERICHASH_KEYBYTES_MAX',
            'CRYPTO_PWHASH_SALTBYTES',
            'CRYPTO_PWHASH_STRPREFIX',
            'CRYPTO_PWHASH_ALG_ARGON2I13',
            'CRYPTO_PWHASH_ALG_ARGON2ID13',
            'CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE',
            'CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE',
            'CRYPTO_PWHASH_MEMLIMIT_MODERATE',
            'CRYPTO_PWHASH_OPSLIMIT_MODERATE',
            'CRYPTO_PWHASH_MEMLIMIT_SENSITIVE',
            'CRYPTO_PWHASH_OPSLIMIT_SENSITIVE',
            'CRYPTO_PWHASH_SCRYPTSALSA208SHA256_SALTBYTES',
            'CRYPTO_PWHASH_SCRYPTSALSA208SHA256_STRPREFIX',
            'CRYPTO_PWHASH_SCRYPTSALSA208SHA256_MEMLIMIT_INTERACTIVE',
            'CRYPTO_PWHASH_SCRYPTSALSA208SHA256_OPSLIMIT_INTERACTIVE',
            'CRYPTO_PWHASH_SCRYPTSALSA208SHA256_MEMLIMIT_SENSITIVE',
            'CRYPTO_PWHASH_SCRYPTSALSA208SHA256_OPSLIMIT_SENSITIVE',
            'CRYPTO_SCALARMULT_BYTES',
            'CRYPTO_SCALARMULT_SCALARBYTES',
            'CRYPTO_SHORTHASH_BYTES',
            'CRYPTO_SHORTHASH_KEYBYTES',
            'CRYPTO_SECRETBOX_KEYBYTES',
            'CRYPTO_SECRETBOX_MACBYTES',
            'CRYPTO_SECRETBOX_NONCEBYTES',
            'CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_ABYTES',
            'CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES',
            'CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_KEYBYTES',
            'CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_PUSH',
            'CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_PULL',
            'CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_REKEY',
            'CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL',
            'CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_MESSAGEBYTES_MAX',
            'CRYPTO_SIGN_BYTES',
            'CRYPTO_SIGN_SEEDBYTES',
            'CRYPTO_SIGN_PUBLICKEYBYTES',
            'CRYPTO_SIGN_SECRETKEYBYTES',
            'CRYPTO_SIGN_KEYPAIRBYTES',
            'CRYPTO_STREAM_KEYBYTES',
            'CRYPTO_STREAM_NONCEBYTES',
            'CRYPTO_STREAM_XCHACHA20_KEYBYTES',
            'CRYPTO_STREAM_XCHACHA20_NONCEBYTES',
            'LIBRARY_MAJOR_VERSION',
            'LIBRARY_MINOR_VERSION',
            'LIBRARY_VERSION_MAJOR',
            'LIBRARY_VERSION_MINOR',
            'VERSION_STRING'
        ] as $constant
    )
    {
        if(! defined("SODIUM_$constant") && defined("ParagonIE_Sodium_Compat::$constant"))
        {
            define("SODIUM_$constant", constant("ParagonIE_Sodium_Compat::$constant"));
        }
    }
    if(! is_callable('sodium_add'))
    {
        function sodium_add(&$string1, $string2)
        {
            ParagonIE_Sodium_Compat::add($string1, $string2);
        }
    }
    if(! is_callable('sodium_base642bin'))
    {
        function sodium_base642bin($string, $variant, $ignore = '')
        {
            return ParagonIE_Sodium_Compat::base642bin($string, $variant, $ignore);
        }
    }
    if(! is_callable('sodium_bin2base64'))
    {
        function sodium_bin2base64($string, $variant)
        {
            return ParagonIE_Sodium_Compat::bin2base64($string, $variant);
        }
    }
    if(! is_callable('sodium_bin2hex'))
    {
        function sodium_bin2hex($string)
        {
            return ParagonIE_Sodium_Compat::bin2hex($string);
        }
    }
    if(! is_callable('sodium_compare'))
    {
        function sodium_compare($string1, $string2)
        {
            return ParagonIE_Sodium_Compat::compare($string1, $string2);
        }
    }
    if(! is_callable('sodium_crypto_aead_aes256gcm_decrypt'))
    {
        function sodium_crypto_aead_aes256gcm_decrypt($ciphertext, $additional_data, $nonce, $key)
        {
            try
            {
                return ParagonIE_Sodium_Compat::crypto_aead_aes256gcm_decrypt($ciphertext, $additional_data, $nonce, $key);
            }
            catch(Error $ex)
            {
                return false;
            }
            catch(Exception $ex)
            {
                if(($ex instanceof SodiumException) && ($ex->getMessage() === 'AES-256-GCM is not available'))
                {
                    throw $ex;
                }

                return false;
            }
        }
    }
    if(! is_callable('sodium_crypto_aead_aes256gcm_encrypt'))
    {
        function sodium_crypto_aead_aes256gcm_encrypt($message, $additional_data, $nonce, $key)
        {
            return ParagonIE_Sodium_Compat::crypto_aead_aes256gcm_encrypt($message, $additional_data, $nonce, $key);
        }
    }
    if(! is_callable('sodium_crypto_aead_aes256gcm_is_available'))
    {
        function sodium_crypto_aead_aes256gcm_is_available()
        {
            return ParagonIE_Sodium_Compat::crypto_aead_aes256gcm_is_available();
        }
    }
    if(! is_callable('sodium_crypto_aead_chacha20poly1305_decrypt'))
    {
        function sodium_crypto_aead_chacha20poly1305_decrypt($ciphertext, $additional_data, $nonce, $key)
        {
            try
            {
                return ParagonIE_Sodium_Compat::crypto_aead_chacha20poly1305_decrypt($ciphertext, $additional_data, $nonce, $key);
            }
            catch(Error $ex)
            {
                return false;
            }
            catch(Exception $ex)
            {
                return false;
            }
        }
    }
    if(! is_callable('sodium_crypto_aead_chacha20poly1305_encrypt'))
    {
        function sodium_crypto_aead_chacha20poly1305_encrypt($message, $additional_data, $nonce, $key)
        {
            return ParagonIE_Sodium_Compat::crypto_aead_chacha20poly1305_encrypt($message, $additional_data, $nonce, $key);
        }
    }
    if(! is_callable('sodium_crypto_aead_chacha20poly1305_keygen'))
    {
        function sodium_crypto_aead_chacha20poly1305_keygen()
        {
            return ParagonIE_Sodium_Compat::crypto_aead_chacha20poly1305_keygen();
        }
    }
    if(! is_callable('sodium_crypto_aead_chacha20poly1305_ietf_decrypt'))
    {
        function sodium_crypto_aead_chacha20poly1305_ietf_decrypt($message, $additional_data, $nonce, $key)
        {
            try
            {
                return ParagonIE_Sodium_Compat::crypto_aead_chacha20poly1305_ietf_decrypt($message, $additional_data, $nonce, $key);
            }
            catch(Error $ex)
            {
                return false;
            }
            catch(Exception $ex)
            {
                return false;
            }
        }
    }
    if(! is_callable('sodium_crypto_aead_chacha20poly1305_ietf_encrypt'))
    {
        function sodium_crypto_aead_chacha20poly1305_ietf_encrypt($message, $additional_data, $nonce, $key)
        {
            return ParagonIE_Sodium_Compat::crypto_aead_chacha20poly1305_ietf_encrypt($message, $additional_data, $nonce, $key);
        }
    }
    if(! is_callable('sodium_crypto_aead_chacha20poly1305_ietf_keygen'))
    {
        function sodium_crypto_aead_chacha20poly1305_ietf_keygen()
        {
            return ParagonIE_Sodium_Compat::crypto_aead_chacha20poly1305_ietf_keygen();
        }
    }
    if(! is_callable('sodium_crypto_aead_xchacha20poly1305_ietf_decrypt'))
    {
        function sodium_crypto_aead_xchacha20poly1305_ietf_decrypt($ciphertext, $additional_data, $nonce, $key)
        {
            try
            {
                return ParagonIE_Sodium_Compat::crypto_aead_xchacha20poly1305_ietf_decrypt($ciphertext, $additional_data, $nonce, $key, true);
            }
            catch(Error $ex)
            {
                return false;
            }
            catch(Exception $ex)
            {
                return false;
            }
        }
    }
    if(! is_callable('sodium_crypto_aead_xchacha20poly1305_ietf_encrypt'))
    {
        function sodium_crypto_aead_xchacha20poly1305_ietf_encrypt(
            $message, $additional_data, $nonce, $key
        ) {
            return ParagonIE_Sodium_Compat::crypto_aead_xchacha20poly1305_ietf_encrypt($message, $additional_data, $nonce, $key, true);
        }
    }
    if(! is_callable('sodium_crypto_aead_xchacha20poly1305_ietf_keygen'))
    {
        function sodium_crypto_aead_xchacha20poly1305_ietf_keygen()
        {
            return ParagonIE_Sodium_Compat::crypto_aead_xchacha20poly1305_ietf_keygen();
        }
    }
    if(! is_callable('sodium_crypto_auth'))
    {
        function sodium_crypto_auth($message, $key)
        {
            return ParagonIE_Sodium_Compat::crypto_auth($message, $key);
        }
    }
    if(! is_callable('sodium_crypto_auth_keygen'))
    {
        function sodium_crypto_auth_keygen()
        {
            return ParagonIE_Sodium_Compat::crypto_auth_keygen();
        }
    }
    if(! is_callable('sodium_crypto_auth_verify'))
    {
        function sodium_crypto_auth_verify($mac, $message, $key)
        {
            return ParagonIE_Sodium_Compat::crypto_auth_verify($mac, $message, $key);
        }
    }
    if(! is_callable('sodium_crypto_box'))
    {
        function sodium_crypto_box($message, $nonce, $key_pair)
        {
            return ParagonIE_Sodium_Compat::crypto_box($message, $nonce, $key_pair);
        }
    }
    if(! is_callable('sodium_crypto_box_keypair'))
    {
        function sodium_crypto_box_keypair()
        {
            return ParagonIE_Sodium_Compat::crypto_box_keypair();
        }
    }
    if(! is_callable('sodium_crypto_box_keypair_from_secretkey_and_publickey'))
    {
        function sodium_crypto_box_keypair_from_secretkey_and_publickey($secret_key, $public_key)
        {
            return ParagonIE_Sodium_Compat::crypto_box_keypair_from_secretkey_and_publickey($secret_key, $public_key);
        }
    }
    if(! is_callable('sodium_crypto_box_open'))
    {
        function sodium_crypto_box_open($ciphertext, $nonce, $key_pair)
        {
            try
            {
                return ParagonIE_Sodium_Compat::crypto_box_open($ciphertext, $nonce, $key_pair);
            }
            catch(Error $ex)
            {
                return false;
            }
            catch(Exception $ex)
            {
                return false;
            }
        }
    }
    if(! is_callable('sodium_crypto_box_publickey'))
    {
        function sodium_crypto_box_publickey($key_pair)
        {
            return ParagonIE_Sodium_Compat::crypto_box_publickey($key_pair);
        }
    }
    if(! is_callable('sodium_crypto_box_publickey_from_secretkey'))
    {
        function sodium_crypto_box_publickey_from_secretkey($secret_key)
        {
            return ParagonIE_Sodium_Compat::crypto_box_publickey_from_secretkey($secret_key);
        }
    }
    if(! is_callable('sodium_crypto_box_seal'))
    {
        function sodium_crypto_box_seal($message, $public_key)
        {
            return ParagonIE_Sodium_Compat::crypto_box_seal($message, $public_key);
        }
    }
    if(! is_callable('sodium_crypto_box_seal_open'))
    {
        function sodium_crypto_box_seal_open($message, $key_pair)
        {
            try
            {
                return ParagonIE_Sodium_Compat::crypto_box_seal_open($message, $key_pair);
            }
            catch(SodiumException $ex)
            {
                if($ex->getMessage() === 'Argument 2 must be CRYPTO_BOX_KEYPAIRBYTES long.')
                {
                    throw $ex;
                }

                return false;
            }
        }
    }
    if(! is_callable('sodium_crypto_box_secretkey'))
    {
        function sodium_crypto_box_secretkey($key_pair)
        {
            return ParagonIE_Sodium_Compat::crypto_box_secretkey($key_pair);
        }
    }
    if(! is_callable('sodium_crypto_box_seed_keypair'))
    {
        function sodium_crypto_box_seed_keypair($seed)
        {
            return ParagonIE_Sodium_Compat::crypto_box_seed_keypair($seed);
        }
    }
    if(! is_callable('sodium_crypto_generichash'))
    {
        function sodium_crypto_generichash($message, $key = null, $length = 32)
        {
            return ParagonIE_Sodium_Compat::crypto_generichash($message, $key, $length);
        }
    }
    if(! is_callable('sodium_crypto_generichash_final'))
    {
        function sodium_crypto_generichash_final(&$state, $outputLength = 32)
        {
            return ParagonIE_Sodium_Compat::crypto_generichash_final($state, $outputLength);
        }
    }
    if(! is_callable('sodium_crypto_generichash_init'))
    {
        function sodium_crypto_generichash_init($key = null, $length = 32)
        {
            return ParagonIE_Sodium_Compat::crypto_generichash_init($key, $length);
        }
    }
    if(! is_callable('sodium_crypto_generichash_keygen'))
    {
        function sodium_crypto_generichash_keygen()
        {
            return ParagonIE_Sodium_Compat::crypto_generichash_keygen();
        }
    }
    if(! is_callable('sodium_crypto_generichash_update'))
    {
        function sodium_crypto_generichash_update(&$state, $message = '')
        {
            ParagonIE_Sodium_Compat::crypto_generichash_update($state, $message);
        }
    }
    if(! is_callable('sodium_crypto_kdf_keygen'))
    {
        function sodium_crypto_kdf_keygen()
        {
            return ParagonIE_Sodium_Compat::crypto_kdf_keygen();
        }
    }
    if(! is_callable('sodium_crypto_kdf_derive_from_key'))
    {
        function sodium_crypto_kdf_derive_from_key($subkey_length, $subkey_id, $context, $key)
        {
            return ParagonIE_Sodium_Compat::crypto_kdf_derive_from_key($subkey_length, $subkey_id, $context, $key);
        }
    }
    if(! is_callable('sodium_crypto_kx'))
    {
        function sodium_crypto_kx($my_secret, $their_public, $client_public, $server_public)
        {
            return ParagonIE_Sodium_Compat::crypto_kx($my_secret, $their_public, $client_public, $server_public);
        }
    }
    if(! is_callable('sodium_crypto_kx_seed_keypair'))
    {
        function sodium_crypto_kx_seed_keypair($seed)
        {
            return ParagonIE_Sodium_Compat::crypto_kx_seed_keypair($seed);
        }
    }
    if(! is_callable('sodium_crypto_kx_keypair'))
    {
        function sodium_crypto_kx_keypair()
        {
            return ParagonIE_Sodium_Compat::crypto_kx_keypair();
        }
    }
    if(! is_callable('sodium_crypto_kx_client_session_keys'))
    {
        function sodium_crypto_kx_client_session_keys($client_key_pair, $server_key)
        {
            return ParagonIE_Sodium_Compat::crypto_kx_client_session_keys($client_key_pair, $server_key);
        }
    }
    if(! is_callable('sodium_crypto_kx_server_session_keys'))
    {
        function sodium_crypto_kx_server_session_keys($server_key_pair, $client_key)
        {
            return ParagonIE_Sodium_Compat::crypto_kx_server_session_keys($server_key_pair, $client_key);
        }
    }
    if(! is_callable('sodium_crypto_kx_secretkey'))
    {
        function sodium_crypto_kx_secretkey($key_pair)
        {
            return ParagonIE_Sodium_Compat::crypto_kx_secretkey($key_pair);
        }
    }
    if(! is_callable('sodium_crypto_kx_publickey'))
    {
        function sodium_crypto_kx_publickey($key_pair)
        {
            return ParagonIE_Sodium_Compat::crypto_kx_publickey($key_pair);
        }
    }
    if(! is_callable('sodium_crypto_pwhash'))
    {
        function sodium_crypto_pwhash($length, $passwd, $salt, $opslimit, $memlimit, $algo = null)
        {
            return ParagonIE_Sodium_Compat::crypto_pwhash($length, $passwd, $salt, $opslimit, $memlimit, $algo);
        }
    }
    if(! is_callable('sodium_crypto_pwhash_str'))
    {
        function sodium_crypto_pwhash_str($passwd, $opslimit, $memlimit)
        {
            return ParagonIE_Sodium_Compat::crypto_pwhash_str($passwd, $opslimit, $memlimit);
        }
    }
    if(! is_callable('sodium_crypto_pwhash_str_needs_rehash'))
    {
        function sodium_crypto_pwhash_str_needs_rehash($hash, $opslimit, $memlimit)
        {
            return ParagonIE_Sodium_Compat::crypto_pwhash_str_needs_rehash($hash, $opslimit, $memlimit);
        }
    }
    if(! is_callable('sodium_crypto_pwhash_str_verify'))
    {
        function sodium_crypto_pwhash_str_verify($passwd, $hash)
        {
            return ParagonIE_Sodium_Compat::crypto_pwhash_str_verify($passwd, $hash);
        }
    }
    if(! is_callable('sodium_crypto_pwhash_scryptsalsa208sha256'))
    {
        function sodium_crypto_pwhash_scryptsalsa208sha256($length, $passwd, $salt, $opslimit, $memlimit)
        {
            return ParagonIE_Sodium_Compat::crypto_pwhash_scryptsalsa208sha256($length, $passwd, $salt, $opslimit, $memlimit);
        }
    }
    if(! is_callable('sodium_crypto_pwhash_scryptsalsa208sha256_str'))
    {
        function sodium_crypto_pwhash_scryptsalsa208sha256_str($passwd, $opslimit, $memlimit)
        {
            return ParagonIE_Sodium_Compat::crypto_pwhash_scryptsalsa208sha256_str($passwd, $opslimit, $memlimit);
        }
    }
    if(! is_callable('sodium_crypto_pwhash_scryptsalsa208sha256_str_verify'))
    {
        function sodium_crypto_pwhash_scryptsalsa208sha256_str_verify($passwd, $hash)
        {
            return ParagonIE_Sodium_Compat::crypto_pwhash_scryptsalsa208sha256_str_verify($passwd, $hash);
        }
    }
    if(! is_callable('sodium_crypto_scalarmult'))
    {
        function sodium_crypto_scalarmult($n, $p)
        {
            return ParagonIE_Sodium_Compat::crypto_scalarmult($n, $p);
        }
    }
    if(! is_callable('sodium_crypto_scalarmult_base'))
    {
        function sodium_crypto_scalarmult_base($n)
        {
            return ParagonIE_Sodium_Compat::crypto_scalarmult_base($n);
        }
    }
    if(! is_callable('sodium_crypto_secretbox'))
    {
        function sodium_crypto_secretbox($message, $nonce, $key)
        {
            return ParagonIE_Sodium_Compat::crypto_secretbox($message, $nonce, $key);
        }
    }
    if(! is_callable('sodium_crypto_secretbox_keygen'))
    {
        function sodium_crypto_secretbox_keygen()
        {
            return ParagonIE_Sodium_Compat::crypto_secretbox_keygen();
        }
    }
    if(! is_callable('sodium_crypto_secretbox_open'))
    {
        function sodium_crypto_secretbox_open($ciphertext, $nonce, $key)
        {
            try
            {
                return ParagonIE_Sodium_Compat::crypto_secretbox_open($ciphertext, $nonce, $key);
            }
            catch(Error $ex)
            {
                return false;
            }
            catch(Exception $ex)
            {
                return false;
            }
        }
    }
    if(! is_callable('sodium_crypto_secretstream_xchacha20poly1305_init_push'))
    {
        function sodium_crypto_secretstream_xchacha20poly1305_init_push($key)
        {
            return ParagonIE_Sodium_Compat::crypto_secretstream_xchacha20poly1305_init_push($key);
        }
    }
    if(! is_callable('sodium_crypto_secretstream_xchacha20poly1305_push'))
    {
        function sodium_crypto_secretstream_xchacha20poly1305_push(
            &$state, $message, $additional_data = '', $tag = 0
        ) {
            return ParagonIE_Sodium_Compat::crypto_secretstream_xchacha20poly1305_push($state, $message, $additional_data, $tag);
        }
    }
    if(! is_callable('sodium_crypto_secretstream_xchacha20poly1305_init_pull'))
    {
        function sodium_crypto_secretstream_xchacha20poly1305_init_pull($header, $key)
        {
            return ParagonIE_Sodium_Compat::crypto_secretstream_xchacha20poly1305_init_pull($header, $key);
        }
    }
    if(! is_callable('sodium_crypto_secretstream_xchacha20poly1305_pull'))
    {
        function sodium_crypto_secretstream_xchacha20poly1305_pull(&$state, $ciphertext, $additional_data = '')
        {
            return ParagonIE_Sodium_Compat::crypto_secretstream_xchacha20poly1305_pull($state, $ciphertext, $additional_data);
        }
    }
    if(! is_callable('sodium_crypto_secretstream_xchacha20poly1305_rekey'))
    {
        function sodium_crypto_secretstream_xchacha20poly1305_rekey(&$state)
        {
            ParagonIE_Sodium_Compat::crypto_secretstream_xchacha20poly1305_rekey($state);
        }
    }
    if(! is_callable('sodium_crypto_secretstream_xchacha20poly1305_keygen'))
    {
        function sodium_crypto_secretstream_xchacha20poly1305_keygen()
        {
            return ParagonIE_Sodium_Compat::crypto_secretstream_xchacha20poly1305_keygen();
        }
    }
    if(! is_callable('sodium_crypto_shorthash'))
    {
        function sodium_crypto_shorthash($message, $key = '')
        {
            return ParagonIE_Sodium_Compat::crypto_shorthash($message, $key);
        }
    }
    if(! is_callable('sodium_crypto_shorthash_keygen'))
    {
        function sodium_crypto_shorthash_keygen()
        {
            return ParagonIE_Sodium_Compat::crypto_shorthash_keygen();
        }
    }
    if(! is_callable('sodium_crypto_sign'))
    {
        function sodium_crypto_sign($message, $secret_key)
        {
            return ParagonIE_Sodium_Compat::crypto_sign($message, $secret_key);
        }
    }
    if(! is_callable('sodium_crypto_sign_detached'))
    {
        function sodium_crypto_sign_detached($message, $secret_key)
        {
            return ParagonIE_Sodium_Compat::crypto_sign_detached($message, $secret_key);
        }
    }
    if(! is_callable('sodium_crypto_sign_keypair_from_secretkey_and_publickey'))
    {
        function sodium_crypto_sign_keypair_from_secretkey_and_publickey($secret_key, $public_key)
        {
            return ParagonIE_Sodium_Compat::crypto_sign_keypair_from_secretkey_and_publickey($secret_key, $public_key);
        }
    }
    if(! is_callable('sodium_crypto_sign_keypair'))
    {
        function sodium_crypto_sign_keypair()
        {
            return ParagonIE_Sodium_Compat::crypto_sign_keypair();
        }
    }
    if(! is_callable('sodium_crypto_sign_open'))
    {
        function sodium_crypto_sign_open($signedMessage, $public_key)
        {
            try
            {
                return ParagonIE_Sodium_Compat::crypto_sign_open($signedMessage, $public_key);
            }
            catch(Error $ex)
            {
                return false;
            }
            catch(Exception $ex)
            {
                return false;
            }
        }
    }
    if(! is_callable('sodium_crypto_sign_publickey'))
    {
        function sodium_crypto_sign_publickey($key_pair)
        {
            return ParagonIE_Sodium_Compat::crypto_sign_publickey($key_pair);
        }
    }
    if(! is_callable('sodium_crypto_sign_publickey_from_secretkey'))
    {
        function sodium_crypto_sign_publickey_from_secretkey($secret_key)
        {
            return ParagonIE_Sodium_Compat::crypto_sign_publickey_from_secretkey($secret_key);
        }
    }
    if(! is_callable('sodium_crypto_sign_secretkey'))
    {
        function sodium_crypto_sign_secretkey($key_pair)
        {
            return ParagonIE_Sodium_Compat::crypto_sign_secretkey($key_pair);
        }
    }
    if(! is_callable('sodium_crypto_sign_seed_keypair'))
    {
        function sodium_crypto_sign_seed_keypair($seed)
        {
            return ParagonIE_Sodium_Compat::crypto_sign_seed_keypair($seed);
        }
    }
    if(! is_callable('sodium_crypto_sign_verify_detached'))
    {
        function sodium_crypto_sign_verify_detached($signature, $message, $public_key)
        {
            return ParagonIE_Sodium_Compat::crypto_sign_verify_detached($signature, $message, $public_key);
        }
    }
    if(! is_callable('sodium_crypto_sign_ed25519_pk_to_curve25519'))
    {
        function sodium_crypto_sign_ed25519_pk_to_curve25519($public_key)
        {
            return ParagonIE_Sodium_Compat::crypto_sign_ed25519_pk_to_curve25519($public_key);
        }
    }
    if(! is_callable('sodium_crypto_sign_ed25519_sk_to_curve25519'))
    {
        function sodium_crypto_sign_ed25519_sk_to_curve25519($secret_key)
        {
            return ParagonIE_Sodium_Compat::crypto_sign_ed25519_sk_to_curve25519($secret_key);
        }
    }
    if(! is_callable('sodium_crypto_stream'))
    {
        function sodium_crypto_stream($length, $nonce, $key)
        {
            return ParagonIE_Sodium_Compat::crypto_stream($length, $nonce, $key);
        }
    }
    if(! is_callable('sodium_crypto_stream_keygen'))
    {
        function sodium_crypto_stream_keygen()
        {
            return ParagonIE_Sodium_Compat::crypto_stream_keygen();
        }
    }
    if(! is_callable('sodium_crypto_stream_xor'))
    {
        function sodium_crypto_stream_xor($message, $nonce, $key)
        {
            return ParagonIE_Sodium_Compat::crypto_stream_xor($message, $nonce, $key);
        }
    }
    require_once dirname(__FILE__).'/stream-xchacha20.php';
    if(! is_callable('sodium_hex2bin'))
    {
        function sodium_hex2bin($string, $ignore = '')
        {
            return ParagonIE_Sodium_Compat::hex2bin($string, $ignore);
        }
    }
    if(! is_callable('sodium_increment'))
    {
        function sodium_increment(&$string)
        {
            ParagonIE_Sodium_Compat::increment($string);
        }
    }
    if(! is_callable('sodium_library_version_major'))
    {
        function sodium_library_version_major()
        {
            return ParagonIE_Sodium_Compat::library_version_major();
        }
    }
    if(! is_callable('sodium_library_version_minor'))
    {
        function sodium_library_version_minor()
        {
            return ParagonIE_Sodium_Compat::library_version_minor();
        }
    }
    if(! is_callable('sodium_version_string'))
    {
        function sodium_version_string()
        {
            return ParagonIE_Sodium_Compat::version_string();
        }
    }
    if(! is_callable('sodium_memcmp'))
    {
        function sodium_memcmp($string1, $string2)
        {
            return ParagonIE_Sodium_Compat::memcmp($string1, $string2);
        }
    }
    if(! is_callable('sodium_memzero'))
    {
        function sodium_memzero(&$string)
        {
            ParagonIE_Sodium_Compat::memzero($string);
        }
    }
    if(! is_callable('sodium_pad'))
    {
        function sodium_pad($unpadded, $block_size)
        {
            return ParagonIE_Sodium_Compat::pad($unpadded, $block_size, true);
        }
    }
    if(! is_callable('sodium_unpad'))
    {
        function sodium_unpad($padded, $block_size)
        {
            return ParagonIE_Sodium_Compat::unpad($padded, $block_size, true);
        }
    }
    if(! is_callable('sodium_randombytes_buf'))
    {
        function sodium_randombytes_buf($amount)
        {
            return ParagonIE_Sodium_Compat::randombytes_buf($amount);
        }
    }

    if(! is_callable('sodium_randombytes_uniform'))
    {
        function sodium_randombytes_uniform($upperLimit)
        {
            return ParagonIE_Sodium_Compat::randombytes_uniform($upperLimit);
        }
    }

    if(! is_callable('sodium_randombytes_random16'))
    {
        function sodium_randombytes_random16()
        {
            return ParagonIE_Sodium_Compat::randombytes_random16();
        }
    }
