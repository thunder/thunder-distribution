# Install Thunder

## System requirements

You have to install `PHP`, `composer` and `git` on your computer before you can install Thunder. The `composer` requires `git` command for proper functioning.

To install `PHP` please take a look at official [Installation and Configuration](https://www.php.net/manual/install.php) for `PHP`.
On top of `PHP`, you need to install the required libraries. Thunder installation requires the following `PHP` libraries: `xmlwriter`, `gd`, `mbstring`, `curl`, `json` and `pdo_sqlite`. The installation of `PHP` extensions can differ between operating systems, that's why you should check for detailed instructions on `PHP` [Installation and Configuration](https://www.php.net/manual/install.php).

To install `composer`, you can check the `composer` [installation instructions](https://getcomposer.org/download) and for `git` you can find installation instructions [here](https://git-scm.com/downloads).

## Project setup

To setup a new project, run this in your console to install Thunder from the command line:

```
composer create-project thunder/thunder-project thunder --no-interaction --no-install
cd thunder
composer install
composer drupal:scaffold
```

## Run locally

To quickly run this installation locally call the following command from within the docroot:

```
php core/scripts/drupal quick-start thunder
```

**NOTE:** This command is useful to try Thunder locally, but it's not the way to run it in production.

## Beyond quick install

For any further information on how to run and maintain your installation in production environments please refer to [the Drupal User Guide](https://www.drupal.org/docs/user_guide/en/index.html).
