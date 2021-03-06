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
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Value\SingleValue;
use Rollerworks\Component\Search\ValuesBag;
use Rollerworks\Component\Search\ValuesGroup;

// Note: Because the JSON exporter extends the ArrayExporter we don't need extensive checks
class JsonExporterSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('Rollerworks\Component\Search\Exporter\JsonExporter');
        $this->shouldImplement('Rollerworks\Component\Search\ExporterInterface');
    }

    public function its_a_json_string(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);

        $values = new ValuesBag();
        $values->addSingleValue(new SingleValue('', 'value'));
        $values->addSingleValue(new SingleValue('', 'value2'));

        $group = new ValuesGroup();
        $group->addField('field1', $values);

        $condition = new SearchCondition($fieldSet->getWrappedObject(), $group);

        $this->exportCondition($condition)->shouldBeLike(
            json_encode(array(
                'fields' => array(
                    'field1' => array(
                        'single-values' => array('value', 'value2')
                    )
                )
            ), JSON_FORCE_OBJECT)
        );
    }
}
