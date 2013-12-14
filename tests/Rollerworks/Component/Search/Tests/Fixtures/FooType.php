<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search\Tests\Fixtures;

use Rollerworks\Component\Search\AbstractFieldType;

class FooType extends AbstractFieldType
{
    public function getName()
    {
        return 'foo';
    }

    public function getParent()
    {
        return null;
    }
}