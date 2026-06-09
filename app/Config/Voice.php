<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Voice extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * Pronoun: Subject
     * --------------------------------------------------------------------------
     *
     * The subject pronoun used in content copy.
     * E.g., "I" (personal) or "We" (collective/brand).
     */
    public string $subject = 'I';

    /**
     * --------------------------------------------------------------------------
     * Pronoun: Object
     * --------------------------------------------------------------------------
     *
     * The object pronoun used in content copy.
     * E.g., "me" or "us".
     */
    public string $object = 'me';

    /**
     * --------------------------------------------------------------------------
     * Pronoun: Possessive Determiner
     * --------------------------------------------------------------------------
     *
     * The possessive determiner used in content copy.
     * E.g., "my" or "our".
     */
    public string $possessive = 'my';

    /**
     * --------------------------------------------------------------------------
     * Pronoun: Reflexive
     * --------------------------------------------------------------------------
     *
     * The reflexive pronoun used in content copy.
     * E.g., "myself" or "ourselves".
     */
    public string $reflexive = 'myself';
}
