<?php declare(strict_types=1);

namespace alanrogers\tools\services\jwt;

use alanrogers\tools\traits\ErrorManagementTrait;
use Craft;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use Lcobucci\Clock\Clock;
use Lcobucci\Clock\FrozenClock;
use Lcobucci\JWT\Encoding\CannotDecodeContent;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Hmac\Sha384;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Token\Builder;
use Lcobucci\JWT\Token\InvalidTokenStructure;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Token\UnsupportedHeaderFound;
use Lcobucci\JWT\Validation\Constraint;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use Lcobucci\JWT\Validation\Validator;

class JWTAuth
{
    use ErrorManagementTrait;

    /**
     * Keys that are valid for use when creating constraints.
     * These might be keys in `Params` object or that describe time based validity.
     */
    public const array CONSTRAINT_KEYS = [
        'identified_by',
        'issued_by',
        'permitted_for',
        'valid_at'
    ];

    /**
     * Helper for creating a `Params` instance.
     * @param array $params
     * @return Params
     */
    public function createParams(array $params): Params
    {
        return new Params($params);
    }

    /**
     * Issues a JWT Token
     * @param Params $params
     * @return Plain
     * @throws JWTException
     */
    public function issueToken(Params $params): Plain
    {
        $builder = new Builder(new JoseEncoder(), ChainedFormatter::default());
        $algorithm = new Sha384();

        if ($params->signing_key === null) {
            // Key must be at least 48 chars / 384 bits
            $length = strlen($_SERVER['JWT_SIGNING_KEY']);
            if (isset($_SERVER['JWT_SIGNING_KEY']) && strlen($_SERVER['JWT_SIGNING_KEY']) >= 48) {
                $params->signing_key = $_SERVER['JWT_SIGNING_KEY'];
            } elseif ($length < 48) {
                throw new JWTException('Signing key must be at least 48 chars / 384 bits.');
            }
        }

        $signing_key = InMemory::plainText($_SERVER['JWT_SIGNING_KEY'] ?? $params->signing_key);

        // Required params
        $builder->issuedBy($params->issued_by)
                ->permittedFor($params->permitted_for);

        // Optional Params
        if ($params->identified_by !== null) {
            $builder->identifiedBy($params->identified_by);
        }
        if ($params->claims) {
            foreach ($params->claims as $key => $value) {
                $builder->withClaim($key, $value);
            }
        }
        if ($params->headers) {
            foreach ($params->headers as $key => $value) {
                $builder->withHeader($key, $value);
            }
        }

        // Date params
        if ($params->issued_at === null) {
            try {
                $params->issued_at = new DateTimeImmutable('now', new DateTimeZone('UTC'));
            } catch (Exception $e) {
                // should not happen as only throws when invalid strings passed in and passing in constants
                throw new JWTException('Could not create `issued_at` DateTimeImmutable instance.', (int) $e->getCode());
            }
        }
        if ($params->can_only_be_used_after === null) {
            try {
                $params->can_only_be_used_after = new DateTimeImmutable('now', new DateTimeZone('UTC'));
            } catch (Exception $e) {
                // should not happen as only throws when invalid strings passed in and passing in constants
                throw new JWTException('Could not create `can_only_be_used_after` DateTimeImmutable instance.', (int) $e->getCode());
            }
        }
        if ($params->expires_at === null) {
            try {
                $params->expires_at = (new DateTimeImmutable(
                    'now',
                    new DateTimeZone('UTC')
                ))->modify('+5 mins');
            } catch (Exception $e) {
                // should not happen as only throws when invalid strings passed in and passing in constants
                throw new JWTException('Could not create `expires_at` DateTimeImmutable instance.', (int) $e->getCode());
            }
        }
        $builder->issuedAt($params->issued_at)
                ->canOnlyBeUsedAfter($params->can_only_be_used_after)
                ->expiresAt($params->expires_at);

        return $builder->getToken($algorithm, $signing_key);
    }

    /**
     * Parses a passed in token. NOTE: this does not check any of the claims.
     * @param string $token
     * @return Token
     * @throws JWTException
     */
    public function parseToken(string $token): Token
    {
        $parser = new Parser(new JoseEncoder());

        try {
            $parsed_token = $parser->parse($token);
        } catch (CannotDecodeContent | InvalidTokenStructure | UnsupportedHeaderFound $e) {
            throw new JWTException('Could not parse JWT token.', (int) $e->getCode(), $e);
        }

        return $parsed_token;
    }

    /**
     * Note: call `getErrors()` to see what the specific errors were if this method returns `false`.
     * @param string $token
     * @param Constraint[] $constraints
     * @return bool
     * @throws JWTException
     */
    public function validateToken(string $token, array $constraints=[]): bool
    {
        $parsed_token = $this->parseToken($token);

        $validator = new Validator();
        $ok = false;

        // add automatic expiry constraint:
        if (!isset($constraints['valid_at'])) {
            array_unshift($constraints, $this->createConstraint(
                'valid_at',
                $this->createFrozenClock('now', 'UTC')
            ));
        }

        try {
            $validator->assert($parsed_token, ...$constraints);
            $ok = true;
        } catch(RequiredConstraintsViolated $e) {
            foreach ($e->violations() as $violation) {
                $this->addError($violation->getMessage());
            }
        }

        return $ok;
    }

    /**
     * Creates a set of constraints for validating a token based on the values in a `Params` instance
     * @param Params $params
     * @return Constraint[]
     * @throws JWTException
     */
    public function createParamsConstraints(Params $params): array
    {
        $constraints = [];
        $params_values = array_filter(get_object_vars($params));
        foreach ($params_values as $key => $value) {
            $constraints[] = $this->createConstraint($key, $value);
        }
        return $constraints;
    }

    /**
     * Creates an individual constraint based on the `$claim_key` found in the `Params` class as a property key.
     * @param string $claim_key
     * @param string|float|int|Clock $claim_value
     * @return Constraint
     * @throws JWTException
     */
    public function createConstraint(string $claim_key, string|float|int|Clock $claim_value) : Constraint
    {
        // check key exists in `Params` class
        if (!in_array($claim_key, self::CONSTRAINT_KEYS, true)) {
            // Ensure reported key length is no more than 30 chars
            throw new JWTException(sprintf('Key "%.30s" not valid for creating a constraint.', $claim_key));
        }

        switch ($claim_key) {
            case 'identified_by' : return new Constraint\IdentifiedBy($claim_value);
            case 'issued_by' : return new Constraint\IssuedBy($claim_value);
            case 'permitted_for' : return new Constraint\PermittedFor($claim_value);
            case 'valid_at' : return new Constraint\ValidAt($claim_value);
        }

        throw new JWTException(
            sprintf(
                'Could not create constraint, key: "%.30s" valid but no constraint handler found.',
                $claim_key
            )
        );
    }

    /**
     * Creates a `FrozenClock` instance for the time based on `$datetime_string` which is a string suitable for passing
     * into `new DateTimeImmutable($datetime_string)`.
     * This method is useful if manually setting the `valid_at` constraint, however, you probably don't want to do that.
     * @param string $datetime_string (default: 'now')
     * @param string $timezone Timezone string (default: 'UTC' as that's what's used elsewhere)- NOTE: you probably don't want ot change this!
     * @return FrozenClock
     * @throws JWTException
     */
    public function createFrozenClock(string $datetime_string='now', string $timezone='UTC') : FrozenClock
    {
        try {
            return new FrozenClock(new DateTimeImmutable($datetime_string, new DateTimeZone($timezone)));
        } catch (Exception $e) {
            throw new JWTException('Could not create FrozenClock.', (int) $e->getCode(), $e);
        }
    }
}