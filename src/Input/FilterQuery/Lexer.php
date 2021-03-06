<?php

/**
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Input\FilterQuery;

/**
 * Scans a Query for tokens.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 * @author Doctrine-Project <http://www.doctrine-project.org>
 */
class Lexer extends AbstractLexer
{
    // All tokens that are not valid identifiers must be < 100
    const T_NONE = 1;
    const T_INTEGER = 2;
    const T_STRING = 3;
    const T_INPUT_PARAMETER = 4;
    const T_FLOAT = 5;
    const T_CLOSE_PARENTHESIS = 6;
    const T_OPEN_PARENTHESIS = 7;
    const T_COMMA = 8;
    const T_DIVIDE = 9;
    const T_DOT = 10;
    const T_EQUALS = 11;
    const T_GREATER_THAN = 12;
    const T_LOWER_THAN = 13;
    const T_MINUS = 14;
    const T_MULTIPLY = 15;
    const T_NEGATE = 16;
    const T_PLUS = 17;
    const T_OPEN_CURLY_BRACE = 18;
    const T_CLOSE_CURLY_BRACE = 19;
    const T_SEMICOLON = 20;
    const T_TILDE = 21;
    const T_COLON = 22;
    const T_OPEN_BRACE = 23;
    const T_CLOSE_BRACE = 24;
    const T_QUESTION_MARK = 25;
    const T_AND = 26;

    // All tokens that are also identifiers should be >= 100
    const T_IDENTIFIER = 100;

    /**
     * Creates a new query scanner object.
     *
     * @param string $input A query string.
     */
    public function __construct($input = null)
    {
        if (null !== $input) {
            $this->setInput($input);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getCatchablePatterns()
    {
        return array(
            '\p{L}[\p{L}\p{N}_-]*:', // field-name, unicode
            '\p{L}+\p{N}*', // string literal
            '(?:\p{N}+(?:[\.]\p{N}+)*)', // numbers (no negatives)
            '"(?:[^"]|"")*"', // quoted string
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getNonCatchablePatterns()
    {
        return array('\s+', '(.)');
    }

    /**
     * {@inheritdoc}
     */
    protected function getType(&$value)
    {
        $type = self::T_NONE;

        switch (true) {
            // Recognize numeric values
            case (is_numeric($value)):
                if (strpos($value, '.') !== false) {
                    return self::T_FLOAT;
                }

                return self::T_INTEGER;

            // Recognize quoted strings
            case ($value[0] === '"'):
                $value = str_replace('""', '"', substr($value, 1, strlen($value) - 2));

                return self::T_STRING;

            // Recognize identifiers
            // identifiers are suffixed with ':' to distinct them from strings literals
            case (preg_match('/^\p{L}[\p{L}\p{N}_-]*:$/ui', $value)):
                $value = substr($value, 0, strlen($value) -1);

                return self::T_IDENTIFIER;

            // Recognize strings literals
            case (preg_match('/^\p{L}+\p{N}*$/ui', $value) > 0):
                return self::T_STRING;

            // Recognize symbols
            // @codingStandardsIgnoreStart

            case ($value === ','): return self::T_COMMA;
            case ($value === '('): return self::T_OPEN_PARENTHESIS;
            case ($value === ')'): return self::T_CLOSE_PARENTHESIS;
            case ($value === '='): return self::T_EQUALS;
            case ($value === '>'): return self::T_GREATER_THAN;
            case ($value === '<'): return self::T_LOWER_THAN;
            case ($value === '+'): return self::T_PLUS;
            case ($value === '-'): return self::T_MINUS;
            case ($value === '*'): return self::T_MULTIPLY;
            case ($value === '/'): return self::T_DIVIDE;
            case ($value === '!'): return self::T_NEGATE;
            case ($value === ';'): return self::T_SEMICOLON;
            case ($value === ':'): return self::T_COLON;
            case ($value === '~'): return self::T_TILDE;
            case ($value === '['): return self::T_OPEN_BRACE;
            case ($value === ']'): return self::T_CLOSE_BRACE;
            case ($value === '?'): return self::T_QUESTION_MARK;
            case ($value === '&'): return self::T_AND;

            // Reserved for future usage
            case ($value === '.'): return self::T_DOT;
            case ($value === '{'): return self::T_OPEN_CURLY_BRACE;
            case ($value === '}'): return self::T_CLOSE_CURLY_BRACE;
            // @codingStandardsIgnoreEnd

            // Default
            default:
                // Do nothing
        }

        return $type;
    }
}
