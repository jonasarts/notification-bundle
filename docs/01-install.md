Setting up the bundle
=====================

## Install the bundle

Execute this console command in your project:

``` bash
composer require jonasarts/notification-bundle
```

## Enable the bundle

Composer enables the bundle for you in config/bundles.php

## Enable the Twig templating engine

```yaml
#app/config/config.yml
framework:
    templating:
        engines: ['twig']
```

## Configuration options

[Read the bundle configuration options](02-configuration.md)

## That's it

Check out the docs for information on how to use the bundle! [Return to the index.](index.md)
