<?php

declare(strict_types=1);

use App\Support\VersionedAsset;
use Inertia\Testing\AssertableInertia as Assert;

test('versioned asset urls include a filemtime cache buster', function (): void {
    $url = VersionedAsset::url('bounce_logo.png');
    $mtime = (string) filemtime(public_path('bounce_logo.png'));

    expect($url)
        ->toContain('bounce_logo.png')
        ->toContain('v='.$mtime);
});

test('login page shares the official versioned logo assets', function (): void {
    $logo = VersionedAsset::url('bounce_logo.png');
    $logoDark = VersionedAsset::url('bounce-logo.png');

    $this->get(route('login'))
        ->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->where('assets.logo', $logo)
            ->where('assets.logoDark', $logoDark)
        );
});
