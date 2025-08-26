<?php

return [

    /*
    |--------------------------------------------------------------------------
    | JWT Authentication Secret
    |--------------------------------------------------------------------------
    |
    | Don't forget to set this in your .env file, as it will be used to sign
    | your tokens. A helper command is provided for this:
    | `php artisan jwt:secret`
    |
    | Note: This will be used for ALL guard types unless specified otherwise.
    |
    */

    'secret' => env('JWT_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | JWT Authentication Keys
    |--------------------------------------------------------------------------
    |
    | The algorithm you are using, will determine the token's signature, and
    | to an extent, how the tokens are serialized, allowing you to achieve
    | some of the more advanced features of JWT.
    |
    | When you have multiple algorithms defined, then the `TYPES` array will
    | be used to determine which algorithm is used for the token generation.
    | You can also set this to null to use the same default key for all
    | algorithms.
    |
    | Supported algorithms are: HS256, HS384, HS512, RS256, RS384, RS512,
    | ES256, ES384, ES512, EdDSA
    |
    */

    'keys' => [

        /*
        |--------------------------------------------------------------------------
        | Public Key
        |--------------------------------------------------------------------------
        |
        | A path or string to your public key that corresponds to your private
        | key. It is used for verifying tokens that are signed with the
        | corresponding private key.
        |
        | Since JWT tokens are usually sent in Authorization headers, you can
        | also use your domain as a prefix, e.g. https://example.com/jwt
        |
        */

        'public' => env('JWT_PUBLIC_KEY'),

        /*
        |--------------------------------------------------------------------------
        | Private Key
        |--------------------------------------------------------------------------
        |
        | A path or string to your private key that corresponds to your public
        | key. It is used for signing tokens that are issued by your
        | application. The private key should never be shared!
        |
        */

        'private' => env('JWT_PRIVATE_KEY'),

        /*
        |--------------------------------------------------------------------------
        | Passphrase
        |--------------------------------------------------------------------------
        |
        | When using RSA or ECDSA, you might need to provide a passphrase for
        | your private key. You can provide it here, but it is recommended
        | to pass it through the .env file for better security.
        |
        */

        'passphrase' => env('JWT_PASSPHRASE'),

    ],

    /*
    |--------------------------------------------------------------------------
    | JWT time to live
    |--------------------------------------------------------------------------
    |
    | Specify the length of time (in minutes) that the token will be valid for,
    | defaults to 1 hour (60).
    |
    | You can also set this to null, to yield a never expiring token.
    | Some people may want this behaviour for e.g. a mobile app.
    | This is not particularly recommended, so make sure you have appropriate
    | systems in place to revoke these tokens if necessary.
    | Note: If you set this to null you should remove 'exp' element from 'required_claims' list.
    |
    */

    'ttl' => env('JWT_TTL', 30),

    /*
    |--------------------------------------------------------------------------
    | Refresh time to live
    |--------------------------------------------------------------------------
    |
    | Specify the length of time (in minutes) that the token can be refreshed
    | within. I.E. The user can refresh their token within a 2 week window of
    | the original token being created until they must re-authenticate.
    | Defaults to 2 weeks.
    |
    | You can also set this to null, to yield an infinite refresh time.
    | Some may want this instead of never expiring tokens for e.g. a mobile app.
    | This is not particularly recommended, so make sure you have appropriate
    | systems in place to revoke these tokens if necessary.
    |
    */

    'refresh_ttl' => env('JWT_REFRESH_TTL', 1440),

    /*
    |--------------------------------------------------------------------------
    | JWT hashing algorithm
    |--------------------------------------------------------------------------
    |
    | Specify the hashing algorithm that will be used to sign the token.
    |
    | See here: https://github.com/namshi/jose/tree/master/src/Namshi/JOSE/Signer/OpenSSL
    | for possible values.
    |
    */

    'algo' => env('JWT_ALGO', 'HS256'),

    /*
    |--------------------------------------------------------------------------
    | Required Claims
    |--------------------------------------------------------------------------
    |
    | Specify the required claims that must exist in any token. A TokenInvalid
    | exception will be thrown if any of these claims are not present.
    |
    */

    'required_claims' => [
        'iss',
        'iat',
        'exp',
        'nbf',
        'sub',
        'jti',
    ],

    /*
    |--------------------------------------------------------------------------
    | Persistent Claims
    |--------------------------------------------------------------------------
    |
    | Specify the claim keys to be persisted when refreshing a token. The
    | claims specified will be duplicated as they are added to the refreshed
    | token.
    |
    | Note: If a claim's key doesn't exist in the original token, it will be
    | ignored. It won't be added to the refreshed token.
    |
    | In most cases, the defaults are appropriate.
    |
    */

    'persistent_claims' => [
        // 'foo',
        // 'bar',
    ],

    /*
    |--------------------------------------------------------------------------
    | Lock Subject
    |--------------------------------------------------------------------------
    |
    | This will determine whether a `prv` claim is automatically added to
    | the token. The `prv` claim is a unique identifier for the user for
    | the issued token. It is used to prevent token refresh abuse.
    |
    | For more information, see: https://github.com/tymondesigns/jwt-auth/issues/1973
    |
    */

    'lock_subject' => true,

    /*
    |--------------------------------------------------------------------------
    | Leeway
    |--------------------------------------------------------------------------
    |
    | This property gives the jwt timestamp claims some "leeway".
    | Meaning that if you have any clock skew on any of your servers
    | then this will afford you some level of cushioning.
    |
    | This applies to the claims `iat`, `nbf` and `exp`.
    |
    | Please specify this value in seconds. Possible values:
    |
    |  - 0: No leeway (default)
    |  - 30: 30 seconds leeway
    |  - 60: 1 minute leeway
    |
    */

    'leeway' => env('JWT_LEEWAY', 0),

    /*
    |--------------------------------------------------------------------------
    | Blacklist Enabled
    |--------------------------------------------------------------------------
    |
    | In order to invalidate tokens, you must have the blacklist enabled.
    | If you do not want or need this functionality, then you can
    | set this to false but it must be explicitly set, as it would
    | be an "abnormal" state, and leaving it to the default here
    | would create an extremely random state.
    |
    | If you explicitly want to disable the blacklist, then you
    | should replace this value with a boolean false.
    |
    | Note: This has been deprecated and will be removed in the next
    | major release
    |
    */

    'blacklist_enabled' => env('JWT_BLACKLIST_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Blacklist Grace Period
    |--------------------------------------------------------------------------
    |
    | When multiple concurrent requests are made with the same JWT,
    | the blacklist grace period determines how long (in seconds)
    | to allow the first request to proceed while blocking the
    | subsequent requests.
    |
    | Note: This has been deprecated and will be removed in the next
    | major release
    |
    */

    'blacklist_grace_period' => env('JWT_BLACKLIST_GRACE_PERIOD', 0),

    /*
    |--------------------------------------------------------------------------
    | Show blacklisted token option
    |--------------------------------------------------------------------------
    |
    | When set to true, this will show a blacklisted token option
    | in the blacklist exception message. This is useful for
    | debugging purposes.
    |
    | Note: This has been deprecated and will be removed in the next
    | major release
    |
    */

    'show_black_list_exception' => env('JWT_SHOW_BLACKLIST_EXCEPTION', true),

    /*
    |--------------------------------------------------------------------------
    | Decryptable tokens
    |--------------------------------------------------------------------------
    |
    | This option allows you to create decryptable tokens. When set to true,
    | the secret key will be used to encrypt the token payload. You can
    | decrypt the payload using the secret key.
    |
    | Note: This is only available for the following algorithms:
    | HS256, HS384, HS512, RS256, RS384, RS512, ES256, ES384, ES512
    |
    */

    'decrypt' => false,

    /*
    |--------------------------------------------------------------------------
    | Providers
    |--------------------------------------------------------------------------
    |
    | Specify the various providers used throughout the package when
    | using the various features. These providers can be overridden
    | within the config array where they are defined.
    |
    | Note: This has been deprecated and will be removed in the next
    | major release
    |
    */

    'providers' => [

        'jwt' => Tymon\JWTAuth\Providers\JWT\Lcobucci::class,

        'auth' => Tymon\JWTAuth\Providers\Auth\Illuminate::class,

        'storage' => Tymon\JWTAuth\Providers\Storage\Illuminate::class,

    ],

];