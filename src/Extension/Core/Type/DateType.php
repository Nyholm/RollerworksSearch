<?php

/**
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Extension\Core\Type;

use Rollerworks\Component\Search\AbstractFieldType;
use Rollerworks\Component\Search\Exception\InvalidConfigurationException;
use Rollerworks\Component\Search\Extension\Core\DataTransformer\DateTimeToLocalizedStringTransformer;
use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\ValueComparisonInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class DateType extends AbstractFieldType
{
    const DEFAULT_FORMAT = \IntlDateFormatter::MEDIUM;

    const HTML5_FORMAT = 'yyyy-MM-dd';

    /**
     * @var array
     */
    private static $acceptedFormats = array(
        \IntlDateFormatter::FULL,
        \IntlDateFormatter::LONG,
        \IntlDateFormatter::MEDIUM,
        \IntlDateFormatter::SHORT,
    );

    /**
     * @var ValueComparisonInterface
     */
    protected $valueComparison;

    /**
     * Constructor.
     *
     * @param ValueComparisonInterface $valueComparison
     */
    public function __construct(ValueComparisonInterface $valueComparison)
    {
        $this->valueComparison = $valueComparison;
    }

    /**
     * {@inheritdoc}
     */
    public function buildType(FieldConfigInterface $config, array $options)
    {
        $config->setValueComparison($this->valueComparison);

        $dateFormat = is_int($options['format']) ? $options['format'] : self::DEFAULT_FORMAT;
        $timeFormat = \IntlDateFormatter::NONE;
        $calendar = \IntlDateFormatter::GREGORIAN;
        $pattern = is_string($options['format']) ? $options['format'] : null;

        if (!in_array($dateFormat, self::$acceptedFormats, true)) {
            throw new InvalidConfigurationException(
                'The "format" option must be one of the IntlDateFormatter constants (FULL, LONG, MEDIUM, SHORT) '.
                'or a string representing a custom format.'
            );
        }

        if (null !== $pattern && (false === strpos($pattern, 'y') || false === strpos($pattern, 'M')
            || false === strpos($pattern, 'd'))
        ) {
            throw new InvalidConfigurationException(
                sprintf(
                    'The "format" option should contain the letters "y", "M" and "d". Its current value is "%s".',
                    $pattern
                )
            );
        }

        $config->addViewTransformer(
            new DateTimeToLocalizedStringTransformer(
                $options['model_timezone'],
                $options['view_timezone'],
                $dateFormat,
                $timeFormat,
                $calendar,
                $pattern
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'format' => DateType::DEFAULT_FORMAT,
                'model_timezone' => null,
                'input_timezone' => null,
            )
        );

        $resolver->setAllowedTypes(
            array('format' => array('int', 'string'),)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function hasRangeSupport()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function hasCompareSupport()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'date';
    }
}
