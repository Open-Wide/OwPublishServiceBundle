===========================================
OwServiceBundle for eZ Publish documentation
===========================================

.. image:: https://github.com/Open-Wide/OwServiceBundle/raw/master/doc/images/Open-Wide_logo.png

:Extension: OW ServiceBundle v1.0
:Requires: eZ Publish 5.4.x
:Author: Open Wide http://www.openwide.fr

Presentation
============

This extension provides a complete system to create and show service in bloc or full view.

LICENCE
-------
This eZ Publish extension is provided *as is*, in GPL v3 (see LICENCE).

Installation via composer
=========================

1. Add ServiceBundle in your project's composer.json

.. code-block:: json

    {
      "require": {
        "open-wide/ezpublish-service-bundle": "dev-master"
      }
    }


2. Enable the Bundle in your EzPublishKernel.php file:

.. code-block:: php

    <?php
    // ezpublish/EzPublishKernel.php
    use OpenWide\Publish\ServiceBundle;
    ...

    public function registerBundles()
    {
      $bundles = array(
        // ...
        new OpenWide\Publish\ServiceBundle\OpenWidePublishServiceBundle(),
      );
    }


3. Create the following classes using the content package in ``Package`` directory or using [OWMigration](https://github.com/Open-Wide/OWMigration):


* In the class group ``Service``
    * service_folder
    * service_link


4. Add your event_folder LocationId in ``src/symfony/ezpublish/config/config.yml``:

.. code-block:: yml

        # LocationId of Service folder
        open_wide_service:
            root:
                location_id: ....
             # Nb of element per page
            paginate:
                max_per_page: ...

5. Create contents on back-office with the following structure:


    * service_folder
        * service_link
        * service_link


6. Run the legacy bundle install script manually:

.. code-block:: sh

    $ php ezpublish/console ezpublish:legacybundles:install_extensions


By default, it will create an absolute symlink, but options exist to use a hard copy (–copy) or a relative link (--relative).



