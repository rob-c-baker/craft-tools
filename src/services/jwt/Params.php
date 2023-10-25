<?php declare(strict_types=1);

namespace alanrogers\tools\services\jwt;

use DateTimeImmutable;
use yii\base\BaseObject;

class Params extends BaseObject
{
    /**
     * The secret key used to sign the token.
     * @var string|null (Optional) Default: `securityKey` in Craft general config.
     */
    public ?string $signing_key = null;

    /**
     * Configures the issuer (iss claim)
     * Example: "https://alanrogers.com"
     * @var string
     */
    public string $issued_by;

    /**
     * Configures the audience (aud claim)
     * Example: "https://alanrogers.com/api"
     * @var string
     */
    public string $permitted_for;

    /**
     * Configures the id (jti claim)
     * Example: "example@example.com"
     * Example (id): "123456"
     * Example (uid): "a79da330-3cbc-4904-adea-9b3b8aef0506"
     * @var string|null (Optional)
     */
    public ?string $identified_by = null;

    /**
     * Configures the time that the token was issue (iat claim)
     * Note: Timezone will be forced to UTC.
     * @var DateTimeImmutable|null (Optional) Default: now
     */
    public ?DateTimeImmutable $issued_at = null;

    /**
     * Configures the time that the token can be used (nbf claim)
     * Example: `(new DateTimeImmutable())->modify('+1 minute')`
     * Note: Timezone will be forced to UTC.
     * @var DateTimeImmutable|null(Optional) Default: now
     */
    public ?DateTimeImmutable $can_only_be_used_after = null;

    /**
     * Configures the expiration time of the token (exp claim)
     * Example: `(new DateTimeImmutable())->modify('+1 hour')`
     * Note: Timezone will be forced to UTC.
     * @var DateTimeImmutable|null (Optional) Default: `(new DateTimeImmutable())->modify('+5 mins')`
     */
    public ?DateTimeImmutable $expires_at = null;

    /**
     * Arbitrary array of custom claims
     * Example: `[ 'foo' => 'bar', ... ]`
     * @var array<string, int|float|string>
     */
    public array $claims = [];

    /**
     * Arbitrary array of custom headers
     * Example: `[ 'foo` => 'bar', ... ]`
     * @var array
     */
    public array $headers = [];
}