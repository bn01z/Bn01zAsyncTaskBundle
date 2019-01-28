Prerequisites
=============

This version of the bundle requires Symfony 4 or greater.

It also requires php-zmq extension installed and it currently work only on linux 
and requires PHP with [pcntl functions](http://php.net/manual/en/book.pcntl.php) enabled.  


Installation
============

Applications that use Symfony Flex
----------------------------------

Open a command console, enter your project directory and execute:

```console
$ composer require bn01z/async-task-bundle
```

Applications that don't use Symfony Flex
----------------------------------------

### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require bn01z/async-task-bundle
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `app/AppKernel.php` file of your project:

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = [
            // ...
            new Bn01z\AsyncTask\Bn01zAsyncTaskBundle(),
        ];

        // ...
    }

    // ...
}
```


### Step 3: Configure routing (OPTIONAL)

Import the routing.yml configuration file in app/config/routing.yml:

```yaml
# app/config/routing.yml
bn01z_async_task_status:
    resource: "@Bn01zAsyncTaskBundle/Resources/config/routes.yaml"
    prefix: /async-status

```

This is used for getting status of the call via HTTP request.

### Step 4: Configure Bn01zAsyncTaskBundle

Adding default Bn01zAsyncTaskBundle settings in app/config/config.yml:

```yaml
# app/config/config.yml
bn01z_async_task: ~
```

This will use ZMQ for queue tasks and filesystem for saving current status and web socket on port 8080.

To use redis for queue and status and websocket on port 9090:

```yaml
bn01z_async_task:
    queue:
        use: redis
    status:
        use: redis
    web_socket:
        address: '0.0.0.0:9090'
```

Basic usage
===========



Next steps
==========

[Detailed usage](detailed_usage.md)

[Configuration Reference](configuration_reference.md)

[Customization](customization.md)
