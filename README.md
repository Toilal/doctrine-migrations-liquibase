# doctrine-migrations-liquibase
Generate Liquibase ChangeLog from Doctrine Entities.

## API Usage

```php
/** @var Doctrine\ORM\EntityManager $em */
$em = ...; // Retrieve Doctrine EntityManager as usual in your environment.

$metadata = $em->getMetadataFactory()->getAllMetadata()

/** @var \DOMDocument $doc */
$createDoc = $schemaTool->changeLog($metadata)->getResult();

// Liquibase ChangeLog that can be used on an empty database to build it from scratch.
$echo $createDoc->saveXML();

/** @var \DOMDocument $doc */
$updateDoc = $schemaTool->diffChangeLog($metadata)->getResult();

// Liquibase ChangeLog that can be used on the current database to upgrade it.
$echo $updateDoc->saveXML();
```

## Command Line Usage

To be done ...

## Symfony Command

To be done ...