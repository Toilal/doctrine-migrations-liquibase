# doctrine-migrations-liquibase
Generate Liquibase ChangeLog from Doctrine Entities.

## API Usage

```php
/** @var Doctrine\ORM\EntityManager $em */
$em = ...; // Retrieve Doctrine EntityManager as usual in your environment.

// Create a Liquibase SchemaTool with EntityManager
$schemaTool = new LiquibaseSchemaTool($this->em);

// Create a changelog that can be used on an empty database to build from scratch.
/** @var \DOMDocument $doc */
$createDoc = $schemaTool->changeLog()->getResult();
$echo $createDoc->saveXML();

// Or create a diff changelog that can be used on current database to upgrade it.
/** @var \DOMDocument $doc */
$updateDoc = $schemaTool->diffChangeLog()->getResult();
$echo $updateDoc->saveXML();
```

## Command Line Usage

To be done ...

## Symfony Command

To be done ...