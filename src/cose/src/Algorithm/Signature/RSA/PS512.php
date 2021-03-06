<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace Cose\Algorithm\Signature\RSA;

use Cose\Algorithms;
use Jose\Component\Core\Util\Hash;

final class PS512 extends PSSRSA
{
    public static function identifier(): int
    {
        return Algorithms::COSE_ALGORITHM_PS512;
    }

    protected function getHashAlgorithm(): Hash
    {
        return Hash::sha512();
    }
}
