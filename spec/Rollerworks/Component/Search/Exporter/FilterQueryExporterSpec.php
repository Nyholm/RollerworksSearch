<?php

/**
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace spec\Rollerworks\Component\Search\Exporter;

use PhpSpec\ObjectBehavior;
use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\FieldLabelResolverInterface;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\SingleValue;
use Rollerworks\Component\Search\ValuesBag;
use Rollerworks\Component\Search\ValuesGroup;

class FilterQueryExporterSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('Rollerworks\Component\Search\Exporter\FilterQueryExporter');
        $this->shouldImplement('Rollerworks\Component\Search\ExporterInterface');
    }

    public function it_exports_single_values(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);

        $values = new ValuesBag();
        $values->addSingleValue(new SingleValue('', 'value'));
        $values->addSingleValue(new SingleValue('', 'value2'));

        $group = new ValuesGroup();
        $group->addField('field1', $values);

        $condition = new SearchCondition($fieldSet->getWrappedObject(), $group);

        $this->exportCondition($condition)->shouldBeLike('field1: value, value2;');
    }

    public function it_exports_with_multiple_fields(FieldSet $fieldSet, FieldConfigInterface $field, FieldConfigInterface $field2)
    {
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);

        $fieldSet->has('field2')->willReturn(true);
        $fieldSet->get('field2')->willReturn($field);

        $values = new ValuesBag();
        $values->addSingleValue(new SingleValue('', 'value'));
        $values->addSingleValue(new SingleValue('', 'value2'));

        $group = new ValuesGroup();
        $group->addField('field1', $values);

        $values = new ValuesBag();
        $values->addSingleValue(new SingleValue('', '1value'));
        $values->addSingleValue(new SingleValue('', '1value2'));
        $group->addField('field2', $values);

        $condition = new SearchCondition($fieldSet->getWrappedObject(), $group);

        $this->exportCondition($condition)->shouldBeLike('field1: value, value2; field2: 1value, 1value2;');
    }

    public function it_exports_values_escaped_when_needed(FieldSet $fieldSet, FieldConfigInterface $field, FieldConfigInterface $field2)
    {
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);

        $fieldSet->has('field2')->willReturn(true);
        $fieldSet->get('field2')->willReturn($field);

        $values = new ValuesBag();
        $values->addSingleValue(new SingleValue('', 'value '));
        $values->addSingleValue(new SingleValue('', '-value2'));
        $values->addSingleValue(new SingleValue('', 'value2-'));
        $values->addSingleValue(new SingleValue('', '10.00'));
        $values->addSingleValue(new SingleValue('', '10,00'));
        $values->addSingleValue(new SingleValue('', 'hÌ'));
        $values->addSingleValue(new SingleValue('', '٤٤٤٦٥٤٦٠٠'));
        $values->addSingleValue(new SingleValue('', 'doctor"who""'));

        $group = new ValuesGroup();
        $group->addField('field1', $values);

        $condition = new SearchCondition($fieldSet->getWrappedObject(), $group);

        $this->exportCondition($condition)->shouldBeLike('field1: "value ", "-value2", "value2-", 10.00, "10,00", hÌ, ٤٤٤٦٥٤٦٠٠, "doctor""who""""";');
    }

    public function it_exports_excluded_values(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);

        $values = new ValuesBag();
        $values->addExcludedValue(new SingleValue('', 'value'));
        $values->addExcludedValue(new SingleValue('', 'value2'));

        $group = new ValuesGroup();
        $group->addField('field1', $values);

        $condition = new SearchCondition($fieldSet->getWrappedObject(), $group);

        $this->exportCondition($condition)->shouldBeLike('field1: !value, !value2;');
    }

    public function it_exports_ranges(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);

        $values = new ValuesBag();
        $values->addRange(new Range('', '', true, true, '10', '20'));
        $values->addRange(new Range('', '', true, true, '30', '50'));

        $values->addRange(new Range('', '', true, false, '30', '50'));
        $values->addRange(new Range('', '', false, true, '30', '50'));

        $group = new ValuesGroup();
        $group->addField('field1', $values);

        $condition = new SearchCondition($fieldSet->getWrappedObject(), $group);

        $this->exportCondition($condition)->shouldBeLike('field1: 10-20, 30-50, 30-50[, ]30-50;');
    }

    public function it_exports_excluded_ranges(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);

        $values = new ValuesBag();
        $values->addExcludedRange(new Range('', '', true, true, '10', '20'));
        $values->addExcludedRange(new Range('', '', true, true, '30', '50'));

        $group = new ValuesGroup();
        $group->addField('field1', $values);

        $condition = new SearchCondition($fieldSet->getWrappedObject(), $group);

        $this->exportCondition($condition)->shouldBeLike('field1: !10-20, !30-50;');
    }

    public function it_exports_comparisons(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);

        $values = new ValuesBag();
        $values->addComparison(new Compare('', '>', '10'));
        $values->addComparison(new Compare('', '<', '50'));

        $group = new ValuesGroup();
        $group->addField('field1', $values);

        $condition = new SearchCondition($fieldSet->getWrappedObject(), $group);

        $this->exportCondition($condition)->shouldBeLike('field1: >10, <50;');
    }

    public function it_exports_pattern_matchers(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);

        $values = new ValuesBag();
        $values->addPatternMatch(new PatternMatch('foo', 'CONTAINS'));
        $values->addPatternMatch(new PatternMatch('bla', 'NOT_CONTAINS'));
        $values->addPatternMatch(new PatternMatch('bar', 'CONTAINS', true));
        $values->addPatternMatch(new PatternMatch('ooi', 'NOT_CONTAINS', true));
        $values->addPatternMatch(new PatternMatch('who', 'STARTS_WITH', true));
        $values->addPatternMatch(new PatternMatch('(\w+|\d+)', 'REGEX'));

        $group = new ValuesGroup();
        $group->addField('field1', $values);

        $condition = new SearchCondition($fieldSet->getWrappedObject(), $group);

        $this->exportCondition($condition)->shouldBeLike('field1: ~*foo, ~!*bla, ~i*bar, ~i!*ooi, ~i>who, ~?"(\w+|\d+)";');
    }

    public function it_supports_field_label(FieldSet $fieldSet, FieldConfigInterface $field, FieldLabelResolverInterface $labelResolver)
    {
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);

        $labelResolver->resolveFieldLabel($fieldSet->getWrappedObject(), 'field1')->willReturn('user-id');
        $this->setLabelResolver($labelResolver);

        $values = new ValuesBag();
        $values->addSingleValue(new SingleValue('', 'value'));
        $values->addSingleValue(new SingleValue('', 'value2'));

        $group = new ValuesGroup();
        $group->addField('field1', $values);

        $condition = new SearchCondition($fieldSet->getWrappedObject(), $group);

        $this->exportCondition($condition, true)->shouldBeLike('user-id: value, value2;');
    }

    public function it_exports_groups(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);

        $values = new ValuesBag();
        $values->addSingleValue(new SingleValue('', 'value'));
        $values->addSingleValue(new SingleValue('', 'value2'));

        $subGroup = new ValuesGroup();
        $subGroup->addField('field1', $values);

        $group = new ValuesGroup();
        $group->addGroup($subGroup);

        $condition = new SearchCondition($fieldSet->getWrappedObject(), $group);

        $this->exportCondition($condition)->shouldBeLike('(field1: value, value2; );');
    }

    public function it_exports_subgroups(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);

        $values = new ValuesBag();
        $values->addSingleValue(new SingleValue('', 'value'));
        $values->addSingleValue(new SingleValue('', 'value2'));

        $subGroup = new ValuesGroup();
        $subGroup->addField('field1', $values);

        $values = new ValuesBag();
        $values->addSingleValue(new SingleValue('', 'value'));
        $values->addSingleValue(new SingleValue('', 'value2'));

        $subGroup2 = new ValuesGroup();
        $subGroup2->addField('field1', $values);
        $subGroup->addGroup($subGroup2);

        $group = new ValuesGroup();
        $group->addGroup($subGroup);

        $condition = new SearchCondition($fieldSet->getWrappedObject(), $group);

        $this->exportCondition($condition)->shouldBeLike('(field1: value, value2; (field1: value, value2; ););');
    }

    public function it_exports_logical_groups(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);

        $values = new ValuesBag();
        $values->addSingleValue(new SingleValue('', 'value'));
        $values->addSingleValue(new SingleValue('', 'value2'));

        $group = new ValuesGroup(ValuesGroup::GROUP_LOGICAL_OR);
        $group->addField('field1', $values);

        $condition = new SearchCondition($fieldSet->getWrappedObject(), $group);

        $this->exportCondition($condition)->shouldBeLike('*(field1: value, value2; );');
    }

    public function it_exports_logical_subgroups(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);

        $values = new ValuesBag();
        $values->addSingleValue(new SingleValue('', 'value'));
        $values->addSingleValue(new SingleValue('', 'value2'));

        $subGroup = new ValuesGroup(ValuesGroup::GROUP_LOGICAL_OR);
        $subGroup->addField('field1', $values);

        $group = new ValuesGroup();
        $group->addGroup($subGroup);

        $condition = new SearchCondition($fieldSet->getWrappedObject(), $group);

        $this->exportCondition($condition)->shouldBeLike('*(field1: value, value2; );');
    }
}
