<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\Rollerworks\Component\Search\Input;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Rollerworks\Component\Search\Exception\InputProcessorException;
use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\Value\SingleValue;
use Rollerworks\Component\Search\ValuesBag;
use Rollerworks\Component\Search\ValuesGroup;
use Seld\JsonLint\ParsingException;

// Note: Because the JSON input extends the ArrayInput we don't need extensive checks
class JsonInputSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Rollerworks\Component\Search\Input\JsonInput');
        $this->shouldImplement('Rollerworks\Component\Search\InputProcessorInterface');
    }

    function it_processes_valid_json(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $field->isRequired()->willReturn(false);
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);
        $fieldSet->all()->willReturn(array('field1' => $field));

        $values = new ValuesBag();
        $values->addSingleValue(new SingleValue('value'));
        $values->addSingleValue(new SingleValue('value2'));

        $expectedGroup = new ValuesGroup();
        $expectedGroup->addField('field1', $values);

        $this->setFieldSet($fieldSet);
        $this->process(
            json_encode(array(
                'fields' => array(
                    'field1' => array(
                        'single-values' => array('value', 'value2')
                    )
                )
            ), JSON_FORCE_OBJECT)
        )->shouldBeLike($expectedGroup);
    }

    function it_processes_nested_values(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $field->isRequired()->willReturn(false);
        $field->acceptCompares()->willReturn(true);
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);
        $fieldSet->all()->willReturn(array('field1' => $field));

        $this->setMaxNestingLevel(1);

        $this->setFieldSet($fieldSet);
        $this->process(
            json_encode(array(
                'groups' => array(
                    array(
                        'fields' => array(
                            'field1' => array(
                                'comparisons' => array(array('value' => 'value', 'operator' => '>'))
                            )
                        )
                    )
                )
            ), JSON_FORCE_OBJECT)
        )->shouldHaveType('Rollerworks\Component\Search\ValuesGroup');
    }

    function it_errors_on_invalid_json(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $field->isRequired()->willReturn(false);
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);
        $fieldSet->all()->willReturn(array('field1' => $field));

        $this->setFieldSet($fieldSet);

        //$e = new ParsingException("Input does not contain valid JSON\nParse error on line 1:\n{]\n-^\nExpected one of: 'STRING', '}'", array());
        $this->shouldThrow(new InputProcessorException('Provided input is invalid.'))->during('process', array('{]'));
    }
}
