<?php

use App\Services\FundingLimitService;

beforeEach(function () {
    $this->service = new FundingLimitService;
});

test('get limits returns correct values for usd', function () {
    $limits = $this->service->getLimits('USD');

    expect($limits)->toBeArray()
        ->and($limits['min'])->toBe(200_000) // 2 000 $
        ->and($limits['max'])->toBe(10_000_000); // 100 000 $
});

test('get limits returns correct values for cdf', function () {
    $limits = $this->service->getLimits('CDF');

    expect($limits)->toBeArray()
        ->and($limits['min'])->toBe(500_000_000) // 5 000 000 CDF
        ->and($limits['max'])->toBe(25_000_000_000); // 250 000 000 CDF
});

test('get limits returns correct values for eur', function () {
    $limits = $this->service->getLimits('EUR');

    expect($limits)->toBeArray()
        ->and($limits['min'])->toBe(200_000) // 2 000 €
        ->and($limits['max'])->toBe(10_000_000); // 100 000 €
});

test('get limits returns null for unsupported currency', function () {
    $limits = $this->service->getLimits('GBP');

    expect($limits)->toBeNull();
});

test('is within limits accepts valid amount for usd', function () {
    $result = $this->service->isWithinLimits(5_000_000, 'USD'); // 50 000 $

    expect($result)->toBeTrue();
});

test('is within limits rejects amount too low', function () {
    $result = $this->service->isWithinLimits(100_000, 'USD'); // 1 000 $ (trop bas)

    expect($result)->toBeFalse();
});

test('is within limits rejects amount too high', function () {
    $result = $this->service->isWithinLimits(15_000_000, 'USD'); // 150 000 $ (trop élevé)

    expect($result)->toBeFalse();
});

test('is within limits accepts exact min amount', function () {
    $result = $this->service->isWithinLimits(200_000, 'USD'); // Exactement 2 000 $

    expect($result)->toBeTrue();
});

test('is within limits accepts exact max amount', function () {
    $result = $this->service->isWithinLimits(10_000_000, 'USD'); // Exactement 100 000 $

    expect($result)->toBeTrue();
});

test('is within limits returns true for unsupported currency', function () {
    // Par défaut, on ne bloque pas les devises non supportées
    $result = $this->service->isWithinLimits(999_999_999, 'GBP');

    expect($result)->toBeTrue();
});

test('get validation message returns localized message', function () {
    $message = $this->service->getValidationMessage('USD');

    expect($message)
        ->toContain('2 000')
        ->toContain('100 000')
        ->toContain('USD');
});

test('get all limits returns all currencies', function () {
    $limits = $this->service->getAllLimits();

    expect($limits)->toBeArray()
        ->toHaveKey('USD')
        ->toHaveKey('CDF')
        ->toHaveKey('EUR')
        ->toHaveCount(3);
});

test('format amount returns readable string', function () {
    $formatted = $this->service->formatAmount(5_000_000, 'USD');

    expect($formatted)->toBe('50 000 USD');
});

test('is currency supported returns true for supported currencies', function () {
    expect($this->service->isCurrencySupported('USD'))->toBeTrue()
        ->and($this->service->isCurrencySupported('CDF'))->toBeTrue()
        ->and($this->service->isCurrencySupported('EUR'))->toBeTrue();
});

test('is currency supported returns false for unsupported currency', function () {
    expect($this->service->isCurrencySupported('GBP'))->toBeFalse()
        ->and($this->service->isCurrencySupported('JPY'))->toBeFalse();
});

test('is currency supported is case insensitive', function () {
    expect($this->service->isCurrencySupported('usd'))->toBeTrue()
        ->and($this->service->isCurrencySupported('Usd'))->toBeTrue();
});
