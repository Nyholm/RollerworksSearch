<?php

/**
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Exception;

/**
 * UnsupportedValueTypeException.
 *
 * Throw this exception when the value-type is not supported for the field.
 */
class UnsupportedValueTypeException extends \RuntimeException implements ExceptionInterface
{
    /**
     * @var string
     */
    protected $fieldName;

    /**
     * @var string
     */
    protected $valueType;

    /**
     * Constructor.
     *
     * @param string $fieldName
     * @param string $valueType
     */
    public function __construct($fieldName, $valueType)
    {
        $this->fieldName = $fieldName;
        $this->valueType = $valueType;

        parent::__construct(
            sprintf('Field "%s" does accept %s values.', $fieldName, $valueType)
        );
    }

    /**
     * @return string
     */
    public function getValueType()
    {
        return $this->valueType;
    }

    /**
     * @return string
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }
}
