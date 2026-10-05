<?php

declare(strict_types=1);

namespace ampf\Kit\Testing\Guard;

use Generator;
use PhpToken;

/**
 * What the guards read in the code of a PHP file by its tokens, so that a comment says nothing of the code: the
 * attributes that a class carries, and whether the code hands a class out as a class-string, which is how an entity gets
 * to the entity manager and into a query.
 *
 * @internal
 */
class SourceCode
{
    /** The tokens that name a class, a function or a constant. */
    private const array NAMES = [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED, T_NAME_RELATIVE];

    /** The tokens of a string, in which a query may name an entity. */
    private const array STRINGS = [T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE];

    /**
     * The names of the attributes in the code as they are written (`ORM\Entity`), in the order of the code.
     *
     * @return list<string>
     */
    public static function attributeNames(string $code): array
    {
        $names = [];

        foreach (self::tokens($code) as [$token, $depth]) {
            if ($depth === 1 && $token->is(self::NAMES)) {
                $names[] = $token->text;
            }
        }

        return $names;
    }

    /**
     * Whether the code, outside its attributes, hands the class out as a class-string: `Name::class`, whatever namespace the
     * code writes before the name, or a string that holds the name (a query). The class is given with its namespace or
     * without.
     */
    public static function handsOut(string $code, string $class): bool
    {
        $short = self::shortName($class);
        $outside = [];

        foreach (self::tokens($code) as [$token, $depth]) {
            if ($depth === 0) {
                $outside[] = $token;
            }
        }

        foreach ($outside as $position => $token) {
            $isClassConstant = $token->is(self::NAMES)
                && self::shortName($token->text) === $short
                && ($outside[$position + 1] ?? null)?->is(T_DOUBLE_COLON) === true
                && ($outside[$position + 2] ?? null)?->is(T_CLASS) === true;
            // The name of a class has no character that a pattern reads
            $isQuery = $token->is(self::STRINGS) && preg_match('/\b' . $short . '\b/', $token->text) === 1;

            if ($isClassConstant || $isQuery) {
                return true;
            }
        }

        return false;
    }

    /** The last part of a name: `Entity` of `ORM\Entity`. */
    public static function shortName(string $name): string
    {
        return array_last(explode('\\', $name));
    }

    /**
     * The tokens that are code (no white space, no comment), each with how deep it stands in an attribute: 0 outside one,
     * 1 directly in the `#[ ]` of an attribute, where its name is, and more in its arguments.
     *
     * @return Generator<int, array{PhpToken, int}>
     */
    private static function tokens(string $code): Generator
    {
        $depth = 0;

        foreach (PhpToken::tokenize($code) as $token) {
            if ($token->isIgnorable()) {
                continue;
            }

            if ($token->is(T_ATTRIBUTE) || ($depth > 0 && $token->is(['(', '[']))) {
                $depth++;
            } elseif ($depth > 0 && $token->is([')', ']'])) {
                $depth--;
            }

            yield [$token, $depth];
        }
    }
}
