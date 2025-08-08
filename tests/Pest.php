<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function createUserAndGenerateToken()
{
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $personalAccessTokenResult = $user->createToken('TestToken');
    return [
        'token' => $personalAccessTokenResult->plainTextToken,
        'user' => $user
    ];
}

function createUnverifiedUserAndGenerateToken()
{
    $user = User::factory()->create([
        'password' => Hash::make('password'),
        'email_verified_at' => null
    ]);

    $personalAccessTokenResult = $user->createToken('TestToken');
    return [
        'token' => $personalAccessTokenResult->plainTextToken,
        'user' => $user
    ];
}

function verificationUrlParam($user)
{
    $signedRoute = URL::temporarySignedRoute(
        'auth.verification.verify',
        Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
        [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]
    );

    // Pull down the signed route for restructuring with the callbackUrl
    $parsedUrl = parse_url($signedRoute);
    parse_str($parsedUrl['query'], $urlQueries);

    // Build the query parameters
    $parameters = http_build_query([
        'expires' => $urlQueries['expires'],
        'hash' => $urlQueries['hash'],
        'id' => $urlQueries['id'],
        'signature' => $urlQueries['signature']
    ]);

    return "{$parameters}";
}
