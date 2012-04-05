<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\RecordFilterBundle\Tests;

use Rollerworks\RecordFilterBundle\Type\Date;

class DaterTest extends \PHPUnit_Framework_TestCase
{
    function testSanitize()
    {
        $type = new Date();

        $this->assertEquals('2010-10-04', $type->sanitizeString('04.10.2010'));
        $this->assertEquals('2010-10-04', $type->sanitizeString('04.10.2010'));
        $this->assertEquals('2010-10-04', $type->sanitizeString('04-10-2010'));

        $this->assertEquals('2010-10-04', $type->sanitizeString('2010.10.04'));
        $this->assertEquals('2010-10-04', $type->sanitizeString('2010-10-04'));
    }


    function testValidation()
    {
        $type = new Date();

        $this->assertTrue($type->validateValue('04.10.2010'));
        $this->assertFalse($type->validateValue('04.13.2010'));
    }


    function testHigher()
    {
        $type = new Date();

        $this->assertTrue($type->isHigher('05.10.2010', '04.10.2010'));
    }


    function testNotHigher()
    {
        $type = new Date();

        $this->assertFalse($type->isHigher('04.10.2010', '05.10.2010'));
    }


    function testLower()
    {
        $type = new Date();

        $this->assertTrue($type->isLower('03.10.2010', '04.10.2010'));
    }


    function testNotLower()
    {
        $type = new Date();

        $this->assertFalse($type->isLower('05.10.2010', '04.10.2010'));
    }


    function testEquals()
    {
        $type = new Date();

        $this->assertTrue($type->isEquals('05.10.2010', '05.10.2010'));
        $this->assertTrue($type->isEquals('05.10.2010', '05-10-2010'));
        $this->assertTrue($type->isEquals('05-10-2010', '05.10.2010'));
    }


    function testNotEquals()
    {
        $type = new Date();

        $this->assertFalse($type->isEquals('03.10.2010', '04.10.2010'));
        $this->assertFalse($type->isEquals('03.10.2010', '04-10-2010'));
        $this->assertFalse($type->isEquals('03-10-2010', '04.10.2010'));
    }

    function testHigherValue()
    {
        $type = new Date();

        $this->assertEquals('2010-10-05', $type->getHigherValue('2010-10-04'));
        $this->assertEquals('2012-01-01', $type->getHigherValue('2011-12-31'));

        $this->assertEquals('2012-02-29', $type->getHigherValue('2012-02-28'));
        $this->assertEquals('2011-03-01', $type->getHigherValue('2011-02-28'));
    }
}