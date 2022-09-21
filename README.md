# File manager with path abstraction and file registry, storage solution

This bundle provides various helpers for managing files in Symfony:

 - Provides a `FileManager` object which provides primitives for managing
   files identified with custom logical schemes (such as `public://image/foo.png`)
   transparently handling physical real paths.

 - Provides a PHP StreamWrapper implementation for transparent usage of
   the custom URI schemes.

 - File index storage interface with advanced features.

 - SQL index implementation with transaction support without destructive
   rollbacks operations and delayed real file deletion operation.

# Setup

## Installation

First start with:

```sh
composer require makinacorpus/files
```

Then register the bundle to Symfony in `config/bundles.php`:

```php
<?php

return [
    /// ... Your other bundles...
    MakinaCorpus\Files\Bridge\Symfony\FilesBundle::class => ['all' => true],
];

```

Then proceed with configuration.

## Basic configuration

Everything should be auto-configured if you follow the rest of this section.

## Custom schemes configuration

Each custom scheme is tied to a custom folder, allowing you to store protocol
relative URI in your database instead of absolute path, making the application
portable and migrable easily.

Per default, the bundle offers three schemes:

 - `private://` for files that should not be accessible via the HTTPd
   which will default to `%kernel.project_dir/var/private/%`,
 - `public://` for files that will be freely visible via the HTTPd, which
   will default to `%kernel.project_dir/public/files/`,
 - `temporary://` for temporary files, which will default to PHP configured
   temporary folder,
 - `upload://` for chunked file upload, which defaults to `temporary://filechunk/`
 - `webroot://` for files that are in the public directory,
   will default to `%kernel.project_dir/public`,

Only the temporary one cannot be configured, all others can be set via
the following `.env` file variables:

```
FILE_PRIVATE_DIR="%kernel.project_dir%/var/private"
FILE_PUBLIC_DIR="%kernel.project_dir%/public/files"
FILE_UPLOAD_DIR="%kernel.project_dir%/var/tmp/upload"
FILE_WEBROOT_DIR="%kernel.project_dir%/public"
```

# Usage

## File manager API

Documentation will come soon.

## File storage API

Documentation will come soon.

# Notes about migration from `makinacorpus/filechunk-bundle`

 - Environment variables remain the same.

 - `MakinaCorpus\FilechunkBundle\FileManager` becomes `MakinaCorpus\Files\FileManager`,
   a class alias will be registered to allow a smooth migration.

 - You *MUST* upgrade `makinacorpus/filechunk-bundle` to version `>= 3` if you
   want to keep the chunked file upload widget in order to avoid conflicts.

# Credits

This code includes sligthly modified code from Drupal 8.x https://www.drupal.org
project, located in the `./StreamWrapper` directory, all credits to their
original authors.

All remaining code is an original creation of Makina Corpus
https://www.makina-corpus.com
