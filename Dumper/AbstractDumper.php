<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\RecordFilterBundle\Dumper;

use Rollerworks\RecordFilterBundle\Value\FilterValuesBag;
use Rollerworks\RecordFilterBundle\Formatter\FormatterInterface;
use Rollerworks\RecordFilterBundle\Type\FilterTypeInterface;

/**
 * AbstractDumper.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
abstract class AbstractDumper implements DumperInterface
{
    /**
     * Return the array version of an FilterStruct object.
     *
     * The order is: singe-values, single-value-excludes, ranges, excluded-ranges, compares.
     *
     * Ranges will always have both-side values quoted like "lower"-"higher".
     * Single values are only quoted depending on $quoteLooseValue
     *
     * @param FormatterInterface $formatter
     * @param string             $fieldName
     * @param FilterValuesBag    $filter
     * @param boolean            $quoteLooseValue
     *
     * @return array
     */
    static protected function filterStructToArray(FormatterInterface $formatter, $fieldName, FilterValuesBag $filter, $quoteLooseValue = false)
    {
        $type = $formatter->getFieldSet()->get($fieldName)->getType();
        $filters = array();

        foreach ($filter->getSingleValues() as $value) {
            $value = self::dumpValue($type, $value->getValue());

            if ($quoteLooseValue) {
                $value = self::quoteValue($value);
            }

            $filters[] = $value;
        }

        foreach ($filter->getExcludes() as $value) {
            $value = self::dumpValue($type, $value->getValue());

            if ($quoteLooseValue) {
                $value = self::quoteValue($value);
            }

            $filters[] = '!' . $value;
        }

        foreach ($filter->getRanges() as $range) {
            $filters[] = self::quoteValue(self::dumpValue($type, $range->getLower())) . '-' . self::quoteValue(self::dumpValue($type, $range->getUpper()));
        }

        foreach ($filter->getExcludedRanges() as $range) {
            $filters[] = '!' . self::quoteValue(self::dumpValue($type, $range->getLower())) . '-' . self::quoteValue(self::dumpValue($type, $range->getUpper()));
        }

        foreach ($filter->getCompares() as $value) {
            $filters[] = $value->getOperator() . self::dumpValue($type, $value->getValue());
        }

        return $filters;
    }

    /**
     * @param FilterTypeInterface $type
     * @param string              $value
     *
     * @return string
     */
    static public function dumpValue(FilterTypeInterface $type = null, $value)
    {
        if ($type) {
            $value = $type->dumpValue($value);
        }

        return (string) $value;
    }

    /**
     * Quote an value and escape the quotes
     *
     * @param string $input
     *
     * @return string
     */
    static protected function quoteValue($input)
    {
        return '"' . str_replace('"', '""', $input) . '"';
    }
}
