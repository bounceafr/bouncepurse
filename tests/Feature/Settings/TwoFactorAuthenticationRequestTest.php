<?php

declare(strict_types=1);

use App\Http\Requests\Settings\TwoFactorAuthenticationRequest;

test('two factor authentication request authorize returns true when feature is enabled', function (): void {
    $request = new TwoFactorAuthenticationRequest;

    expect($request->authorize())->toBeTrue();
});

test('two factor authentication request rules returns empty array', function (): void {
    $request = new TwoFactorAuthenticationRequest;

    expect($request->rules())->toBe([]);
});
