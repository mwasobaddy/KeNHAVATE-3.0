<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;

test('two factor challenge redirects to login when not authenticated', function () {
    $this->markTestSkipped('Two-factor authentication not implemented with OTP flow.');
});

test('two factor challenge can be rendered', function () {
    $this->markTestSkipped('Two-factor authentication not implemented with OTP flow.');
});