Metadata
========

Class metadata is used by the ``FieldSetBuilder`` to populate a ``FieldSet`` instance
based on the metadata of a Model class.

The information can be stored directly with the class using `PHP Annotations`_,
or as a separate file using either YAML or XML.

.. note::

    To actually use the metadata component you first need to
    install the ``jms/metadata`` package.

    And for XML and YAML support you need to configure
    the file-locator.

    See the 'Metadata' subsection in :doc:`/installing/` for more information.

The ``FileLocator`` will try to guess the the mapping config-dir by
matching the namespace prefix to the given Model class-name.

In the example below the Model class ``Acme\Store\Model\Product``
will be mapped to the ``src/Acme/Store/Resources/Rollerworks/Search/`` directory-namespace
and tries to find the corresponding class-name ``Product`` as either ``Product.yml`` or
``Product.xml``

.. code-block:: php

    use Metadata\Driver\FileLocator;
    use Metadata\Driver\DriverChain;
    use Metadata\MetadataFactory;
    use Doctrine\Common\Annotations\Reader;
    use Rollerworks\Component\Search\Metadata\Driver as MappingDriver;

    $locator = new FileLocator(array(
        'Acme\Store\Model' => 'src/Acme/Store/Resources/Rollerworks/Search/',
        'Acme\User\Model' => 'src/Acme/User/Resources/Rollerworks/Search/',
    ));

    // You'd properly want to use one of the provided caches
    // See: https://github.com/schmittjoh/metadata/tree/master/src/Metadata/Cache

    $driver = new DriverChain(array(
        new MappingDriver\AnnotationDriver(),
        new MappingDriver\XmlFileDriver($locator),
        new MappingDriver\YamlFileDriver($locator),
    ));

    $metadataFactory = new MetadataFactory($driver);
    $searchFactory = new SearchFactory(..., $metadataFactory);

.. configuration-block::

    .. code-block:: php-annotations

        // src/Acme/Store/Model/Product.php

        namespace Acme\Store\Model;

        use Rollerworks\Component\Search\Metadata as Search;

        class Product
        {
            /**
             * @Search\Field("product_id", required=false, type="number")
             */
            protected $id;

            /**
             * @Search\Field("product_name", type="text")
             */
            protected $name;

            /**
             * @Search\Field("product_price", type="decimal", options={min=0.01})
             */
            protected $price;

            // ...
        }

    .. code-block:: yaml

        # src/Acme/Store/Resources/Rollerworks/Search/Product.yml
        id:
            # Name is the search-field name
            name: product_id
            type: number
            required: false
            accept-ranges: true
            accept-compares: true

        name:
            name: product_name
            type: text

        price:
            name: product_price
            accept-ranges: true
            accept-compares: true
            type:
                name: decimal
                params:
                    min: 0.01

    .. code-block:: xml

        <!-- src/Acme/Store/Resources/Rollerworks/Search/Product.xml -->

        <?xml version="1.0" encoding="UTF-8"?>
        <properties>
            <property id="id" name="product_id" required="false">
                <type name="number" />
            </property>
            <property id="name" name="product_name">
                <type name="text" />
            </property>
            <property id="name" name="product_name">
                <type name="text" />
            </property>
            <property id="price" name="product_price" accept-ranges="true" accept-compares="true">
                <type name="text">
                    <param key="min" type="float">0.01</param>
                    <!-- An array-value is build as follow. Key and type are optional for, type is required for collection -->
                    <!--
                    <option key="key" type="collection">
                        <option type="string">value</option>
                        <option type="collection">
                            <value key="foo">value</option>
                        </option>
                    </option>
                    -->
                </type>
            </property>
        </properties>

.. caution::

    A class can accept only one metadata definition format.

    For example, it is not possible to mix YAML metadata definitions with
    annotated PHP class definitions.

.. _`PHP Annotations`: http://docs.doctrine-project.org/projects/doctrine-common/en/latest/reference/annotations.html
