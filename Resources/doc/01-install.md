Setting up the bundle
=====================

## Install the bundle

Execute this console command in your project:

``` bash
$ composer require jonasarts/notification-bundle
```

## Enable the bundle

Register the bundle in the kernel:

```php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    // ...

    public function registerBundles()
    {
        $bundles = array(
            // ...
            new jonasarts\Bundle\NotificationBundle\NotificationBundle(),
        );

    // ...
    }
}
```

## Configuration options

[Read the bundle configuration options](02-configuration.md)

## That's it

Check out the docs for information on how to use the bundle! [Return to the index.](index.md)
