<?php

namespace Pterodactyl\Extensions\Lcobucci\JWT\Encoding;

use Lcobucci\JWT\ClaimsFormatter;
use Lcobucci\JWT\Token\RegisteredClaims;

final class TimestampDates implements ClaimsFormatter
{
    
    public function formatClaims(array $claims): array
    {
        foreach (RegisteredClaims::DATE_CLAIMS as $claim) {
            if (!array_key_exists($claim, $claims)) {
                continue;
            }

            assert($claims[$claim] instanceof \DateTimeImmutable);
            $claims[$claim] = $claims[$claim]->getTimestamp();
        }

        return $claims;
    }
}
