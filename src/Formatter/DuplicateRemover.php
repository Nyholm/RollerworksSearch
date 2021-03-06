<?php

/**
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Formatter;

use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\FormatterInterface;
use Rollerworks\Component\Search\SearchConditionInterface;
use Rollerworks\Component\Search\ValuesBag;
use Rollerworks\Component\Search\ValuesGroup;

/**
 * Removes duplicated values.
 *
 * Duplicated values are only scanned at single-level, so if a subgroup
 * has a value also present at a higher level its not removed.
 *
 *  Doing so would require to keep track of all the previous-values per-type.
 *  Which can get very complicated very fast.
 *
 * Values are compared using the {@see \Rollerworks\Component\Search\ValueComparisonInterface}.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class DuplicateRemover implements FormatterInterface
{
    /**
     * {@inheritdoc}
     */
    public function format(SearchConditionInterface $condition)
    {
        $fieldSet = $condition->getFieldSet();
        $valuesGroup = $condition->getValuesGroup();

        $this->removeDuplicatesInGroup($valuesGroup, $fieldSet);
    }

    /**
     * @param ValuesGroup $valuesGroup
     * @param FieldSet    $fieldSet
     */
    private function removeDuplicatesInGroup(ValuesGroup $valuesGroup, FieldSet $fieldSet)
    {
        foreach ($valuesGroup->getFields() as $fieldName => $values) {
            if (!$fieldSet->has($fieldName)) {
                continue;
            }

            $config = $fieldSet->get($fieldName);
            $this->removeDuplicatesInValuesBag($config, $values);
        }

        // now traverse the subgroups
        foreach ($valuesGroup->getGroups() as $group) {
            $this->removeDuplicatesInGroup($group, $fieldSet);
        }
    }

    /**
     * @param FieldConfigInterface $config
     * @param ValuesBag            $valuesBag
     */
    private function removeDuplicatesInValuesBag(FieldConfigInterface $config, ValuesBag $valuesBag)
    {
        $comparison = $config->getValueComparison();
        $options = $config->getOptions();

        if ($valuesBag->hasSingleValues()) {
            $singleValues = $valuesBag->getSingleValues();

            foreach ($singleValues as $i => $value) {
                if (!isset($singleValues[$i])) {
                    continue;
                }

                foreach ($singleValues as $c => $value2) {
                    if ($i === $c) {
                        continue;
                    }

                    if ($comparison->isEqual($value->getValue(), $value2->getValue(), $options)) {
                        $valuesBag->removeSingleValue($c);
                        unset($singleValues[$c]);
                    }
                }
            }

            unset($singleValues);
        }

        if ($valuesBag->hasExcludedValues()) {
            $excludedValues = $valuesBag->getExcludedValues();

            foreach ($excludedValues as $i => $value) {
                if (!isset($excludedValues[$i])) {
                    continue;
                }

                foreach ($excludedValues as $c => $value2) {
                    if ($i === $c) {
                        continue;
                    }

                    if ($comparison->isEqual($value->getValue(), $value2->getValue(), $options)) {
                        $valuesBag->removeExcludedValue($c);
                        unset($excludedValues[$c]);
                    }
                }
            }

            unset($excludedValues);
        }

        if ($valuesBag->hasRanges()) {
            $ranges = $valuesBag->getRanges();

            foreach ($ranges as $i => $value) {
                if (!isset($ranges[$i])) {
                    continue;
                }

                foreach ($ranges as $c => $value2) {
                    if ($i === $c) {
                        continue;
                    }

                    // Only compare when both inclusive are equal
                    if ($value->isLowerInclusive() !== $value2->isLowerInclusive() ||
                        $value->isUpperInclusive() !== $value2->isUpperInclusive()
                    ) {
                        continue;
                    }

                    if ($comparison->isEqual($value->getLower(), $value2->getLower(), $options) &&
                        $comparison->isEqual($value->getUpper(), $value2->getUpper(), $options)
                    ) {
                        $valuesBag->removeRange($c);
                        unset($ranges[$c]);
                    }
                }
            }

            unset($ranges);
        }

        if ($valuesBag->hasExcludedRanges()) {
            $ranges = $valuesBag->getExcludedRanges();

            foreach ($ranges as $i => $value) {
                foreach ($ranges as $c => $value2) {
                    if (!isset($ranges[$i])) {
                        continue;
                    }

                    if ($i === $c) {
                        continue;
                    }

                    // Only compare when both inclusive are equal
                    if ($value->isLowerInclusive() !== $value2->isLowerInclusive() ||
                        $value->isUpperInclusive() !== $value2->isUpperInclusive()
                    ) {
                        continue;
                    }

                    if ($comparison->isEqual($value->getLower(), $value2->getLower(), $options) &&
                        $comparison->isEqual($value->getUpper(), $value2->getUpper(), $options)
                    ) {
                        $valuesBag->removeExcludedRange($c);
                        unset($ranges[$c]);
                    }
                }
            }

            unset($ranges);
        }

        if ($valuesBag->hasComparisons()) {
            $comparisons = $valuesBag->getComparisons();

            foreach ($comparisons as $i => $value) {
                if (!isset($comparisons[$i])) {
                    continue;
                }

                foreach ($comparisons as $c => $value2) {
                    if ($i === $c) {
                        continue;
                    }

                    if ($value->getOperator() === $value2->getOperator() &&
                        $comparison->isEqual($value->getValue(), $value2->getValue(), $options)
                    ) {
                        $valuesBag->removeComparison($c);
                        unset($comparisons[$c]);
                    }
                }
            }

            unset($comparisons);
        }

        if ($valuesBag->hasPatternMatchers()) {
            $matchers = $valuesBag->getPatternMatchers();

            foreach ($matchers as $i => $value) {
                if (!isset($matchers[$i])) {
                    continue;
                }

                foreach ($matchers as $c => $value2) {
                    if ($i === $c) {
                        continue;
                    }

                    if ($value->getType() === $value2->getType() &&
                        $comparison->isEqual($value->getValue(), $value2->getValue(), $options) &&
                        $value->isCaseInsensitive() === $value2->isCaseInsensitive()
                    ) {
                        $valuesBag->removePatternMatch($c);
                        unset($matchers[$c]);
                    }
                }
            }

            unset($matchers);
        }
    }
}
