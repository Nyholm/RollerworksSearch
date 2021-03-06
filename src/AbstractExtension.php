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

use Rollerworks\Component\Search\Exception\InvalidArgumentException;
use Rollerworks\Component\Search\Exception\UnexpectedTypeException;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
abstract class AbstractExtension implements SearchExtensionInterface
{
    /**
     * The types provided by this extension.
     *
     * @var FieldTypeInterface[] An array of FieldTypeInterface
     */
    private $types;

    /**
     * The type extensions provided by this extension.
     *
     * @var FieldTypeExtensionInterface[] An array of FieldTypeExtensionInterface
     */
    private $typeExtensions;

    /**
     * {@inheritdoc}
     */
    public function getType($name)
    {
        if (null === $this->types) {
            $this->initTypes();
        }

        if (!isset($this->types[$name])) {
            throw new InvalidArgumentException(
                sprintf('The type "%s" can not be loaded by this extension', $name)
            );
        }

        return $this->types[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function hasType($name)
    {
        if (null === $this->types) {
            $this->initTypes();
        }

        return isset($this->types[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeExtensions($name)
    {
        if (null === $this->typeExtensions) {
            $this->initTypeExtensions();
        }

        return isset($this->typeExtensions[$name]) ? $this->typeExtensions[$name] : array();
    }

    /**
     * {@inheritdoc}
     */
    public function hasTypeExtensions($name)
    {
        if (null === $this->typeExtensions) {
            $this->initTypeExtensions();
        }

        return isset($this->typeExtensions[$name]) && count($this->typeExtensions[$name]) > 0;
    }

    /**
     * Registers the types.
     *
     * @return FieldTypeInterface[] An array of FormTypeInterface instances
     */
    protected function loadTypes()
    {
        return array();
    }

    /**
     * Registers the type extensions.
     *
     * @return FieldTypeExtensionInterface[] An array of FieldTypeExtensionInterface instances
     */
    protected function loadTypeExtensions()
    {
        return array();
    }

    /**
     * Initializes the types.
     *
     * @throws UnexpectedTypeException if any registered type is not an instance of FormTypeInterface
     */
    private function initTypes()
    {
        $this->types = array();

        foreach ($this->loadTypes() as $type) {
            if (!$type instanceof FieldTypeInterface) {
                throw new UnexpectedTypeException($type, 'Rollerworks\Component\Search\FieldTypeInterface');
            }

            $this->types[$type->getName()] = $type;
        }
    }

    /**
     * Initializes the type extensions.
     *
     * @throws UnexpectedTypeException if any registered type extension is not
     *                                 an instance of FieldTypeExtensionInterface
     */
    private function initTypeExtensions()
    {
        $this->typeExtensions = array();

        foreach ($this->loadTypeExtensions() as $extension) {
            if (!$extension instanceof FieldTypeExtensionInterface) {
                throw new UnexpectedTypeException(
                    $extension,
                    'Rollerworks\Component\Search\FieldTypeExtensionInterface'
                );
            }

            $type = $extension->getExtendedType();

            $this->typeExtensions[$type][] = $extension;
        }
    }
}
