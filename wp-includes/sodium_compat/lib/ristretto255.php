<?php

    if(! defined('SODIUM_CRYPTO_CORE_RISTRETTO255_BYTES'))
    {
        define('SODIUM_CRYPTO_CORE_RISTRETTO255_BYTES', ParagonIE_Sodium_Compat::CRYPTO_CORE_RISTRETTO255_BYTES);
        define('SODIUM_COMPAT_POLYFILLED_RISTRETTO255', true);
    }
    if(! defined('SODIUM_CRYPTO_CORE_RISTRETTO255_HASHBYTES'))
    {
        define('SODIUM_CRYPTO_CORE_RISTRETTO255_HASHBYTES', ParagonIE_Sodium_Compat::CRYPTO_CORE_RISTRETTO255_HASHBYTES);
    }
    if(! defined('SODIUM_CRYPTO_CORE_RISTRETTO255_SCALARBYTES'))
    {
        define('SODIUM_CRYPTO_CORE_RISTRETTO255_SCALARBYTES', ParagonIE_Sodium_Compat::CRYPTO_CORE_RISTRETTO255_SCALARBYTES);
    }
    if(! defined('SODIUM_CRYPTO_CORE_RISTRETTO255_NONREDUCEDSCALARBYTES'))
    {
        define('SODIUM_CRYPTO_CORE_RISTRETTO255_NONREDUCEDSCALARBYTES', ParagonIE_Sodium_Compat::CRYPTO_CORE_RISTRETTO255_NONREDUCEDSCALARBYTES);
    }
    if(! defined('SODIUM_CRYPTO_SCALARMULT_RISTRETTO255_SCALARBYTES'))
    {
        define('SODIUM_CRYPTO_SCALARMULT_RISTRETTO255_SCALARBYTES', ParagonIE_Sodium_Compat::CRYPTO_SCALARMULT_RISTRETTO255_SCALARBYTES);
    }
    if(! defined('SODIUM_CRYPTO_SCALARMULT_RISTRETTO255_BYTES'))
    {
        define('SODIUM_CRYPTO_SCALARMULT_RISTRETTO255_BYTES', ParagonIE_Sodium_Compat::CRYPTO_SCALARMULT_RISTRETTO255_BYTES);
    }

    if(! is_callable('sodium_crypto_core_ristretto255_add'))
    {
        function sodium_crypto_core_ristretto255_add($p, $q)
        {
            return ParagonIE_Sodium_Compat::ristretto255_add($p, $q, true);
        }
    }
    if(! is_callable('sodium_crypto_core_ristretto255_from_hash'))
    {
        function sodium_crypto_core_ristretto255_from_hash($s)
        {
            return ParagonIE_Sodium_Compat::ristretto255_from_hash($s, true);
        }
    }
    if(! is_callable('sodium_crypto_core_ristretto255_is_valid_point'))
    {
        function sodium_crypto_core_ristretto255_is_valid_point($s)
        {
            return ParagonIE_Sodium_Compat::ristretto255_is_valid_point($s, true);
        }
    }
    if(! is_callable('sodium_crypto_core_ristretto255_random'))
    {
        function sodium_crypto_core_ristretto255_random()
        {
            return ParagonIE_Sodium_Compat::ristretto255_random(true);
        }
    }
    if(! is_callable('sodium_crypto_core_ristretto255_scalar_add'))
    {
        function sodium_crypto_core_ristretto255_scalar_add($x, $y)
        {
            return ParagonIE_Sodium_Compat::ristretto255_scalar_add($x, $y, true);
        }
    }
    if(! is_callable('sodium_crypto_core_ristretto255_scalar_complement'))
    {
        function sodium_crypto_core_ristretto255_scalar_complement($s)
        {
            return ParagonIE_Sodium_Compat::ristretto255_scalar_complement($s, true);
        }
    }
    if(! is_callable('sodium_crypto_core_ristretto255_scalar_invert'))
    {
        function sodium_crypto_core_ristretto255_scalar_invert($p)
        {
            return ParagonIE_Sodium_Compat::ristretto255_scalar_invert($p, true);
        }
    }
    if(! is_callable('sodium_crypto_core_ristretto255_scalar_mul'))
    {
        function sodium_crypto_core_ristretto255_scalar_mul($x, $y)
        {
            return ParagonIE_Sodium_Compat::ristretto255_scalar_mul($x, $y, true);
        }
    }
    if(! is_callable('sodium_crypto_core_ristretto255_scalar_negate'))
    {
        function sodium_crypto_core_ristretto255_scalar_negate($s)
        {
            return ParagonIE_Sodium_Compat::ristretto255_scalar_negate($s, true);
        }
    }
    if(! is_callable('sodium_crypto_core_ristretto255_scalar_random'))
    {
        function sodium_crypto_core_ristretto255_scalar_random()
        {
            return ParagonIE_Sodium_Compat::ristretto255_scalar_random(true);
        }
    }
    if(! is_callable('sodium_crypto_core_ristretto255_scalar_reduce'))
    {
        function sodium_crypto_core_ristretto255_scalar_reduce($s)
        {
            return ParagonIE_Sodium_Compat::ristretto255_scalar_reduce($s, true);
        }
    }
    if(! is_callable('sodium_crypto_core_ristretto255_scalar_sub'))
    {
        function sodium_crypto_core_ristretto255_scalar_sub($x, $y)
        {
            return ParagonIE_Sodium_Compat::ristretto255_scalar_sub($x, $y, true);
        }
    }
    if(! is_callable('sodium_crypto_core_ristretto255_sub'))
    {
        function sodium_crypto_core_ristretto255_sub($p, $q)
        {
            return ParagonIE_Sodium_Compat::ristretto255_sub($p, $q, true);
        }
    }
    if(! is_callable('sodium_crypto_scalarmult_ristretto255'))
    {
        function sodium_crypto_scalarmult_ristretto255($n, $p)
        {
            return ParagonIE_Sodium_Compat::scalarmult_ristretto255($n, $p, true);
        }
    }
    if(! is_callable('sodium_crypto_scalarmult_ristretto255_base'))
    {
        function sodium_crypto_scalarmult_ristretto255_base($n)
        {
            return ParagonIE_Sodium_Compat::scalarmult_ristretto255_base($n, true);
        }
    }