<?php

/**
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace spec\Rollerworks\Component\Search\Input;

use PhpSpec\ObjectBehavior;
use Rollerworks\Component\Search\Exception\FieldRequiredException;
use Rollerworks\Component\Search\Exception\GroupsNestingException;
use Rollerworks\Component\Search\Exception\GroupsOverflowException;
use Rollerworks\Component\Search\Exception\UnknownFieldException;
use Rollerworks\Component\Search\Exception\UnsupportedValueTypeException;
use Rollerworks\Component\Search\Exception\ValuesOverflowException;
use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\Input\FilterQuery\QueryException;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\SingleValue;
use Rollerworks\Component\Search\ValuesBag;
use Rollerworks\Component\Search\ValuesGroup;

class FilterQueryInputSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('Rollerworks\Component\Search\Input\FilterQueryInput');
        $this->shouldImplement('Rollerworks\Component\Search\InputProcessorInterface');
    }

    public function it_returns_null_on_empty_input()
    {
        $this->process('')->shouldReturn(null);
        $this->process(' ')->shouldReturn(null);
        $this->process(null)->shouldReturn(null);
    }

    public function it_parses_a_single_query_pair(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);
        $fieldSet->all()->willReturn(array('field1' => $field));

        $values = new ValuesBag();
        $values->addSingleValue(new SingleValue('value'));
        $values->addSingleValue(new SingleValue('value2'));

        $expectedGroup = new ValuesGroup();
        $expectedGroup->addField('field1', $values);

        $condition = new SearchCondition($fieldSet->getWrappedObject(), $expectedGroup);

        $this->setFieldSet($fieldSet);
        $this->process('field1: value, value2;')->shouldBeLike($condition);
        $this->process('field1: value, value2')->shouldBeLike($condition);
    }

    // this is a special case as the dash is also used for ranges
    public function it_parses_field_with_dash(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $fieldSet->has('field-1')->willReturn(true);
        $fieldSet->get('field-1')->willReturn($field);
        $fieldSet->all()->willReturn(array('field-1' => $field));

        $values = new ValuesBag();
        $values->addSingleValue(new SingleValue('value'));
        $values->addSingleValue(new SingleValue('value2'));

        $expectedGroup = new ValuesGroup();
        $expectedGroup->addField('field-1', $values);

        $condition = new SearchCondition($fieldSet->getWrappedObject(), $expectedGroup);

        $this->setFieldSet($fieldSet);
        $this->process('field-1: value, value2')->shouldBeLike($condition);
    }

    public function it_parses_multiple_query_pairs(FieldSet $fieldSet, FieldConfigInterface $field, FieldConfigInterface $field2)
    {
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->has('field2')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);
        $fieldSet->get('field2')->willReturn($field2);
        $fieldSet->all()->willReturn(array('field1' => $field, 'field2' => $field2));

        $expectedGroup = new ValuesGroup();

        $values = new ValuesBag();
        $values->addSingleValue(new SingleValue('value'));
        $values->addSingleValue(new SingleValue('value2'));
        $expectedGroup->addField('field1', $values);

        $values = new ValuesBag();
        $values->addSingleValue(new SingleValue('value3'));
        $values->addSingleValue(new SingleValue('value4'));
        $expectedGroup->addField('field2', $values);

        $condition = new SearchCondition($fieldSet->getWrappedObject(), $expectedGroup);

        $this->setFieldSet($fieldSet);
        $this->process('field1: value, value2; field2: value3, value4;')->shouldBeLike($condition);
        $this->process('field1: value, value2; field2: value3, value4')->shouldBeLike($condition);
    }

    public function it_parses_a_quoted_value(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);
        $fieldSet->all()->willReturn(array('field1' => $field));

        $values = new ValuesBag();
        $values->addSingleValue(new SingleValue('value'));
        $values->addSingleValue(new SingleValue('value"2'));

        $expectedGroup = new ValuesGroup();
        $expectedGroup->addField('field1', $values);

        $condition = new SearchCondition($fieldSet->getWrappedObject(), $expectedGroup);

        $this->setFieldSet($fieldSet);
        $this->process('field1: "value", "value""2";')->shouldBeLike($condition);
    }

    public function it_parses_excluded_singleValues(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);
        $fieldSet->all()->willReturn(array('field1' => $field));

        $values = new ValuesBag();
        $values->addExcludedValue(new SingleValue('value'));
        $values->addSingleValue(new SingleValue('value2'));

        $expectedGroup = new ValuesGroup();
        $expectedGroup->addField('field1', $values);

        $condition = new SearchCondition($fieldSet->getWrappedObject(), $expectedGroup);

        $this->setFieldSet($fieldSet);
        $this->process('field1: !value, value2;')->shouldBeLike($condition);
    }

    public function it_parses_simple_range_values(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $field->acceptRanges()->willReturn(true);
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);
        $fieldSet->all()->willReturn(array('field1' => $field));

        $values = new ValuesBag();
        $values->addRange(new Range('1', '10'));
        $values->addRange(new Range('15', '30'));

        $expectedGroup = new ValuesGroup();
        $expectedGroup->addField('field1', $values);

        $condition = new SearchCondition($fieldSet->getWrappedObject(), $expectedGroup);

        $this->setFieldSet($fieldSet);
        $this->process('field1: 1-10, 15 - 30;')->shouldBeLike($condition);
    }

    public function it_parses_exclusive_range_values(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $field->acceptRanges()->willReturn(true);
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);
        $fieldSet->all()->willReturn(array('field1' => $field));

        $values = new ValuesBag();
        $values->addRange(new Range('1', '10', true, false));
        $values->addRange(new Range('15', '30', false));

        $expectedGroup = new ValuesGroup();
        $expectedGroup->addField('field1', $values);

        $condition = new SearchCondition($fieldSet->getWrappedObject(), $expectedGroup);

        $this->setFieldSet($fieldSet);
        $this->process('field1: 1-10[ , ]15 - 30;')->shouldBeLike($condition);
    }

    public function it_parses_excluded_range_values(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $field->acceptRanges()->willReturn(true);
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);
        $fieldSet->all()->willReturn(array('field1' => $field));

        $values = new ValuesBag();
        $values->addExcludedRange(new Range('1', '10'));
        $values->addRange(new Range('15', '30'));

        $expectedGroup = new ValuesGroup();
        $expectedGroup->addField('field1', $values);

        $condition = new SearchCondition($fieldSet->getWrappedObject(), $expectedGroup);

        $this->setFieldSet($fieldSet);
        $this->process('field1: !1-10, 15 - 30;')->shouldBeLike($condition);
    }

    public function it_parses_comparisons(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $fieldSet->has('field1')->willReturn(true);

        $field->acceptCompares()->willReturn(true);
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);
        $fieldSet->all()->willReturn(array('field1' => $field));

        $values = new ValuesBag();
        $values->addComparison(new Compare('value', '>'));
        $values->addComparison(new Compare('value2', '<='));
        $values->addComparison(new Compare('value3', '>='));

        $expectedGroup = new ValuesGroup();
        $expectedGroup->addField('field1', $values);

        $condition = new SearchCondition($fieldSet->getWrappedObject(), $expectedGroup);

        $this->setFieldSet($fieldSet);
        $this->process('field1: > value, <= value2, >= value3;')->shouldBeLike($condition);
    }

    public function it_parses_matchers(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $field->acceptCompares()->willReturn(true);
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);
        $fieldSet->all()->willReturn(array('field1' => $field));

        $values = new ValuesBag();
        $values->addPatternMatch(new PatternMatch('value', PatternMatch::PATTERN_CONTAINS));
        $values->addPatternMatch(new PatternMatch('value2', PatternMatch::PATTERN_STARTS_WITH, true));
        $values->addPatternMatch(new PatternMatch('value3', PatternMatch::PATTERN_ENDS_WITH));
        $values->addPatternMatch(new PatternMatch('^foo|bar?', PatternMatch::PATTERN_REGEX));
        $values->addPatternMatch(new PatternMatch('value4', PatternMatch::PATTERN_NOT_CONTAINS));
        $values->addPatternMatch(new PatternMatch('value5', PatternMatch::PATTERN_NOT_CONTAINS, true));

        $expectedGroup = new ValuesGroup();
        $expectedGroup->addField('field1', $values);

        $condition = new SearchCondition($fieldSet->getWrappedObject(), $expectedGroup);

        $this->setFieldSet($fieldSet);
        $this->process('field1: ~* value, ~i> value2, ~< value3, ~? "^foo|bar?", ~!* value4, ~i!* value5;')->shouldBeLike($condition);
    }

    public function it_parses_groups(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->all()->willReturn(array('field1' => $field));

        $rootGroup = new ValuesGroup();

        $values = new ValuesBag();
        $values->addSingleValue(new SingleValue('value'));
        $values->addSingleValue(new SingleValue('value2'));
        $rootGroup->addField('field1', $values);

        $values = new ValuesBag();
        $values->addSingleValue(new SingleValue('value3'));
        $values->addSingleValue(new SingleValue('value4'));

        $subGroup = new ValuesGroup();
        $subGroup->addField('field1', $values);
        $rootGroup->addGroup($subGroup);

        $condition = new SearchCondition($fieldSet->getWrappedObject(), $rootGroup);

        $this->setFieldSet($fieldSet);
        $this->process('field1: value, value2; (field1: value3, value4;);')->shouldBeLike($condition);
        $this->process('field1: value, value2; (field1: value3, value4);')->shouldBeLike($condition);
        $this->process('field1: value, value2; (field1: value3, value4)')->shouldBeLike($condition);
        $this->process('(field1: value3, value4;); field1: value, value2;')->shouldBeLike($condition);
        $this->process('(field1: value3, value4); field1: value, value2;')->shouldBeLike($condition);
        $this->process('(field1: value3, value4); field1: value, value2')->shouldBeLike($condition);
    }

    public function it_parses_logical_groups(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->all()->willReturn(array('field1' => $field));

        $rootGroup = new ValuesGroup();

        $values = new ValuesBag();
        $values->addSingleValue(new SingleValue('value'));
        $values->addSingleValue(new SingleValue('value2'));
        $rootGroup->addField('field1', $values);

        $values = new ValuesBag();
        $values->addSingleValue(new SingleValue('value3'));
        $values->addSingleValue(new SingleValue('value4'));

        $subGroup = new ValuesGroup(ValuesGroup::GROUP_LOGICAL_OR);
        $subGroup->addField('field1', $values);
        $rootGroup->addGroup($subGroup);

        $condition = new SearchCondition($fieldSet->getWrappedObject(), $rootGroup);

        $this->setFieldSet($fieldSet);
        $this->process('field1: value, value2; *(field1: value3, value4;);')->shouldBeLike($condition);
    }

    public function it_parses_multiple_subgroups(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $fieldSet->has('field1')->willReturn(true);

        $values = new ValuesBag();
        $values->addSingleValue(new SingleValue('value'));
        $values->addSingleValue(new SingleValue('value2'));
        $fieldSet->all()->willReturn(array('field1' => $field));

        $rootGroup = new ValuesGroup();

        $subGroup = new ValuesGroup();
        $subGroup->addField('field1', $values);

        $values = new ValuesBag();
        $values->addSingleValue(new SingleValue('value3'));
        $values->addSingleValue(new SingleValue('value4'));
        $rootGroup->addGroup($subGroup);

        $subGroup2 = new ValuesGroup();
        $subGroup2->addField('field1', $values);

        $rootGroup->addGroup($subGroup2);

        $condition = new SearchCondition($fieldSet->getWrappedObject(), $rootGroup);

        $this->setFieldSet($fieldSet);
        $this->process('(field1: value, value2;); (field1: value3, value4;)')->shouldBeLike($condition);
    }

    public function it_parses_nested_subgroups(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->all()->willReturn(array('field1' => $field));

        $values = new ValuesBag();
        $values->addSingleValue(new SingleValue('value'));
        $values->addSingleValue(new SingleValue('value2'));

        $rootGroup = new ValuesGroup();
        $subGroup = new ValuesGroup();

        $nestedGroup = new ValuesGroup();
        $nestedGroup->addField('field1', $values);
        $subGroup->addGroup($nestedGroup);

        $rootGroup->addGroup($subGroup);

        $condition = new SearchCondition($fieldSet->getWrappedObject(), $rootGroup);

        $this->setFieldSet($fieldSet);
        $this->process('((field1: value, value2;))')->shouldBeLike($condition);
    }

    public function it_errors_when_maximum_values_count_is_exceeded(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->has('field2')->willReturn(true);
        $fieldSet->all()->willReturn(array('field1' => $field));

        $this->setFieldSet($fieldSet);
        $this->setMaxValues(3);

        $this->shouldThrow(new ValuesOverflowException('field1', 3, 4, 0, 0))->during('process', array('field1: value, value2, value3, value4, value5;'));
        $this->shouldThrow(new ValuesOverflowException('field1', 3, 4, 0, 2))->during('process', array('((field1: value, value2, value3, value4, value5));'));
        $this->shouldThrow(new ValuesOverflowException('field1', 3, 4, 1, 2))->during('process', array('((field1: value); (field1: value, value2, value3, value4, value5));'));
    }

    public function it_errors_when_maximum_values_count_is_exceeded_at_merging(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->has('field2')->willReturn(true);
        $fieldSet->all()->willReturn(array('field1' => $field));

        $this->setFieldSet($fieldSet);
        $this->setMaxValues(3);

        $this->shouldThrow(new ValuesOverflowException('field1', 3, 4, 0, 0))->during('process', array('field1: value, value2; field1: value3, value4, value5;'));
    }

    public function it_errors_when_maximum_groups_count_is_exceeded(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->has('field2')->willReturn(true);
        $fieldSet->all()->willReturn(array('field1' => $field));

        $this->setFieldSet($fieldSet);
        $this->setMaxGroups(3);

        $this->shouldThrow(new GroupsOverflowException(3, 4, 0, 0))->during('process', array('(field1: value, value2;); (field1: value, value2;); (field1: value, value2;); (field1: value, value2;)'));
        $this->shouldThrow(new GroupsOverflowException(3, 4, 1, 2))->during('process', array('( ((field1: value, value2)); ((field1: value, value2;); (field1: value, value2;); (field1: value, value2;); (field1: value, value2;)) )'));
    }

    public function it_errors_when_maximum_nesting_level_is_reached(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->has('field2')->willReturn(true);
        $fieldSet->all()->willReturn(array('field1' => $field));

        $this->setFieldSet($fieldSet);
        $this->setMaxNestingLevel(1);

        $this->shouldThrow(new GroupsNestingException(1, 0, 2))->during('process', array('((field1: value;))'));
    }

    public function it_errors_when_the_syntax_is_invalid(FieldSet $fieldSet, FieldConfigInterface $field, FieldConfigInterface $field2)
    {
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->has('field2')->willReturn(true);
        $fieldSet->all()->willReturn(array('field1' => $field, 'field2' => $field2));

        $this->setFieldSet($fieldSet);

        $this->shouldThrow(new QueryException('[Syntax Error] line 0, col 46: Error: Expected \'"(" or FieldIdentification\', got \')\''))->during('process', array('field1: value, value2, value3, value4, value5;)'));
        $this->shouldThrow(new QueryException("[Syntax Error] line 0, col 14: Error: Expected '; | , | )', got 'value2'"))->during('process', array('field1: value value2)'));
    }

    public function it_errors_when_the_field_does_not_exist_in_fieldset(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->has('field2')->willReturn(false);
        $fieldSet->all()->willReturn(array('field1' => $field));

        $this->setFieldSet($fieldSet);

        $this->shouldThrow(new UnknownFieldException('field2'))->during('process', array('field2: value;'));
    }

    public function it_errors_when_the_field_does_not_support_the_value_type(FieldSet $fieldSet, FieldConfigInterface $field, FieldConfigInterface $field2)
    {
        $field->acceptRanges()->willReturn(false);
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);

        $field2->acceptCompares()->willReturn(false);
        $fieldSet->has('field2')->willReturn(true);
        $fieldSet->get('field2')->willReturn($field2);

        $fieldSet->all()->willReturn(array('field1' => $field, 'field2' => $field2));

        $this->setFieldSet($fieldSet);

        $this->shouldThrow(new UnsupportedValueTypeException('field1', 'range'))->during('process', array('field1: 1-12;'));
        $this->shouldThrow(new UnsupportedValueTypeException('field2', 'comparison'))->during('process', array('field2: >12;'));
    }

    public function it_errors_when_a_field_is_required_but_not_set(FieldSet $fieldSet, FieldConfigInterface $field, FieldConfigInterface $field2)
    {
        $field->isRequired()->willReturn(false);
        $field->acceptRanges()->willReturn(false);
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);

        $field2->isRequired()->willReturn(true);
        $field2->acceptCompares()->willReturn(false);
        $fieldSet->has('field2')->willReturn(true);
        $fieldSet->get('field2')->willReturn($field2);

        $fieldSet->all()->willReturn(array('field1' => $field, 'field2' => $field2));

        $this->setFieldSet($fieldSet);

        $this->shouldThrow(new FieldRequiredException('field2', 0, 0))->during('process', array('field1: value, value2, value3, value4, value5;'));
        $this->shouldThrow(new FieldRequiredException('field2', 0, 2))->during('process', array('((field1: value, value2, value3, value4, value5));'));
        $this->shouldThrow(new FieldRequiredException('field2', 1, 2))->during('process', array('((field2: value); (field1: value, value2, value3, value4, value5));'));
    }
}
