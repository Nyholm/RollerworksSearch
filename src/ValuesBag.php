<?php

/**
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search;

use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\SingleValue;

/**
 * ValuesBag holds all the values per-type.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ValuesBag implements \Countable, \Serializable
{
    protected $excludedValues = array();
    protected $ranges = array();
    protected $excludedRanges = array();
    protected $comparisons = array();
    protected $singleValues = array();
    protected $patternMatchers = array();
    protected $valuesCount = 0;

    /**
     * @var ValuesError[]
     */
    protected $errors = array();

    /**
     * @return SingleValue[]
     */
    public function getSingleValues()
    {
        return $this->singleValues;
    }

    /**
     * @param SingleValue $value
     *
     * @return static
     */
    public function addSingleValue(SingleValue $value)
    {
        $this->singleValues[] = $value;
        $this->valuesCount++;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasSingleValues()
    {
        return !empty($this->singleValues);
    }

    /**
     * @param int $index
     *
     * @return static
     */
    public function removeSingleValue($index)
    {
        if (isset($this->singleValues[$index])) {
            unset($this->singleValues[$index]);

            $this->valuesCount--;
        }

        return $this;
    }

    /**
     * @param SingleValue $value
     *
     * @return static
     */
    public function addExcludedValue(SingleValue $value)
    {
        $this->excludedValues[] = $value;
        $this->valuesCount++;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasExcludedValues()
    {
        return !empty($this->excludedValues);
    }

    /**
     * @return SingleValue[]
     */
    public function getExcludedValues()
    {
        return $this->excludedValues;
    }

    /**
     * @param int $index
     *
     * @return static
     */
    public function removeExcludedValue($index)
    {
        if (isset($this->excludedValues[$index])) {
            unset($this->excludedValues[$index]);

            $this->valuesCount--;
        }

        return $this;
    }

    /**
     * @param Range $range
     *
     * @return static
     */
    public function addRange(Range $range)
    {
        $this->ranges[] = $range;
        $this->valuesCount++;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasRanges()
    {
        return count($this->ranges) > 0;
    }

    /**
     * @return Range[]
     */
    public function getRanges()
    {
        return $this->ranges;
    }

    /**
     * @param int $index
     *
     * @return static
     */
    public function removeRange($index)
    {
        if (isset($this->ranges[$index])) {
            unset($this->ranges[$index]);

            $this->valuesCount--;
        }

        return $this;
    }

    /**
     * @param Range $range
     *
     * @return static
     */
    public function addExcludedRange(Range $range)
    {
        $this->excludedRanges[] = $range;
        $this->valuesCount++;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasExcludedRanges()
    {
        return !empty($this->excludedRanges);
    }

    /**
     * @return Range[]
     */
    public function getExcludedRanges()
    {
        return $this->excludedRanges;
    }

    /**
     * @param int $index
     *
     * @return static
     */
    public function removeExcludedRange($index)
    {
        if (isset($this->excludedRanges[$index])) {
            unset($this->excludedRanges[$index]);

            $this->valuesCount--;
        }

        return $this;
    }

    /**
     * @param Compare $value
     *
     * @return static
     */
    public function addComparison(Compare $value)
    {
        $this->comparisons[] = $value;
        $this->valuesCount++;

        return $this;
    }

    /**
     * @return Compare[]
     */
    public function getComparisons()
    {
        return $this->comparisons;
    }

    /**
     * @return bool
     */
    public function hasComparisons()
    {
        return !empty($this->comparisons);
    }

    /**
     * @param int $index
     *
     * @return static
     */
    public function removeComparison($index)
    {
        if (isset($this->comparisons[$index])) {
            unset($this->comparisons[$index]);

            $this->valuesCount--;
        }

        return $this;
    }

    /**
     * @return PatternMatch[]
     */
    public function getPatternMatchers()
    {
        return $this->patternMatchers;
    }

    /**
     * @param PatternMatch $value
     *
     * @return static
     */
    public function addPatternMatch(PatternMatch $value)
    {
        $this->patternMatchers[] = $value;
        $this->valuesCount++;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasPatternMatchers()
    {
        return !empty($this->patternMatchers);
    }

    /**
     * @param int $index
     *
     * @return static
     */
    public function removePatternMatch($index)
    {
        if (isset($this->patternMatchers[$index])) {
            unset($this->patternMatchers[$index]);

            $this->valuesCount--;
        }

        return $this;
    }

    /**
     * @param ValuesError $error
     *
     * @return static
     */
    public function addError(ValuesError $error)
    {
        $this->errors[] = $error;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasErrors()
    {
        return !empty($this->errors);
    }

    /**
     * @return ValuesError[]
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @return int
     */
    public function count()
    {
        return $this->valuesCount;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize(array(
            $this->excludedValues,
            $this->ranges,
            $this->excludedRanges,
            $this->comparisons,
            $this->singleValues,
            $this->patternMatchers,
            $this->valuesCount,
            $this->errors
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);

        list(
            $this->excludedValues,
            $this->ranges,
            $this->excludedRanges,
            $this->comparisons,
            $this->singleValues,
            $this->patternMatchers,
            $this->valuesCount,
            $this->errors
        ) = $data;
    }
}
