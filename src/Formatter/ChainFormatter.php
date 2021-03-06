<?php

/**
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Formatter;

use Rollerworks\Component\Search\FormatterInterface;
use Rollerworks\Component\Search\SearchConditionInterface;

/**
 * ChainFormatter performs the registered formatters in sequence.
 *
 * If during the formatting a violation is set the sequence is stopped.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ChainFormatter implements FormatterInterface
{
    /**
     * @var FormatterInterface[]
     */
    protected $formatters = array();

    /**
     * @param FormatterInterface $formatter
     *
     * @throws \InvalidArgumentException
     *
     * @return self
     */
    public function addFormatter(FormatterInterface $formatter)
    {
        // Ensure we got no end-less loops
        if ($formatter === $this) {
            throw new \InvalidArgumentException(
                'Unable to add formatter to chain, can not assign formatter to its self.'
            );
        }

        $this->formatters[] = $formatter;

        return $this;
    }

    /**
     * @return FormatterInterface[]
     */
    public function getFormatters()
    {
        return $this->formatters;
    }

    /**
     * {@inheritdoc}
     */
    public function format(SearchConditionInterface $condition)
    {
        if (true === $condition->getValuesGroup()->hasErrors()) {
            return;
        }

        foreach ($this->formatters as $formatter) {
            $formatter->format($condition);

            if (true === $condition->getValuesGroup()->hasErrors()) {
                break;
            }
        }
    }
}
