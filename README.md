# Event Store

Opiniated event store implementation in PHP over PostgreSQL.

Please note that it is built over `makinacorpus/goat-query` SQL query builder
and database connector. Any driver that supports PostgreSQL `RETURNING` clause
equivalent will work.

# Setup

First of all, install this package:

```sh
composer install makinacorpus/event-store
```

It is also recommended to chose an UUID implementation:

```sh
composer install ramsey/uuid
```

Or:

```sh
composer install symfony/uid
```

My favorite remains `ramsey/uuid`.

## Symfony

Start by installing the `makinacorpus/goat-query-bundle` Symfony bundle:

```sh
composer install makinacorpus/goat-query-bundle
```

And configure it as documented.

Then register the bundle into your `config/bundles.php` file:

```php
<?php

return [
    // ... Your other bundles.
    MakinaCorpus\EventStore\Bridge\Symfony\EventStoreBundle::class => ['all' => true],
];
```

## Standalone

This is not documented yet, but basically only thing you need to do is to
create an instance implementing `EventStore`.

# Usage

This is not documented yet.

# Status

For now this is alpha quality. It was just exported from deprecated legacy
`makinacorpus/goat` package and need some beta-testing.

Nevertheless, you should now this code is running in production on many
projects for many years.

# Run tests

A docker environement with various containers for various PHP versions is
present in the `sys/` folder. For tests to work in all PHP versions, you
need to run `composer update --prefer-lowest` in case of any failure.

```sh
composer install
composer update --prefer-lowest
cd sys/
./docker-rebuild.sh # Run this only once
./docker-run.sh
```

Additionnaly generate coverage report:

```sh
./docker-coverage.sh
```

HTML coverage report will be generated in `coverage` folder.
