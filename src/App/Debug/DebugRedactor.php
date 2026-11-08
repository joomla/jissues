<?php

/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Debug;

use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\Registry\Registry;

/**
 * Helper to remove sensitive values (passwords, tokens, secrets) from data before it is dumped for debugging.
 *
 * @since  1.0
 */
abstract class DebugRedactor
{
    /**
     * Replacement for redacted values.
     *
     * @var    string
     * @since  1.0
     */
    public const REDACTED = '*** redacted ***';

    /**
     * Pattern matching the names of sensitive keys.
     *
     * @var    string
     * @since  1.0
     */
    private const SENSITIVE_KEY_PATTERN = '/pass|secret|token|credential|private_?key|api_?key|auth_pw|authorization|cookie/i';

    /**
     * Maximum nesting level to process.
     *
     * @var    integer
     * @since  1.0
     */
    private const MAX_DEPTH = 10;

    /**
     * Check if a key name denotes a sensitive value.
     *
     * @param   string|integer  $key  The key name.
     *
     * @return  boolean
     *
     * @since   1.0
     */
    public static function isSensitiveKey($key): bool
    {
        return \is_string($key) && preg_match(self::SENSITIVE_KEY_PATTERN, $key) === 1;
    }

    /**
     * Get a copy of a value with all sensitive values redacted.
     *
     * Objects are converted to arrays (with their class name in the "__class" key) so their protected and private
     * properties can be redacted too. Database drivers and DI containers are not expanded, as they contain the
     * credentials of the whole application.
     *
     * @param   mixed  $value  The value to redact.
     *
     * @return  mixed
     *
     * @since   1.0
     */
    public static function redact($value)
    {
        return self::redactValue($value, 0, []);
    }

    /**
     * Recursively redact a value.
     *
     * @param   mixed    $value    The value to redact.
     * @param   integer  $depth    The current nesting level.
     * @param   array    $visited  Hashes of the objects already being processed (to prevent endless recursion).
     *
     * @return  mixed
     *
     * @since   1.0
     */
    private static function redactValue($value, int $depth, array $visited)
    {
        if (\is_object($value)) {
            $class = \get_class($value);

            if ($value instanceof DatabaseInterface || $value instanceof Container) {
                return '[' . $class . ']';
            }

            $hash = spl_object_hash($value);

            if ($depth >= self::MAX_DEPTH || isset($visited[$hash])) {
                return '[' . $class . ' (recursion)]';
            }

            $visited[$hash] = true;

            if ($value instanceof Registry) {
                $properties = $value->toArray();
            } else {
                $properties = [];

                foreach ((array) $value as $name => $property) {
                    // Protected and private properties are prefixed with "\0*\0" or "\0ClassName\0"
                    $name              = (string) $name;
                    $position          = strrpos($name, "\0");
                    $name              = $position === false ? $name : substr($name, $position + 1);
                    $properties[$name] = $property;
                }
            }

            return ['__class' => $class] + self::redactArray($properties, $depth, $visited);
        }

        if (\is_array($value)) {
            if ($depth >= self::MAX_DEPTH) {
                return '[array (recursion)]';
            }

            return self::redactArray($value, $depth, $visited);
        }

        return $value;
    }

    /**
     * Redact the values of an array.
     *
     * @param   array    $values   The values to redact.
     * @param   integer  $depth    The current nesting level.
     * @param   array    $visited  Hashes of the objects already being processed.
     *
     * @return  array
     *
     * @since   1.0
     */
    private static function redactArray(array $values, int $depth, array $visited): array
    {
        $result = [];

        foreach ($values as $key => $item) {
            if (self::isSensitiveKey($key) && $item !== null && $item !== '') {
                $result[$key] = self::REDACTED;

                continue;
            }

            $result[$key] = self::redactValue($item, $depth + 1, $visited);
        }

        return $result;
    }
}
