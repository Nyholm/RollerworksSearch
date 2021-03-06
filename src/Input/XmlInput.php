<?php

/**
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Input;

use Rollerworks\Component\Search\Exception\FieldRequiredException;
use Rollerworks\Component\Search\Exception\InputProcessorException;
use Rollerworks\Component\Search\Exception\ValuesOverflowException;
use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Util\XmlUtils;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\SingleValue;
use Rollerworks\Component\Search\ValuesBag;
use Rollerworks\Component\Search\ValuesGroup;

/**
 * XmlInput processes input provided as an XML document.
 *
 * See the XSD in schema/dic/input/xml-input-1.0.xsd for more information
 * about the schema.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class XmlInput extends AbstractInput
{
    /**
     * Process the input and returns the result.
     *
     * @param string $input
     *
     * @return null|SearchCondition Returns null on empty input
     */
    public function process($input)
    {
        $document = simplexml_import_dom(XmlUtils::parseXml($input, __DIR__ . '/schema/dic/input/xml-input-1.0.xsd'));

        $valuesGroup = new ValuesGroup();
        if (isset($document['logical']) && 'OR' === strtoupper((string) $document['logical'])) {
            $valuesGroup->setGroupLogical(ValuesGroup::GROUP_LOGICAL_OR);
        }

        $this->processGroup($document, $valuesGroup, 0, 0, true);

        return new SearchCondition($this->fieldSet, $valuesGroup);
    }

    /**
     * @param \SimpleXMLElement $values
     * @param ValuesGroup       $valuesGroup
     * @param int               $groupIdx
     * @param int               $level
     * @param bool              $isRoot
     *
     * @throws FieldRequiredException
     * @throws InputProcessorException
     */
    private function processGroup(\SimpleXMLElement $values, ValuesGroup $valuesGroup, $groupIdx = 0, $level = 0, $isRoot = false)
    {
        $this->validateGroupNesting($groupIdx, $level);
        $allFields = $this->fieldSet->all();

        if (!isset($values->fields) && !isset($values->groups)) {
            throw new InputProcessorException(
                sprintf('Empty group found in group %d at nesting level %d', $groupIdx, $level)
            );
        }

        if (isset($values->fields)) {
            foreach ($values->fields->children() as $element) {
                /** @var \SimpleXMLElement $element */
                $fieldName = $this->getFieldName((string) $element['name']);
                $filterConfig = $this->fieldSet->get($fieldName);

                if ($valuesGroup->hasField($fieldName)) {
                    $this->valuesToBag(
                        $filterConfig,
                        $element,
                        $fieldName,
                        $groupIdx,
                        $level,
                        $valuesGroup->getField($fieldName)
                    );
                } else {
                    $valuesGroup->addField(
                        $fieldName,
                        $this->valuesToBag($filterConfig, $element, $fieldName, $groupIdx, $level)
                    );
                }

                unset($allFields[$fieldName]);
            }
        }

        // Now run trough all the remaining fields and look if there are required
        // Fields that were set without values have already been checked by valuesToBag()
        foreach ($allFields as $fieldName => $filterConfig) {
            if ($filterConfig->isRequired()) {
                throw new FieldRequiredException($fieldName, $groupIdx, $level);
            }
        }

        if (isset($values->groups)) {
            $this->validateGroupsCount($this->maxGroups, $values->groups->children()->count(), $level);

            $index = 0;
            foreach ($values->groups->children() as $element) {
                $subValuesGroup = new ValuesGroup();

                if (isset($element['logical']) && 'OR' === strtoupper($element['logical'])) {
                    $subValuesGroup->setGroupLogical(ValuesGroup::GROUP_LOGICAL_OR);
                }

                $this->processGroup(
                    $element,
                    $subValuesGroup,
                    $index,
                    ($isRoot ? 0 : $level+1)
                );

                $valuesGroup->addGroup($subValuesGroup);
                $index++;
            }
        }
    }

    /**
     * Converts the values list to an FilterValuesBag object.
     *
     * @param FieldConfigInterface $fieldConfig
     * @param \SimpleXMLElement    $values
     * @param string               $fieldName
     * @param int                  $groupIdx
     * @param int                  $level
     * @param ValuesBag|null       $valuesBag
     *
     * @return ValuesBag
     *
     * @throws FieldRequiredException
     * @throws ValuesOverflowException
     */
    private function valuesToBag(FieldConfigInterface $fieldConfig, \SimpleXMLElement $values, $fieldName, $groupIdx, $level = 0, ValuesBag $valuesBag = null)
    {
        if (isset($values->comparisons)) {
            $this->assertAcceptsType('comparison', $fieldName);
        }

        if (isset($values->ranges) || isset($values->{'excluded-ranges'})) {
            $this->assertAcceptsType('range', $fieldName);
        }

        if (isset($values->{'pattern-matchers'})) {
            $this->assertAcceptsType('pattern-match', $fieldName);
        }

        if (!$valuesBag) {
            $valuesBag = new ValuesBag();
        }

        $count = $valuesBag->count();

        if (isset($values->{'single-values'})) {
            foreach ($values->{'single-values'}->children() as $value) {
                if ($count > $this->maxValues) {
                    throw new ValuesOverflowException($fieldName, $this->maxValues, $count, $groupIdx, $level);
                }
                $count++;

                $valuesBag->addSingleValue(new SingleValue((string) $value));
            }
        }

        if (isset($values->{'excluded-values'})) {
            foreach ($values->{'excluded-values'}->children() as $value) {
                if ($count > $this->maxValues) {
                    throw new ValuesOverflowException($fieldName, $this->maxValues, $count, $groupIdx, $level);
                }
                $count++;

                $valuesBag->addExcludedValue(new SingleValue((string) $value));
            }
        }

        if (isset($values->comparisons)) {
            foreach ($values->comparisons->children() as $comparison) {
                if ($count > $this->maxValues) {
                    throw new ValuesOverflowException($fieldName, $this->maxValues, $count, $groupIdx, $level);
                }
                $count++;

                $valuesBag->addComparison(new Compare((string) $comparison, (string) $comparison['operator']));
            }
        }

        if (isset($values->ranges)) {
            foreach ($values->ranges->children() as $range) {
                if ($count > $this->maxValues) {
                    throw new ValuesOverflowException($fieldName, $this->maxValues, $count, $groupIdx, $level);
                }
                $count++;

                $valuesBag->addRange(
                    new Range(
                        (string) $range->lower,
                        (string) $range->upper,
                        'false' !== strtolower($range->lower['inclusive']),
                        'false' !== strtolower($range->upper['inclusive'])
                    )
                );
            }
        }

        if (isset($values->{'excluded-ranges'})) {
            foreach ($values->{'excluded-ranges'}->children() as $range) {
                if ($count > $this->maxValues) {
                    throw new ValuesOverflowException($fieldName, $this->maxValues, $count, $groupIdx, $level);
                }
                $count++;

                $valuesBag->addExcludedRange(
                    new Range(
                        (string) $range->lower,
                        (string) $range->upper,
                        'false' !== strtolower($range->lower['inclusive']),
                        'false' !== strtolower($range->upper['inclusive'])
                    )
                );
            }
        }

        if (isset($values->{'pattern-matchers'})) {
            $this->assertAcceptsType('pattern-match', $fieldName);

            foreach ($values->{'pattern-matchers'}->children() as $patternMatch) {
                if ($count > $this->maxValues) {
                    throw new ValuesOverflowException($fieldName, $this->maxValues, $count, $groupIdx, $level);
                }
                $count++;

                $valuesBag->addPatternMatch(
                    new PatternMatch(
                        (string) $patternMatch,
                        (string) $patternMatch['type'],
                        'true' === strtolower($patternMatch['case-insensitive'])
                    )
                );
            }
        }

        if (0 === $count && $fieldConfig->isRequired()) {
            throw new FieldRequiredException($fieldName, $groupIdx, $level);
        }

        return $valuesBag;
    }
}
