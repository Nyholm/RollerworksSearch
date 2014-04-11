<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search\Doctrine\Orm;

use Rollerworks\Component\Search\Doctrine\Dbal\QueryGenerator;
use Rollerworks\Component\Search\Doctrine\Dbal\SqlFieldConversionInterface;
use Rollerworks\Component\Search\Doctrine\Dbal\SqlValueConversionInterface;
use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\Value\PatternMatch;

class DqlQueryGenerator extends QueryGenerator
{
    /**
     * {@inheritdoc}
     */
    protected function getPatternMatcher(PatternMatch $patternMatch, $column, $value)
    {
        // Doctrine at the moment does not support case insensitive LIKE or regex match
        // So we use a custom function for this

        $patternType = array(
            PatternMatch::PATTERN_STARTS_WITH => 'starts_with',
            PatternMatch::PATTERN_NOT_STARTS_WITH => 'starts_with',

            PatternMatch::PATTERN_CONTAINS => 'contains',
            PatternMatch::PATTERN_NOT_CONTAINS => 'contains',

            PatternMatch::PATTERN_ENDS_WITH => 'ends_with',
            PatternMatch::PATTERN_NOT_ENDS_WITH => 'ends_with',

            PatternMatch::PATTERN_REGEX => 'regex',
            PatternMatch::PATTERN_NOT_REGEX => 'regex',
        );

        return sprintf(
            "RW_SEARCH_MATCH(%s, %s, '%s', %s) %s 1",
            $column,
            $value,
            $patternType[$patternMatch->getType()],
            $patternMatch->isCaseInsensitive() ? 'true' : 'false',
            $patternMatch->isExclusive() ? '<>' : '='
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function convertSqlValue(SqlValueConversionInterface $converter, $fieldName, $column, $value, $convertedValue, FieldConfigInterface $field, array $hints, $strategy)
    {
        // If the value requires embedding we inform the DQL function about the parameter-index
        // Where he can then find the value, else its not possible to embed the value safely
        $valueRequiresEmbedding = $converter->valueRequiresEmbedding($value, $field->getOptions(), $hints);

        $paramName = $this->getUniqueParameterName($fieldName);
        $this->parameters[$paramName] = $convertedValue;
        $convertedValue = ':'.$paramName;

        return "RW_SEARCH_VALUE_CONVERSION('$fieldName', ".$this->fields[$fieldName]['column'].", $convertedValue, ".(null === $strategy ? 'null' : $strategy).", ".($valueRequiresEmbedding ? 'true' : 'false').")";
    }

    /**
     * {@inheritdoc}
     */
    protected function getFieldColumn($fieldName, $strategy = null)
    {
        if (isset($this->fieldsMappingCache[$fieldName]) && array_key_exists($strategy, $this->fieldsMappingCache[$fieldName])) {
            return $this->fieldsMappingCache[$fieldName][$strategy];
        }

        if (!isset($this->fieldsMappingCache[$fieldName])) {
            $this->fieldsMappingCache[$fieldName] = array();
        }

        if ($this->fields[$fieldName]['field_convertor'] instanceof SqlFieldConversionInterface) {
            $this->fieldsMappingCache[$fieldName][$strategy] = "RW_SEARCH_FIELD_CONVERSION('$fieldName', ".$this->fields[$fieldName]['column'].", ".(null === $strategy ? 'null' : $strategy).")";
        } else {
            $this->fieldsMappingCache[$fieldName][$strategy] = $this->fields[$fieldName]['column'];
        }

        return $this->fieldsMappingCache[$fieldName][$strategy];
    }
}
