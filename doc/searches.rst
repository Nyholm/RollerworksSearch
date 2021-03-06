Performing searches
===================

Using the FactoryBuilder
------------------------

The FactoryBuilder helps with setting up the search system.
It only need to set it up once, and then it can be reuse multiple times.

.. note::

    The ``Searches`` class and SearchFactoryBuilder are only meant to be used when
    you using Rollerworks Search as standalone. When making an integration
    with a framework plugin, you'd properly want create the SearchFactory and FieldsRegistry
    manually using a Dependency Injection system.

.. code-block:: php

    use Rollerworks\Component\Search\Searches;

    $searchFactory = new Searches::createSearchFactoryBuilder()
        // Here can optionally add new types or (type) extensions
        ->getSearchFactory();

Creating a fieldset
-------------------

Now, before you can start performing searches, the system first needs a ``FieldSet``
which will hold our search fields and there configuration.

You can create as many FieldSets as you want, but make sure
there names don't clash. Use a descriptive name like: 'customer_invoices' and 'customers'.

.. code-block:: php
    :linenos:

    $fieldset = $searchFactory->createFieldSetBuilder()
        ->add('id', 'integer')
        ->add('name', 'text')
        ->getFieldSet();

.. tip::

    We can also use the FieldSetBuilder to import the fields from models
    using the :doc:`metadata` component.

Performing a manual search
--------------------------

In most cases you'd ask the system to process an input and pass
it to a list of formatters before applying it on the storage layer.
But its not uncommon to create a SearchCondition manually.

The ``SearchConditionBuilder`` is just for this, if you already know how
an XML document is build then this should be pretty straightforward.

Each time you call ``group()`` it will create a new ``SearchConditionBuilder``
with a new depth. When you can end() it will return to the parent builder.

Calling field() will give us a new ``ValuesBagBuilder`` which
allows us adding new values and calling `end()` to get back to the ConditionBuilder.

.. note::

    When you don't use any formatter, the values must be normalized.

    In practice this means using real integers and floats,
    and DateTime for date/time/datetime.

    But using Choice or Money will require a special value.

.. code-block:: php
    :linenos:

    use Rollerworks\Component\Search\SearchConditionBuilder;
    use Rollerworks\Component\Search\Value\Compare;
    use Rollerworks\Component\Search\Value\PatternMatch;
    use Rollerworks\Component\Search\Value\Range;
    use Rollerworks\Component\Search\Value\SingleValue;

    $searchCondition = new SearchConditionBuilder::create($fieldset)
        ->field('id')
            ->addSingleValue(new SingleValue(12))
            ->addSingleValue(new SingleValue(30))
            ->addRange(new Range(50, 60))
        ->end()
        ->getSearchCondition();

This example will give you a SearchCondition with exactly one SearchGroup
and the following condition: id is 1 or 30 or (inclusive between 50 and 60).

Or if you need a more complex condition.

.. code-block:: php
    :linenos:

    use Rollerworks\Component\Search\SearchConditionBuilder;
    use Rollerworks\Component\Search\ValuesGroup;
    use Rollerworks\Component\Search\Value\Compare;
    use Rollerworks\Component\Search\Value\PatternMatch;
    use Rollerworks\Component\Search\Value\Range;
    use Rollerworks\Component\Search\Value\SingleValue;

    $searchCondition = new SearchConditionBuilder::create($fieldset)
        ->field('id')
            ->addSingleValue(new SingleValue(12))
            ->addSingleValue(new SingleValue(30))
            ->addRange(new Range(50, 60))
        ->end()
        ->group(ValuesGroup::GROUP_LOGICAL_OR)
            ->field('id')
                ->addSingleValue(new SingleValue(12))
                ->addSingleValue(new SingleValue(30))
                ->addRange(new Range(50, 60))
            ->end()
            ->field('name')
                ->addSingleValue(new PatternMatch('rory', PatternMatch::PATTERN_STARTS_WITH))
                ->addSingleValue(new PatternMatch('amy', PatternMatch::PATTERN_STARTS_WITH))
                ->addSingleValue(new PatternMatch('williams', PatternMatch::PATTERN_ENDS_WITH))
            ->end()
        ->end()
        ->getSearchCondition();

.. note::

    When you call ``field()`` with an existing field the values will
    be appended. Set the second parameter to true to force a new one.

Processing input
----------------

The most common case is processing the input to a SearchCondition,
for which the system provides you with a wide range of accepted formats.

This example uses the :doc:`input/filter_query` with the FieldSet shown above
and a modified SearchFactoryBuilder.

.. note::

    The ValidationFormatter requires that that the ``ValidatorExtension``
    is enabled in the factory.

.. code-block:: php
    :linenos:

    use Symfony\Component\Validator\Validation;
    use Rollerworks\Component\Search\Input\FilterQueryInput;
    use Rollerworks\Component\Search\Extension\Validator\ValidatorExtension;
    use Rollerworks\Component\Search\Extension\Validator\ValidationFormatter;
    use Rollerworks\Component\Search\Formatter\ChainFormatter;
    use Rollerworks\Component\Search\Formatter\TransformFormatter;
    use Rollerworks\Component\Search\Formatter\DuplicateRemover;
    use Rollerworks\Component\Search\Formatter\ValuesToRange;
    use Rollerworks\Component\Search\Formatter\RangeOptimizer;
    use Rollerworks\Component\Search\Searches;

    $validator = Validation::createValidator();
    $searchFactory = new Searches::createSearchFactoryBuilder()
        ->addExtension(new ValidatorExtension())
        ->getSearchFactory();

    /* ... */

    $inputProcessor = new FilterQueryInput();
    $inputProcessor->setFieldSet($fieldSet);

    // The query can from anything, like $_GET or $_POST
    $query = ... ;

    $searchCondition = $inputProcessor->process($query);

    // Because using user-input as-is isn't possible it passed trough a list of formatters
    // The order of formatters doesn't really mather, but its important to first transform and then validate
    // as the validator only validates the constraints, but not the format!

    $formatter = new ChainFormatter();
    $formatter->addFormatter(new TransformFormatter());
    $formatter->addFormatter(new ValidationFormatter($validator));
    $formatter->addFormatter(new DuplicateRemover());
    $formatter->addFormatter(new ValuesToRange()); // add this before RangeOptimizer to ensure new overlaps are removed later on
    $formatter->addFormatter(new RangeOptimizer());
    $formatter->format($searchCondition);

    // Now the $searchCondition is already for applying on the storage engine
    // See the chapters about the desired storage engine for more information

