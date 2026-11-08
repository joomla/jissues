<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\Debug;

use App\Debug\DebugRedactor;
use Joomla\Database\DatabaseDriver;
use Joomla\Registry\Registry;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the DebugRedactor.
 *
 * @since  1.0
 */
class DebugRedactorTest extends TestCase
{
    /**
     * Test that sensitive keys are detected.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testIsSensitiveKey(): void
    {
        foreach (['password', 'gh_editbot_pass', 'client_secret', 'access_token', 'HTTP_COOKIE', 'api_key', 'PHP_AUTH_PW'] as $key) {
            $this->assertTrue(DebugRedactor::isSensitiveKey($key), $key);
        }

        foreach (['username', 'gh_editbot_user', 'title', 'id', 0] as $key) {
            $this->assertFalse(DebugRedactor::isSensitiveKey($key), (string) $key);
        }
    }

    /**
     * Test that sensitive values in nested arrays are redacted.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testRedactArray(): void
    {
        $data = [
            'user'   => 'bot',
            'empty'  => ['password' => ''],
            'github' => [
                'client_id'     => 'id',
                'client_secret' => 's3cr3t',
                'accounts'      => [['username' => 'bot', 'password' => 'hunter2']],
            ],
        ];

        $redacted = DebugRedactor::redact($data);

        $this->assertSame('bot', $redacted['user']);
        $this->assertSame('', $redacted['empty']['password']);
        $this->assertSame('id', $redacted['github']['client_id']);
        $this->assertSame(DebugRedactor::REDACTED, $redacted['github']['client_secret']);
        $this->assertSame('bot', $redacted['github']['accounts'][0]['username']);
        $this->assertSame(DebugRedactor::REDACTED, $redacted['github']['accounts'][0]['password']);
    }

    /**
     * Test that protected and private object properties are redacted and database drivers are not expanded.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testRedactObject(): void
    {
        $object = new class ($this->createMock(DatabaseDriver::class)) {
            public $gh_editbot_user    = 'bot';
            protected $gh_editbot_pass = 'hunter2';
            private $database;
            private $params;

            public function __construct($database)
            {
                $this->database = $database;
                $this->params   = new Registry(['token' => 'abc', 'timezone' => 'UTC']);
            }
        };

        $redacted = DebugRedactor::redact($object);

        $this->assertSame(\get_class($object), $redacted['__class']);
        $this->assertSame('bot', $redacted['gh_editbot_user']);
        $this->assertSame(DebugRedactor::REDACTED, $redacted['gh_editbot_pass']);
        $this->assertIsString($redacted['database']);
        $this->assertStringNotContainsString('hunter2', print_r($redacted, true));
        $this->assertSame(DebugRedactor::REDACTED, $redacted['params']['token']);
        $this->assertSame('UTC', $redacted['params']['timezone']);
    }

    /**
     * Test that recursive structures do not loop endlessly.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testRedactRecursion(): void
    {
        $object       = new \stdClass();
        $object->self = $object;

        $redacted = DebugRedactor::redact($object);

        $this->assertSame('[stdClass (recursion)]', $redacted['self']);
    }
}
