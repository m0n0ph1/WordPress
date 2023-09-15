<?php

    if(class_exists('ParagonIE_Sodium_Crypto', false))
    {
        return;
    }

    abstract class Crypto
    {
        public const aead_chacha20poly1305_KEYBYTES = 32;

        public const aead_chacha20poly1305_NSECBYTES = 0;

        public const aead_chacha20poly1305_NPUBBYTES = 8;

        public const aead_chacha20poly1305_ABYTES = 16;

        public const aead_chacha20poly1305_IETF_KEYBYTES = 32;

        public const aead_chacha20poly1305_IETF_NSECBYTES = 0;

        public const aead_chacha20poly1305_IETF_NPUBBYTES = 12;

        public const aead_chacha20poly1305_IETF_ABYTES = 16;

        public const aead_xchacha20poly1305_IETF_KEYBYTES = 32;

        public const aead_xchacha20poly1305_IETF_NSECBYTES = 0;

        public const aead_xchacha20poly1305_IETF_NPUBBYTES = 24;

        public const aead_xchacha20poly1305_IETF_ABYTES = 16;

        public const box_curve25519xsalsa20poly1305_SEEDBYTES = 32;

        public const box_curve25519xsalsa20poly1305_PUBLICKEYBYTES = 32;

        public const box_curve25519xsalsa20poly1305_SECRETKEYBYTES = 32;

        public const box_curve25519xsalsa20poly1305_BEFORENMBYTES = 32;

        public const box_curve25519xsalsa20poly1305_NONCEBYTES = 24;

        public const box_curve25519xsalsa20poly1305_MACBYTES = 16;

        public const box_curve25519xsalsa20poly1305_BOXZEROBYTES = 16;

        public const box_curve25519xsalsa20poly1305_ZEROBYTES = 32;

        public const onetimeauth_poly1305_BYTES = 16;

        public const onetimeauth_poly1305_KEYBYTES = 32;

        public const secretbox_xsalsa20poly1305_KEYBYTES = 32;

        public const secretbox_xsalsa20poly1305_NONCEBYTES = 24;

        public const secretbox_xsalsa20poly1305_MACBYTES = 16;

        public const secretbox_xsalsa20poly1305_BOXZEROBYTES = 16;

        public const secretbox_xsalsa20poly1305_ZEROBYTES = 32;

        public const secretbox_xchacha20poly1305_KEYBYTES = 32;

        public const secretbox_xchacha20poly1305_NONCEBYTES = 24;

        public const secretbox_xchacha20poly1305_MACBYTES = 16;

        public const secretbox_xchacha20poly1305_BOXZEROBYTES = 16;

        public const secretbox_xchacha20poly1305_ZEROBYTES = 32;

        public const stream_salsa20_KEYBYTES = 32;

        public static function aead_chacha20poly1305_decrypt(
            $message = '', $ad = '', $nonce = '', $key = ''
        ) {
            $len = ParagonIE_Sodium_Core_Util::strlen($message);

            $clen = $len - self::aead_chacha20poly1305_ABYTES;

            $adlen = ParagonIE_Sodium_Core_Util::strlen($ad);

            $mac = ParagonIE_Sodium_Core_Util::substr($message, $clen, self::aead_chacha20poly1305_ABYTES);

            $ciphertext = ParagonIE_Sodium_Core_Util::substr($message, 0, $clen);

            $block0 = ParagonIE_Sodium_Core_ChaCha20::stream(32, $nonce, $key);

            /* Recalculate the Poly1305 authentication tag (MAC): */
            $state = new ParagonIE_Sodium_Core_Poly1305_State($block0);
            try
            {
                ParagonIE_Sodium_Compat::memzero($block0);
            }
            catch(SodiumException $ex)
            {
                $block0 = null;
            }
            $state->update($ad);
            $state->update(ParagonIE_Sodium_Core_Util::store64_le($adlen));
            $state->update($ciphertext);
            $state->update(ParagonIE_Sodium_Core_Util::store64_le($clen));
            $computed_mac = $state->finish();

            /* Compare the given MAC with the recalculated MAC: */
            if(! ParagonIE_Sodium_Core_Util::verify_16($computed_mac, $mac))
            {
                throw new SodiumException('Invalid MAC');
            }

            // Here, we know that the MAC is valid, so we decrypt and return the plaintext
            return ParagonIE_Sodium_Core_ChaCha20::streamXorIc($ciphertext, $nonce, $key, ParagonIE_Sodium_Core_Util::store64_le(1));
        }

        public static function aead_chacha20poly1305_encrypt(
            $message = '', $ad = '', $nonce = '', $key = ''
        ) {
            $len = ParagonIE_Sodium_Core_Util::strlen($message);

            $adlen = ParagonIE_Sodium_Core_Util::strlen($ad);

            $block0 = ParagonIE_Sodium_Core_ChaCha20::stream(32, $nonce, $key);
            $state = new ParagonIE_Sodium_Core_Poly1305_State($block0);
            try
            {
                ParagonIE_Sodium_Compat::memzero($block0);
            }
            catch(SodiumException $ex)
            {
                $block0 = null;
            }

            $ciphertext = ParagonIE_Sodium_Core_ChaCha20::streamXorIc($message, $nonce, $key, ParagonIE_Sodium_Core_Util::store64_le(1));

            $state->update($ad);
            $state->update(ParagonIE_Sodium_Core_Util::store64_le($adlen));
            $state->update($ciphertext);
            $state->update(ParagonIE_Sodium_Core_Util::store64_le($len));

            return $ciphertext.$state->finish();
        }

        public static function aead_xchacha20poly1305_ietf_decrypt(
            $message = '', $ad = '', $nonce = '', $key = ''
        ) {
            $subkey = ParagonIE_Sodium_Core_HChaCha20::hChaCha20(ParagonIE_Sodium_Core_Util::substr($nonce, 0, 16), $key);
            $nonceLast = "\x00\x00\x00\x00".ParagonIE_Sodium_Core_Util::substr($nonce, 16, 8);

            return self::aead_chacha20poly1305_ietf_decrypt($message, $ad, $nonceLast, $subkey);
        }

        public static function aead_chacha20poly1305_ietf_decrypt(
            $message = '', $ad = '', $nonce = '', $key = ''
        ) {
            $adlen = ParagonIE_Sodium_Core_Util::strlen($ad);

            $len = ParagonIE_Sodium_Core_Util::strlen($message);

            $clen = $len - self::aead_chacha20poly1305_IETF_ABYTES;

            $block0 = ParagonIE_Sodium_Core_ChaCha20::ietfStream(32, $nonce, $key);

            $mac = ParagonIE_Sodium_Core_Util::substr($message, $len - self::aead_chacha20poly1305_IETF_ABYTES, self::aead_chacha20poly1305_IETF_ABYTES);

            $ciphertext = ParagonIE_Sodium_Core_Util::substr($message, 0, $len - self::aead_chacha20poly1305_IETF_ABYTES);

            /* Recalculate the Poly1305 authentication tag (MAC): */
            $state = new ParagonIE_Sodium_Core_Poly1305_State($block0);
            try
            {
                ParagonIE_Sodium_Compat::memzero($block0);
            }
            catch(SodiumException $ex)
            {
                $block0 = null;
            }
            $state->update($ad);
            $state->update(str_repeat("\x00", ((0x10 - $adlen) & 0xf)));
            $state->update($ciphertext);
            $state->update(str_repeat("\x00", (0x10 - $clen) & 0xf));
            $state->update(ParagonIE_Sodium_Core_Util::store64_le($adlen));
            $state->update(ParagonIE_Sodium_Core_Util::store64_le($clen));
            $computed_mac = $state->finish();

            /* Compare the given MAC with the recalculated MAC: */
            if(! ParagonIE_Sodium_Core_Util::verify_16($computed_mac, $mac))
            {
                throw new SodiumException('Invalid MAC');
            }

            // Here, we know that the MAC is valid, so we decrypt and return the plaintext
            return ParagonIE_Sodium_Core_ChaCha20::ietfStreamXorIc($ciphertext, $nonce, $key, ParagonIE_Sodium_Core_Util::store64_le(1));
        }

        public static function aead_xchacha20poly1305_ietf_encrypt(
            $message = '', $ad = '', $nonce = '', $key = ''
        ) {
            $subkey = ParagonIE_Sodium_Core_HChaCha20::hChaCha20(ParagonIE_Sodium_Core_Util::substr($nonce, 0, 16), $key);
            $nonceLast = "\x00\x00\x00\x00".ParagonIE_Sodium_Core_Util::substr($nonce, 16, 8);

            return self::aead_chacha20poly1305_ietf_encrypt($message, $ad, $nonceLast, $subkey);
        }

        public static function aead_chacha20poly1305_ietf_encrypt(
            $message = '', $ad = '', $nonce = '', $key = ''
        ) {
            $len = ParagonIE_Sodium_Core_Util::strlen($message);

            $adlen = ParagonIE_Sodium_Core_Util::strlen($ad);

            $block0 = ParagonIE_Sodium_Core_ChaCha20::ietfStream(32, $nonce, $key);
            $state = new ParagonIE_Sodium_Core_Poly1305_State($block0);
            try
            {
                ParagonIE_Sodium_Compat::memzero($block0);
            }
            catch(SodiumException $ex)
            {
                $block0 = null;
            }

            $ciphertext = ParagonIE_Sodium_Core_ChaCha20::ietfStreamXorIc($message, $nonce, $key, ParagonIE_Sodium_Core_Util::store64_le(1));

            $state->update($ad);
            $state->update(str_repeat("\x00", ((0x10 - $adlen) & 0xf)));
            $state->update($ciphertext);
            $state->update(str_repeat("\x00", ((0x10 - $len) & 0xf)));
            $state->update(ParagonIE_Sodium_Core_Util::store64_le($adlen));
            $state->update(ParagonIE_Sodium_Core_Util::store64_le($len));

            return $ciphertext.$state->finish();
        }

        public static function auth_verify($mac, $message, $key)
        {
            return ParagonIE_Sodium_Core_Util::hashEquals($mac, self::auth($message, $key));
        }

        public static function auth($message, $key)
        {
            return ParagonIE_Sodium_Core_Util::substr(hash_hmac('sha512', $message, $key, true), 0, 32);
        }

        public static function box_seal($message, $publicKey)
        {
            $ephemeralKeypair = self::box_keypair();

            $ephemeralSK = self::box_secretkey($ephemeralKeypair);

            $ephemeralPK = self::box_publickey($ephemeralKeypair);

            $nonce = self::generichash($ephemeralPK.$publicKey, '', 24);

            $keypair = self::box_keypair_from_secretkey_and_publickey($ephemeralSK, $publicKey);

            $ciphertext = self::box($message, $nonce, $keypair);
            try
            {
                ParagonIE_Sodium_Compat::memzero($ephemeralKeypair);
                ParagonIE_Sodium_Compat::memzero($ephemeralSK);
                ParagonIE_Sodium_Compat::memzero($nonce);
            }
            catch(SodiumException $ex)
            {
                $ephemeralKeypair = null;
                $ephemeralSK = null;
                $nonce = null;
            }

            return $ephemeralPK.$ciphertext;
        }

        public static function box_keypair()
        {
            $sKey = random_bytes(32);
            $pKey = self::scalarmult_base($sKey);

            return $sKey.$pKey;
        }

        public static function scalarmult_base($secret)
        {
            $q = ParagonIE_Sodium_Core_X25519::crypto_scalarmult_curve25519_ref10_base($secret);
            self::scalarmult_throw_if_zero($q);

            return $q;
        }

        protected static function scalarmult_throw_if_zero($q)
        {
            $d = 0;
            for($i = 0; $i < self::box_curve25519xsalsa20poly1305_SECRETKEYBYTES; ++$i)
            {
                $d |= ParagonIE_Sodium_Core_Util::chrToInt($q[$i]);
            }

            /* branch-free variant of === 0 */
            if(-(1 & (($d - 1) >> 8)))
            {
                throw new SodiumException('Zero public key is not allowed');
            }
        }

        public static function box_secretkey($keypair)
        {
            if(ParagonIE_Sodium_Core_Util::strlen($keypair) !== 64)
            {
                throw new RangeException('Must be ParagonIE_Sodium_Compat::CRYPTO_BOX_KEYPAIRBYTES bytes long.');
            }

            return ParagonIE_Sodium_Core_Util::substr($keypair, 0, 32);
        }

        public static function box_publickey($keypair)
        {
            if(ParagonIE_Sodium_Core_Util::strlen($keypair) !== ParagonIE_Sodium_Compat::CRYPTO_BOX_KEYPAIRBYTES)
            {
                throw new RangeException('Must be ParagonIE_Sodium_Compat::CRYPTO_BOX_KEYPAIRBYTES bytes long.');
            }

            return ParagonIE_Sodium_Core_Util::substr($keypair, 32, 32);
        }

        public static function generichash($message, $key = '', $outlen = 32)
        {
            // This ensures that ParagonIE_Sodium_Core_BLAKE2b::$iv is initialized
            ParagonIE_Sodium_Core_BLAKE2b::pseudoConstructor();

            $k = null;
            if(! empty($key))
            {
                $k = ParagonIE_Sodium_Core_BLAKE2b::stringToSplFixedArray($key);
                if($k->count() > ParagonIE_Sodium_Core_BLAKE2b::KEYBYTES)
                {
                    throw new RangeException('Invalid key size');
                }
            }

            $in = ParagonIE_Sodium_Core_BLAKE2b::stringToSplFixedArray($message);

            $ctx = ParagonIE_Sodium_Core_BLAKE2b::init($k, $outlen);
            ParagonIE_Sodium_Core_BLAKE2b::update($ctx, $in, $in->count());

            $out = new SplFixedArray($outlen);
            $out = ParagonIE_Sodium_Core_BLAKE2b::finish($ctx, $out);

            $outArray = $out->toArray();

            return ParagonIE_Sodium_Core_Util::intArrayToString($outArray);
        }

        public static function box_keypair_from_secretkey_and_publickey($sKey, $pKey)
        {
            return ParagonIE_Sodium_Core_Util::substr($sKey, 0, 32).ParagonIE_Sodium_Core_Util::substr($pKey, 0, 32);
        }

        public static function box($plaintext, $nonce, $keypair)
        {
            $c = self::secretbox($plaintext, $nonce, self::box_beforenm(self::box_secretkey($keypair), self::box_publickey($keypair)));

            return $c;
        }

        public static function secretbox($plaintext, $nonce, $key)
        {
            $subkey = ParagonIE_Sodium_Core_HSalsa20::hsalsa20($nonce, $key);

            $block0 = str_repeat("\x00", 32);

            $mlen = ParagonIE_Sodium_Core_Util::strlen($plaintext);
            $mlen0 = $mlen;
            if($mlen0 > 64 - self::secretbox_xsalsa20poly1305_ZEROBYTES)
            {
                $mlen0 = 64 - self::secretbox_xsalsa20poly1305_ZEROBYTES;
            }
            $block0 .= ParagonIE_Sodium_Core_Util::substr($plaintext, 0, $mlen0);

            $block0 = ParagonIE_Sodium_Core_Salsa20::salsa20_xor($block0, ParagonIE_Sodium_Core_Util::substr($nonce, 16, 8), $subkey);

            $c = ParagonIE_Sodium_Core_Util::substr($block0, self::secretbox_xsalsa20poly1305_ZEROBYTES);
            if($mlen > $mlen0)
            {
                $c .= ParagonIE_Sodium_Core_Salsa20::salsa20_xor_ic(ParagonIE_Sodium_Core_Util::substr($plaintext, self::secretbox_xsalsa20poly1305_ZEROBYTES), ParagonIE_Sodium_Core_Util::substr($nonce, 16, 8), 1, $subkey);
            }
            $state = new ParagonIE_Sodium_Core_Poly1305_State(ParagonIE_Sodium_Core_Util::substr($block0, 0, self::onetimeauth_poly1305_KEYBYTES));
            try
            {
                ParagonIE_Sodium_Compat::memzero($block0);
                ParagonIE_Sodium_Compat::memzero($subkey);
            }
            catch(SodiumException $ex)
            {
                $block0 = null;
                $subkey = null;
            }

            $state->update($c);

            $c = $state->finish().$c;
            unset($state);

            return $c;
        }

        public static function box_beforenm($sk, $pk)
        {
            return ParagonIE_Sodium_Core_HSalsa20::hsalsa20(str_repeat("\x00", 16), self::scalarmult($sk, $pk));
        }

        public static function scalarmult($sKey, $pKey)
        {
            $q = ParagonIE_Sodium_Core_X25519::crypto_scalarmult_curve25519_ref10($sKey, $pKey);
            self::scalarmult_throw_if_zero($q);

            return $q;
        }

        public static function box_seal_open($message, $keypair)
        {
            $ephemeralPK = ParagonIE_Sodium_Core_Util::substr($message, 0, 32);

            $ciphertext = ParagonIE_Sodium_Core_Util::substr($message, 32);

            $secretKey = self::box_secretkey($keypair);

            $publicKey = self::box_publickey($keypair);

            $nonce = self::generichash($ephemeralPK.$publicKey, '', 24);

            $keypair = self::box_keypair_from_secretkey_and_publickey($secretKey, $ephemeralPK);

            $m = self::box_open($ciphertext, $nonce, $keypair);
            try
            {
                ParagonIE_Sodium_Compat::memzero($secretKey);
                ParagonIE_Sodium_Compat::memzero($ephemeralPK);
                ParagonIE_Sodium_Compat::memzero($nonce);
            }
            catch(SodiumException $ex)
            {
                $secretKey = null;
                $ephemeralPK = null;
                $nonce = null;
            }

            return $m;
        }

        public static function box_open($ciphertext, $nonce, $keypair)
        {
            return self::secretbox_open($ciphertext, $nonce, self::box_beforenm(self::box_secretkey($keypair), self::box_publickey($keypair)));
        }

        public static function secretbox_open($ciphertext, $nonce, $key)
        {
            $mac = ParagonIE_Sodium_Core_Util::substr($ciphertext, 0, self::secretbox_xsalsa20poly1305_MACBYTES);

            $c = ParagonIE_Sodium_Core_Util::substr($ciphertext, self::secretbox_xsalsa20poly1305_MACBYTES);

            $clen = ParagonIE_Sodium_Core_Util::strlen($c);

            $subkey = ParagonIE_Sodium_Core_HSalsa20::hsalsa20($nonce, $key);

            $block0 = ParagonIE_Sodium_Core_Salsa20::salsa20(64, ParagonIE_Sodium_Core_Util::substr($nonce, 16, 8), $subkey);
            $verified = ParagonIE_Sodium_Core_Poly1305::onetimeauth_verify($mac, $c, ParagonIE_Sodium_Core_Util::substr($block0, 0, 32));
            if(! $verified)
            {
                try
                {
                    ParagonIE_Sodium_Compat::memzero($subkey);
                }
                catch(SodiumException $ex)
                {
                    $subkey = null;
                }
                throw new SodiumException('Invalid MAC');
            }

            $m = ParagonIE_Sodium_Core_Util::xorStrings(ParagonIE_Sodium_Core_Util::substr($block0, self::secretbox_xsalsa20poly1305_ZEROBYTES), ParagonIE_Sodium_Core_Util::substr($c, 0, self::secretbox_xsalsa20poly1305_ZEROBYTES));
            if($clen > self::secretbox_xsalsa20poly1305_ZEROBYTES)
            {
                // We had more than 1 block, so let's continue to decrypt the rest.
                $m .= ParagonIE_Sodium_Core_Salsa20::salsa20_xor_ic(ParagonIE_Sodium_Core_Util::substr($c, self::secretbox_xsalsa20poly1305_ZEROBYTES), ParagonIE_Sodium_Core_Util::substr($nonce, 16, 8), 1, (string) $subkey);
            }

            return $m;
        }

        public static function box_seed_keypair($seed)
        {
            $sKey = ParagonIE_Sodium_Core_Util::substr(hash('sha512', $seed, true), 0, 32);
            $pKey = self::scalarmult_base($sKey);

            return $sKey.$pKey;
        }

        public static function box_publickey_from_secretkey($sKey)
        {
            if(ParagonIE_Sodium_Core_Util::strlen($sKey) !== ParagonIE_Sodium_Compat::CRYPTO_BOX_SECRETKEYBYTES)
            {
                throw new RangeException('Must be ParagonIE_Sodium_Compat::CRYPTO_BOX_SECRETKEYBYTES bytes long.');
            }

            return self::scalarmult_base($sKey);
        }

        public static function generichash_final($ctx, $outlen = 32)
        {
            if(! is_string($ctx))
            {
                throw new TypeError('Context must be a string');
            }
            $out = new SplFixedArray($outlen);

            $context = ParagonIE_Sodium_Core_BLAKE2b::stringToContext($ctx);

            $out = ParagonIE_Sodium_Core_BLAKE2b::finish($context, $out);

            $outArray = $out->toArray();

            return ParagonIE_Sodium_Core_Util::intArrayToString($outArray);
        }

        public static function generichash_init($key = '', $outputLength = 32)
        {
            // This ensures that ParagonIE_Sodium_Core_BLAKE2b::$iv is initialized
            ParagonIE_Sodium_Core_BLAKE2b::pseudoConstructor();

            $k = null;
            if(! empty($key))
            {
                $k = ParagonIE_Sodium_Core_BLAKE2b::stringToSplFixedArray($key);
                if($k->count() > ParagonIE_Sodium_Core_BLAKE2b::KEYBYTES)
                {
                    throw new RangeException('Invalid key size');
                }
            }

            $ctx = ParagonIE_Sodium_Core_BLAKE2b::init($k, $outputLength);

            return ParagonIE_Sodium_Core_BLAKE2b::contextToString($ctx);
        }

        public static function generichash_init_salt_personal(
            $key = '', $outputLength = 32, $salt = '', $personal = ''
        ) {
            // This ensures that ParagonIE_Sodium_Core_BLAKE2b::$iv is initialized
            ParagonIE_Sodium_Core_BLAKE2b::pseudoConstructor();

            $k = null;
            if(! empty($key))
            {
                $k = ParagonIE_Sodium_Core_BLAKE2b::stringToSplFixedArray($key);
                if($k->count() > ParagonIE_Sodium_Core_BLAKE2b::KEYBYTES)
                {
                    throw new RangeException('Invalid key size');
                }
            }
            if(! empty($salt))
            {
                $s = ParagonIE_Sodium_Core_BLAKE2b::stringToSplFixedArray($salt);
            }
            else
            {
                $s = null;
            }
            if(! empty($salt))
            {
                $p = ParagonIE_Sodium_Core_BLAKE2b::stringToSplFixedArray($personal);
            }
            else
            {
                $p = null;
            }

            $ctx = ParagonIE_Sodium_Core_BLAKE2b::init($k, $outputLength, $s, $p);

            return ParagonIE_Sodium_Core_BLAKE2b::contextToString($ctx);
        }

        public static function generichash_update($ctx, $message)
        {
            // This ensures that ParagonIE_Sodium_Core_BLAKE2b::$iv is initialized
            ParagonIE_Sodium_Core_BLAKE2b::pseudoConstructor();

            $context = ParagonIE_Sodium_Core_BLAKE2b::stringToContext($ctx);

            $in = ParagonIE_Sodium_Core_BLAKE2b::stringToSplFixedArray($message);

            ParagonIE_Sodium_Core_BLAKE2b::update($context, $in, $in->count());

            return ParagonIE_Sodium_Core_BLAKE2b::contextToString($context);
        }

        public static function keyExchange($my_sk, $their_pk, $client_pk, $server_pk)
        {
            return ParagonIE_Sodium_Compat::crypto_generichash(ParagonIE_Sodium_Compat::crypto_scalarmult($my_sk, $their_pk).$client_pk.$server_pk);
        }

        public static function secretbox_xchacha20poly1305($plaintext, $nonce, $key)
        {
            $subkey = ParagonIE_Sodium_Core_HChaCha20::hChaCha20(ParagonIE_Sodium_Core_Util::substr($nonce, 0, 16), $key);
            $nonceLast = ParagonIE_Sodium_Core_Util::substr($nonce, 16, 8);

            $block0 = str_repeat("\x00", 32);

            $mlen = ParagonIE_Sodium_Core_Util::strlen($plaintext);
            $mlen0 = $mlen;
            if($mlen0 > 64 - self::secretbox_xchacha20poly1305_ZEROBYTES)
            {
                $mlen0 = 64 - self::secretbox_xchacha20poly1305_ZEROBYTES;
            }
            $block0 .= ParagonIE_Sodium_Core_Util::substr($plaintext, 0, $mlen0);

            $block0 = ParagonIE_Sodium_Core_ChaCha20::streamXorIc($block0, $nonceLast, $subkey);

            $c = ParagonIE_Sodium_Core_Util::substr($block0, self::secretbox_xchacha20poly1305_ZEROBYTES);
            if($mlen > $mlen0)
            {
                $c .= ParagonIE_Sodium_Core_ChaCha20::streamXorIc(ParagonIE_Sodium_Core_Util::substr($plaintext, self::secretbox_xchacha20poly1305_ZEROBYTES), $nonceLast, $subkey, ParagonIE_Sodium_Core_Util::store64_le(1));
            }
            $state = new ParagonIE_Sodium_Core_Poly1305_State(ParagonIE_Sodium_Core_Util::substr($block0, 0, self::onetimeauth_poly1305_KEYBYTES));
            try
            {
                ParagonIE_Sodium_Compat::memzero($block0);
                ParagonIE_Sodium_Compat::memzero($subkey);
            }
            catch(SodiumException $ex)
            {
                $block0 = null;
                $subkey = null;
            }

            $state->update($c);

            $c = $state->finish().$c;
            unset($state);

            return $c;
        }

        public static function secretbox_xchacha20poly1305_open($ciphertext, $nonce, $key)
        {
            $mac = ParagonIE_Sodium_Core_Util::substr($ciphertext, 0, self::secretbox_xchacha20poly1305_MACBYTES);

            $c = ParagonIE_Sodium_Core_Util::substr($ciphertext, self::secretbox_xchacha20poly1305_MACBYTES);

            $clen = ParagonIE_Sodium_Core_Util::strlen($c);

            $subkey = ParagonIE_Sodium_Core_HChaCha20::hchacha20($nonce, $key);

            $block0 = ParagonIE_Sodium_Core_ChaCha20::stream(64, ParagonIE_Sodium_Core_Util::substr($nonce, 16, 8), $subkey);
            $verified = ParagonIE_Sodium_Core_Poly1305::onetimeauth_verify($mac, $c, ParagonIE_Sodium_Core_Util::substr($block0, 0, 32));

            if(! $verified)
            {
                try
                {
                    ParagonIE_Sodium_Compat::memzero($subkey);
                }
                catch(SodiumException $ex)
                {
                    $subkey = null;
                }
                throw new SodiumException('Invalid MAC');
            }

            $m = ParagonIE_Sodium_Core_Util::xorStrings(ParagonIE_Sodium_Core_Util::substr($block0, self::secretbox_xchacha20poly1305_ZEROBYTES), ParagonIE_Sodium_Core_Util::substr($c, 0, self::secretbox_xchacha20poly1305_ZEROBYTES));

            if($clen > self::secretbox_xchacha20poly1305_ZEROBYTES)
            {
                // We had more than 1 block, so let's continue to decrypt the rest.
                $m .= ParagonIE_Sodium_Core_ChaCha20::streamXorIc(ParagonIE_Sodium_Core_Util::substr($c, self::secretbox_xchacha20poly1305_ZEROBYTES), ParagonIE_Sodium_Core_Util::substr($nonce, 16, 8), (string) $subkey, ParagonIE_Sodium_Core_Util::store64_le(1));
            }

            return $m;
        }

        public static function secretstream_xchacha20poly1305_init_push($key)
        {
            # randombytes_buf(out, crypto_secretstream_xchacha20poly1305_HEADERBYTES);
            $out = random_bytes(24);

            # crypto_core_hchacha20(state->k, out, k, NULL);
            $subkey = ParagonIE_Sodium_Core_HChaCha20::hChaCha20($out, $key);
            $state = new ParagonIE_Sodium_Core_SecretStream_State($subkey, ParagonIE_Sodium_Core_Util::substr($out, 16, 8).str_repeat("\0", 4));

            # _crypto_secretstream_xchacha20poly1305_counter_reset(state);
            $state->counterReset();

            # memcpy(STATE_INONCE(state), out + crypto_core_hchacha20_INPUTBYTES,
            #        crypto_secretstream_xchacha20poly1305_INONCEBYTES);
            # memset(state->_pad, 0, sizeof state->_pad);
            return [
                $state->toString(),
                $out
            ];
        }

        public static function secretstream_xchacha20poly1305_init_pull($key, $header)
        {
            # crypto_core_hchacha20(state->k, in, k, NULL);
            $subkey = ParagonIE_Sodium_Core_HChaCha20::hChaCha20(ParagonIE_Sodium_Core_Util::substr($header, 0, 16), $key);
            $state = new ParagonIE_Sodium_Core_SecretStream_State($subkey, ParagonIE_Sodium_Core_Util::substr($header, 16));
            $state->counterReset();
            # memcpy(STATE_INONCE(state), in + crypto_core_hchacha20_INPUTBYTES,
            #     crypto_secretstream_xchacha20poly1305_INONCEBYTES);
            # memset(state->_pad, 0, sizeof state->_pad);
            # return 0;
            return $state->toString();
        }

        public static function secretstream_xchacha20poly1305_push(&$state, $msg, $aad = '', $tag = 0)
        {
            $st = ParagonIE_Sodium_Core_SecretStream_State::fromString($state);
            # crypto_onetimeauth_poly1305_state poly1305_state;
            # unsigned char                     block[64U];
            # unsigned char                     slen[8U];
            # unsigned char                    *c;
            # unsigned char                    *mac;

            $msglen = ParagonIE_Sodium_Core_Util::strlen($msg);
            $aadlen = ParagonIE_Sodium_Core_Util::strlen($aad);

            if((($msglen + 63) >> 6) > 0xfffffffe)
            {
                throw new SodiumException('message cannot be larger than SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_MESSAGEBYTES_MAX bytes');
            }

            # if (outlen_p != NULL) {
            #     *outlen_p = 0U;
            # }
            # if (mlen > crypto_secretstream_xchacha20poly1305_MESSAGEBYTES_MAX) {
            #     sodium_misuse();
            # }

            # crypto_stream_chacha20_ietf(block, sizeof block, state->nonce, state->k);
            # crypto_onetimeauth_poly1305_init(&poly1305_state, block);
            # sodium_memzero(block, sizeof block);
            $auth = new ParagonIE_Sodium_Core_Poly1305_State(ParagonIE_Sodium_Core_ChaCha20::ietfStream(32, $st->getCombinedNonce(), $st->getKey()));

            # crypto_onetimeauth_poly1305_update(&poly1305_state, ad, adlen);
            $auth->update($aad);

            # crypto_onetimeauth_poly1305_update(&poly1305_state, _pad0,
            #     (0x10 - adlen) & 0xf);
            $auth->update(str_repeat("\0", ((0x10 - $aadlen) & 0xf)));

            # memset(block, 0, sizeof block);
            # block[0] = tag;
            # crypto_stream_chacha20_ietf_xor_ic(block, block, sizeof block,
            #                                    state->nonce, 1U, state->k);
            $block = ParagonIE_Sodium_Core_ChaCha20::ietfStreamXorIc(ParagonIE_Sodium_Core_Util::intToChr($tag).str_repeat("\0", 63), $st->getCombinedNonce(), $st->getKey(), ParagonIE_Sodium_Core_Util::store64_le(1));

            # crypto_onetimeauth_poly1305_update(&poly1305_state, block, sizeof block);
            $auth->update($block);

            # out[0] = block[0];
            $out = $block[0];
            # c = out + (sizeof tag);
            # crypto_stream_chacha20_ietf_xor_ic(c, m, mlen, state->nonce, 2U, state->k);
            $cipher = ParagonIE_Sodium_Core_ChaCha20::ietfStreamXorIc($msg, $st->getCombinedNonce(), $st->getKey(), ParagonIE_Sodium_Core_Util::store64_le(2));

            # crypto_onetimeauth_poly1305_update(&poly1305_state, c, mlen);
            $auth->update($cipher);

            $out .= $cipher;
            unset($cipher);

            # crypto_onetimeauth_poly1305_update
            # (&poly1305_state, _pad0, (0x10 - (sizeof block) + mlen) & 0xf);
            $auth->update(str_repeat("\0", ((0x10 - 64 + $msglen) & 0xf)));

            # STORE64_LE(slen, (uint64_t) adlen);
            $slen = ParagonIE_Sodium_Core_Util::store64_le($aadlen);

            # crypto_onetimeauth_poly1305_update(&poly1305_state, slen, sizeof slen);
            $auth->update($slen);

            # STORE64_LE(slen, (sizeof block) + mlen);
            $slen = ParagonIE_Sodium_Core_Util::store64_le(64 + $msglen);

            # crypto_onetimeauth_poly1305_update(&poly1305_state, slen, sizeof slen);
            $auth->update($slen);

            # mac = c + mlen;
            # crypto_onetimeauth_poly1305_final(&poly1305_state, mac);
            $mac = $auth->finish();
            $out .= $mac;

            # sodium_memzero(&poly1305_state, sizeof poly1305_state);
            unset($auth);

            # XOR_BUF(STATE_INONCE(state), mac,
            #     crypto_secretstream_xchacha20poly1305_INONCEBYTES);
            $st->xorNonce($mac);

            # sodium_increment(STATE_COUNTER(state),
            #     crypto_secretstream_xchacha20poly1305_COUNTERBYTES);
            $st->incrementCounter();
            // Overwrite by reference:
            $state = $st->toString();

            $rekey = ($tag & ParagonIE_Sodium_Compat::CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_REKEY) !== 0;
            # if ((tag & crypto_secretstream_xchacha20poly1305_TAG_REKEY) != 0 ||
            #     sodium_is_zero(STATE_COUNTER(state),
            #         crypto_secretstream_xchacha20poly1305_COUNTERBYTES)) {
            #     crypto_secretstream_xchacha20poly1305_rekey(state);
            # }
            if($rekey || $st->needsRekey())
            {
                // DO REKEY
                self::secretstream_xchacha20poly1305_rekey($state);
            }
            # if (outlen_p != NULL) {
            #     *outlen_p = crypto_secretstream_xchacha20poly1305_ABYTES + mlen;
            # }
            return $out;
        }

        public static function secretstream_xchacha20poly1305_rekey(&$state)
        {
            $st = ParagonIE_Sodium_Core_SecretStream_State::fromString($state);
            # unsigned char new_key_and_inonce[crypto_stream_chacha20_ietf_KEYBYTES +
            # crypto_secretstream_xchacha20poly1305_INONCEBYTES];
            # size_t        i;
            # for (i = 0U; i < crypto_stream_chacha20_ietf_KEYBYTES; i++) {
            #     new_key_and_inonce[i] = state->k[i];
            # }
            $new_key_and_inonce = $st->getKey();

            # for (i = 0U; i < crypto_secretstream_xchacha20poly1305_INONCEBYTES; i++) {
            #     new_key_and_inonce[crypto_stream_chacha20_ietf_KEYBYTES + i] =
            #         STATE_INONCE(state)[i];
            # }
            $new_key_and_inonce .= ParagonIE_Sodium_Core_Util::substR($st->getNonce(), 0, 8);

            # crypto_stream_chacha20_ietf_xor(new_key_and_inonce, new_key_and_inonce,
            #                                 sizeof new_key_and_inonce,
            #                                 state->nonce, state->k);

            $st->rekey(ParagonIE_Sodium_Core_ChaCha20::ietfStreamXorIc($new_key_and_inonce, $st->getCombinedNonce(), $st->getKey(), ParagonIE_Sodium_Core_Util::store64_le(0)));

            # for (i = 0U; i < crypto_stream_chacha20_ietf_KEYBYTES; i++) {
            #     state->k[i] = new_key_and_inonce[i];
            # }
            # for (i = 0U; i < crypto_secretstream_xchacha20poly1305_INONCEBYTES; i++) {
            #     STATE_INONCE(state)[i] =
            #          new_key_and_inonce[crypto_stream_chacha20_ietf_KEYBYTES + i];
            # }
            # _crypto_secretstream_xchacha20poly1305_counter_reset(state);
            $st->counterReset();

            $state = $st->toString();
        }

        public static function secretstream_xchacha20poly1305_pull(&$state, $cipher, $aad = '')
        {
            $st = ParagonIE_Sodium_Core_SecretStream_State::fromString($state);

            $cipherlen = ParagonIE_Sodium_Core_Util::strlen($cipher);
            #     mlen = inlen - crypto_secretstream_xchacha20poly1305_ABYTES;
            $msglen = $cipherlen - ParagonIE_Sodium_Compat::CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_ABYTES;
            $aadlen = ParagonIE_Sodium_Core_Util::strlen($aad);

            #     if (mlen > crypto_secretstream_xchacha20poly1305_MESSAGEBYTES_MAX) {
            #         sodium_misuse();
            #     }
            if((($msglen + 63) >> 6) > 0xfffffffe)
            {
                throw new SodiumException('message cannot be larger than SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_MESSAGEBYTES_MAX bytes');
            }

            #     crypto_stream_chacha20_ietf(block, sizeof block, state->nonce, state->k);
            #     crypto_onetimeauth_poly1305_init(&poly1305_state, block);
            #     sodium_memzero(block, sizeof block);
            $auth = new ParagonIE_Sodium_Core_Poly1305_State(ParagonIE_Sodium_Core_ChaCha20::ietfStream(32, $st->getCombinedNonce(), $st->getKey()));

            #     crypto_onetimeauth_poly1305_update(&poly1305_state, ad, adlen);
            $auth->update($aad);

            #     crypto_onetimeauth_poly1305_update(&poly1305_state, _pad0,
            #         (0x10 - adlen) & 0xf);
            $auth->update(str_repeat("\0", ((0x10 - $aadlen) & 0xf)));

            #     memset(block, 0, sizeof block);
            #     block[0] = in[0];
            #     crypto_stream_chacha20_ietf_xor_ic(block, block, sizeof block,
            #                                        state->nonce, 1U, state->k);
            $block = ParagonIE_Sodium_Core_ChaCha20::ietfStreamXorIc($cipher[0].str_repeat("\0", 63), $st->getCombinedNonce(), $st->getKey(), ParagonIE_Sodium_Core_Util::store64_le(1));
            #     tag = block[0];
            #     block[0] = in[0];
            #     crypto_onetimeauth_poly1305_update(&poly1305_state, block, sizeof block);
            $tag = ParagonIE_Sodium_Core_Util::chrToInt($block[0]);
            $block[0] = $cipher[0];
            $auth->update($block);

            #     c = in + (sizeof tag);
            #     crypto_onetimeauth_poly1305_update(&poly1305_state, c, mlen);
            $auth->update(ParagonIE_Sodium_Core_Util::substr($cipher, 1, $msglen));

            #     crypto_onetimeauth_poly1305_update
            #     (&poly1305_state, _pad0, (0x10 - (sizeof block) + mlen) & 0xf);
            $auth->update(str_repeat("\0", ((0x10 - 64 + $msglen) & 0xf)));

            #     STORE64_LE(slen, (uint64_t) adlen);
            #     crypto_onetimeauth_poly1305_update(&poly1305_state, slen, sizeof slen);
            $slen = ParagonIE_Sodium_Core_Util::store64_le($aadlen);
            $auth->update($slen);

            #     STORE64_LE(slen, (sizeof block) + mlen);
            #     crypto_onetimeauth_poly1305_update(&poly1305_state, slen, sizeof slen);
            $slen = ParagonIE_Sodium_Core_Util::store64_le(64 + $msglen);
            $auth->update($slen);

            #     crypto_onetimeauth_poly1305_final(&poly1305_state, mac);
            #     sodium_memzero(&poly1305_state, sizeof poly1305_state);
            $mac = $auth->finish();

            #     stored_mac = c + mlen;
            #     if (sodium_memcmp(mac, stored_mac, sizeof mac) != 0) {
            #     sodium_memzero(mac, sizeof mac);
            #         return -1;
            #     }

            $stored = ParagonIE_Sodium_Core_Util::substr($cipher, $msglen + 1, 16);
            if(! ParagonIE_Sodium_Core_Util::hashEquals($mac, $stored))
            {
                return false;
            }

            #     crypto_stream_chacha20_ietf_xor_ic(m, c, mlen, state->nonce, 2U, state->k);
            $out = ParagonIE_Sodium_Core_ChaCha20::ietfStreamXorIc(ParagonIE_Sodium_Core_Util::substr($cipher, 1, $msglen), $st->getCombinedNonce(), $st->getKey(), ParagonIE_Sodium_Core_Util::store64_le(2));

            #     XOR_BUF(STATE_INONCE(state), mac,
            #         crypto_secretstream_xchacha20poly1305_INONCEBYTES);
            $st->xorNonce($mac);

            #     sodium_increment(STATE_COUNTER(state),
            #         crypto_secretstream_xchacha20poly1305_COUNTERBYTES);
            $st->incrementCounter();

            #     if ((tag & crypto_secretstream_xchacha20poly1305_TAG_REKEY) != 0 ||
            #         sodium_is_zero(STATE_COUNTER(state),
            #             crypto_secretstream_xchacha20poly1305_COUNTERBYTES)) {
            #         crypto_secretstream_xchacha20poly1305_rekey(state);
            #     }

            // Overwrite by reference:
            $state = $st->toString();

            $rekey = ($tag & ParagonIE_Sodium_Compat::CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_REKEY) !== 0;
            if($rekey || $st->needsRekey())
            {
                // DO REKEY
                self::secretstream_xchacha20poly1305_rekey($state);
            }

            return [$out, $tag];
        }

        public static function sign_detached($message, $sk)
        {
            return ParagonIE_Sodium_Core_Ed25519::sign_detached($message, $sk);
        }

        public static function sign($message, $sk)
        {
            return ParagonIE_Sodium_Core_Ed25519::sign($message, $sk);
        }

        public static function sign_open($signedMessage, $pk)
        {
            return ParagonIE_Sodium_Core_Ed25519::sign_open($signedMessage, $pk);
        }

        public static function sign_verify_detached($signature, $message, $pk)
        {
            return ParagonIE_Sodium_Core_Ed25519::verify_detached($signature, $message, $pk);
        }
    }
