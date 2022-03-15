# pimcore-command-locker-bundle

This command will create a lock prior to running a command, so that the command cannot be started again by another container / process.

# Installing the package via composer

This bundle is easily installed via composer: `composer require torqit/pimcore-command-locker-bundle`

# Steps to setting up the object deleter:

1. Make sure you register the `CommandLockerBundle` in your `AppKernel.php` located at `\src\pimcore-root\app\AppKernel.php`. Registering the bundle is as easy as adding a line in the registerBundlesToCollection function, like so: `$collection->addBundle(new \TorqIT\CommandLockerBundle\CommandLockerBundle);`
2. You will also be required to enable the bundle. Either navigate to `var/config/extensions.php` and add it, or run `bin/console pimcore:bundle:enable CommandLockerBundle`.
3. Run the bundle, with the command: `./bin/console torq:command-locker 'YOUR_COMMAND_TO_BE_RUN'`.
