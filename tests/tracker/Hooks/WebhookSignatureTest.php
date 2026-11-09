<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Tracker\Tests\Hooks;

use App\Tracker\Controller\AbstractHookController;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the GitHub webhook signature verification.
 *
 * @since  1.0
 */
class WebhookSignatureTest extends TestCase
{
    /**
     * Data provider for testIsValidSignature.
     *
     * @return  array
     *
     * @since   1.0
     */
    public static function signatureProvider(): array
    {
        $payload = 'payload=%7B%22zen%22%3A%22Keep+it+logically+awesome.%22%7D';
        $secret  = 'It\'s a Secret to Everybody';
        $valid   = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        return [
            'valid signature'        => [$payload, $valid, $secret, true],
            'tampered payload'       => [$payload . 'x', $valid, $secret, false],
            'wrong secret'           => [$payload, $valid, 'another secret', false],
            'missing prefix'         => [$payload, substr($valid, 7), $secret, false],
            'sha1 signature'         => [$payload, 'sha1=' . hash_hmac('sha1', $payload, $secret), $secret, false],
            'empty signature'        => [$payload, '', $secret, false],
            'empty secret'           => [$payload, 'sha256=' . hash_hmac('sha256', $payload, ''), '', false],
            'uppercase hex rejected' => [$payload, 'sha256=' . strtoupper(substr($valid, 7)), $secret, false],
        ];
    }

    /**
     * Test isValidSignature.
     *
     * @param   string   $payload    The raw request body.
     * @param   string   $signature  The signature header.
     * @param   string   $secret     The webhook secret.
     * @param   boolean  $expected   The expected result.
     *
     * @return  void
     *
     * @since   1.0
     */
    #[DataProvider('signatureProvider')]
    public function testIsValidSignature(string $payload, string $signature, string $secret, bool $expected): void
    {
        $this->assertSame($expected, AbstractHookController::isValidSignature($payload, $signature, $secret));
    }
}
