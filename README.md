# doctrine-migrations-liquibase

**UNMAINTAINED**.[Currently maintained drop-in replacement](https://packagist.org/packages/fabiang/doctrine-migrations-liquibase)

Generate Liquibase ChangeLog from Doctrine Entities.

[![Build Status](https://img.shields.io/travis/Toilal/doctrine-migrations-liquibase.svg?style=flat-square)](https://travis-ci.org/Toilal/doctrine-migrations-liquibase)
[![PHP](https://img.shields.io/packagist/php-v/toilal/doctrine-migrations-liquibase.svg?style=flat-square)](https://packagist.org/packages/toilal/doctrine-migrations-liquibase)
[![Version](https://img.shields.io/packagist/v/toilal/doctrine-migrations-liquibase.svg?style=flat-square)](https://packagist.org/packages/toilal/doctrine-migrations-liquibase)
[![Downloads](https://img.shields.io/packagist/dt/toilal/doctrine-migrations-liquibase.svg?style=flat-square)](https://packagist.org/packages/toilal/doctrine-migrations-liquibase)
[![License](https://img.shields.io/packagist/l/toilal/doctrine-migrations-liquibase.svg?style=flat-square)](https://packagist.org/packages/toilal/doctrine-migrations-liquibase)

## API Usage

```php
/** @var Doctrine\ORM\EntityManager $em */
$em = ...; // Retrieve Doctrine EntityManager as usual in your environment.

// Create a Liquibase SchemaTool with EntityManager
$schemaTool = new LiquibaseSchemaTool($this->em);

// Create a changelog that can be used on an empty database to build from scratch.
/** @var \DOMDocument $changeLog */
$changeLog = $schemaTool->changeLog()->getResult();
echo $changeLog->saveXML();

// Or create a diff changelog that can be used on current database to upgrade it.
/** @var \DOMDocument $diffChangeLog */
$diffChangeLog = $schemaTool->diffChangeLog()->getResult();
echo $diffChangeLog->saveXML();
```

## Command Line Usage

To be done ...

## Symfony Command

To be done ...
