<?php

/**
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace spec\Rollerworks\Component\Search\FieldAliasResolver;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Rollerworks\Component\Search\FieldAliasResolverInterface;
use Rollerworks\Component\Search\FieldSet;

class ChainFieldAliasResolverSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith(array());
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Rollerworks\Component\Search\FieldAliasResolver\ChainFieldAliasResolver');
    }

    function it_returns_the_input_when_there_are_no_resolvers(FieldSet $fieldSet, FieldAliasResolverInterface $resolver)
    {
        $resolver->resolveFieldName($fieldSet, 'user')->willReturn('user');
        $resolver->resolveFieldName($fieldSet, 'user')->willReturn('user');
    }

    function it_resolves_a_field(FieldSet $fieldSet, FieldAliasResolverInterface $resolver)
    {
        $this->beConstructedWith(array($resolver));

        $resolver->resolveFieldName($fieldSet, 'user')->willReturn('user_id');
        $resolver->resolveFieldName($fieldSet, 'id')->willReturn('id');
        $resolver->resolveFieldName($fieldSet, 'name')->willReturn(null);

        $this->resolveFieldName($fieldSet, 'user')->shouldReturn('user_id');
        $this->resolveFieldName($fieldSet, 'id')->shouldReturn('id');
        $this->resolveFieldName($fieldSet, 'name')->shouldReturn('name');
    }

    function it_resolves_a_field_with_multiple_resolvers(
        FieldSet $fieldSet,
        FieldAliasResolverInterface $resolver,
        FieldAliasResolverInterface $resolver2
    ) {
        $this->beConstructedWith(array($resolver, $resolver2));

        $resolver->resolveFieldName($fieldSet, 'user')->willReturn('user_id');
        $resolver->resolveFieldName($fieldSet, 'id')->willReturn('id');
        $resolver2->resolveFieldName($fieldSet, 'id')->willReturn('my_id');
        $resolver->resolveFieldName($fieldSet, 'name')->willReturn(null);

        $this->resolveFieldName($fieldSet, 'user')->shouldReturn('user_id');
        $this->resolveFieldName($fieldSet, 'id')->shouldReturn('my_id');
        $this->resolveFieldName($fieldSet, 'name')->shouldReturn('name');
    }
}
