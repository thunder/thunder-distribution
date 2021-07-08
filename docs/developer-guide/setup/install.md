---
tags:
- setup
---
# Install Thunder

## System requirements

You have to install `PHP`, `composer`, and `git` on your computer before you can install Thunder. The `composer` requires `git` command for proper functioning.

To install `PHP` please take a look at the official [Installation and Configuration](https://www.php.net/manual/install.php) for `PHP`.
On top of `PHP`, you need to install the required libraries. The Thunder installation requires at least all the libraries Drupal requires.
Extensions used by Drupal core are defined in Core's composer.json file - see for example the [file for Drupal 9.1.x](https://git.drupalcode.org/project/drupal/blob/9.1.x/core/composer.json). Look at the "require" section and the keys starting with "ext-".

The installation of `PHP` extensions can differ between operating systems, that's why you should check for detailed instructions on `PHP` [Installation and Configuration](https://www.php.net/manual/install.php).

To install `composer`, you can check the `composer` [installation instructions](https://getcomposer.org/download) and for `git` you can find installation instructions [here](https://git-scm.com/downloads).

## Project setup

To set up a new project, run this in your console to install Thunder from the command line:

```bash
composer create-project thunder/thunder-project thunder --no-interaction --no-install
cd thunder
composer install
```

## Run locally

To quickly run this installation locally call the following command from within the docroot:

```bash
cd docroot
php core/scripts/drupal quick-start thunder
```

**NOTE:** This command is useful to try Thunder locally, but it's not the way to run it in production.

## Beyond quick install

For any further information on how to run and maintain your installation in production environments please refer to [the Drupal User Guide](https://www.drupal.org/docs/user_guide/en/index.html).
