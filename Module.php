<?php
namespace NumericDataTypes;

use Doctrine\Common\Collections\Criteria;
use Omeka\Module\AbstractModule;
use Zend\EventManager\Event;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class Module extends AbstractModule
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function install(ServiceLocatorInterface $services)
    {
        $services->get('Omeka\Connection')->exec('
CREATE TABLE numeric_data_types_integer (id INT AUTO_INCREMENT NOT NULL, resource_id INT NOT NULL, property_id INT NOT NULL, value BIGINT NOT NULL, INDEX IDX_6D39C79089329D25 (resource_id), INDEX IDX_6D39C790549213EC (property_id), INDEX property_value (property_id, value), INDEX value (value), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;
CREATE TABLE numeric_data_types_timestamp (id INT AUTO_INCREMENT NOT NULL, resource_id INT NOT NULL, property_id INT NOT NULL, value BIGINT NOT NULL, INDEX IDX_7367AFAA89329D25 (resource_id), INDEX IDX_7367AFAA549213EC (property_id), INDEX property_value (property_id, value), INDEX value (value), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;
ALTER TABLE numeric_data_types_integer ADD CONSTRAINT FK_6D39C79089329D25 FOREIGN KEY (resource_id) REFERENCES resource (id) ON DELETE CASCADE;
ALTER TABLE numeric_data_types_integer ADD CONSTRAINT FK_6D39C790549213EC FOREIGN KEY (property_id) REFERENCES property (id) ON DELETE CASCADE;
ALTER TABLE numeric_data_types_timestamp ADD CONSTRAINT FK_7367AFAA89329D25 FOREIGN KEY (resource_id) REFERENCES resource (id) ON DELETE CASCADE;
ALTER TABLE numeric_data_types_timestamp ADD CONSTRAINT FK_7367AFAA549213EC FOREIGN KEY (property_id) REFERENCES property (id) ON DELETE CASCADE;
');
    }

    public function uninstall(ServiceLocatorInterface $services)
    {
        $services->get('Omeka\Connection')->exec('
DROP TABLE IF EXISTS numeric_data_types_integer;
DROP TABLE IF EXISTS numeric_data_types_timestamp;
');
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.add.after',
            [$this, 'prepareResourceForm']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.edit.after',
            [$this, 'prepareResourceForm']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\ItemSet',
            'view.add.after',
            [$this, 'prepareResourceForm']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\ItemSet',
            'view.edit.after',
            [$this, 'prepareResourceForm']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Media',
            'view.add.after',
            [$this, 'prepareResourceForm']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Media',
            'view.edit.after',
            [$this, 'prepareResourceForm']
        );
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.search.query',
            [$this, 'prepareQuery']
        );
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.hydrate.post',
            [$this, 'saveNumericData']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.advanced_search',
            function (Event $event) {
                $partials = $event->getParam('partials');
                $partials[] = 'common/numeric-data-types-advanced-search';
                $event->setParam('partials', $partials);
            }
        );
    }

    public function prepareResourceForm(Event $event)
    {
        $view = $event->getTarget();
        //~ $view->headLink()->appendStylesheet($view->assetUrl('css/numeric-data-types.css', 'NumericDataTypes'));
        $view->headScript()->appendFile($view->assetUrl('js/numeric-data-types.js', 'NumericDataTypes'));
    }

    /**
     * Save numeric data to the corresponding number tables.
     *
     * This clears all existing numbers and (re)saves them during create and
     * update operations for a resource (item, item set, media). We do this as
     * an easy way to ensure that the numbers in the number tables are in sync
     * with the numbers in the value table.
     *
     * @param Event $event
     */
    public function saveNumericData(Event $event)
    {
        $entity = $event->getParam('entity');
        if (!$entity instanceof \Omeka\Entity\Resource) {
            // This is not a resource.
            return;
        }

        // @todo: automate the generation of this array
        $dataTypes = [
            'numeric:timestamp' => '\NumericDataTypes\Entity\NumericDataTypesTimestamp',
            'numeric:integer' => '\NumericDataTypes\Entity\NumericDataTypesInteger',
        ];
        $allValues = $entity->getValues();
        foreach ($dataTypes as $dataTypeName => $entityClass) {
            $criteria = Criteria::create()->where(Criteria::expr()->eq('type', $dataTypeName));
            $matchingValues = $allValues->matching($criteria);
            if (!$matchingValues) {
                // This resource has no number values of this type.
                continue;
            }

            $em = $this->getServiceLocator()->get('Omeka\EntityManager');
            $existingNumbers = [];

            if ($entity->getId()) {
                $dql = sprintf('SELECT n FROM %s n WHERE n.resource = :resource', $entityClass);
                $query = $em->createQuery($dql);
                $query->setParameter('resource', $entity);
                $existingNumbers = $query->getResult();
            }
            foreach ($matchingValues as $value) {
                // Avoid ID churn by reusing number rows.
                $number = current($existingNumbers);
                if ($number === false) {
                    // No more number rows to reuse. Create a new one.
                    $number = new $entityClass;
                    $em->persist($number);
                } else {
                    // Null out numbers as we reuse them. Note that existing
                    // numbers are already managed and will update during flush.
                    $existingNumbers[key($existingNumbers)] = null;
                    next($existingNumbers);
                }
                $number->setResource($entity);
                $number->setProperty($value->getProperty());
                $number->setValue($value->getValue());
            }
            // Remove any numbers that weren't reused.
            foreach ($existingNumbers as $existingNumber) {
                if (null !== $existingNumber) {
                    $em->remove($existingNumber);
                }
            }
        }
    }

    /**
     * Prepare numerical queries.
     *
     * numeric => [
     *   ts => [
     *     before => [ts => <timestamp>, pid => <propertyID>],
     *     after => [ts => <timestamp>, pid => <propertyID>],
     *   ],
     *   int => [
     *     lt => [int => <integer>, pid => <propertyID>],
     *     gt => [int => <integer>, pid => <propertyID>],
     *   ],
     * ]
     *
     * @param Event $event
     */
    public function prepareQuery(Event $event)
    {
        $query = $event->getParam('request')->getContent();
        if (!isset($query['numeric'])) {
            return;
        }
        $adapter = $event->getTarget();
        $qb = $event->getParam('queryBuilder');

        if (isset($query['numeric']['ts']['before']['ts']) && isset($query['numeric']['ts']['before']['pid'])) {
            $alias = $adapter->createAlias();
            $qb->leftJoin(
                'NumericDataTypes\Entity\NumericDataTypesTimestamp',
                $alias,
                'WITH',
                $qb->expr()->andX(
                    $qb->expr()->eq("$alias.resource", $adapter->getEntityClass() . '.id'),
                    $qb->expr()->eq("$alias.property", (int) $query['numeric']['ts']['before']['pid'])
                )
            );
            $qb->andWhere($qb->expr()->lt(
                "$alias.value",
                $adapter->createNamedParameter($qb, (int) $query['numeric']['ts']['before']['ts'])
            ));
        }
        if (isset($query['numeric']['ts']['after']['ts']) && isset($query['numeric']['ts']['after']['pid'])) {
            $alias = $adapter->createAlias();
            $qb->leftJoin(
                'NumericDataTypes\Entity\NumericDataTypesTimestamp',
                $alias,
                'WITH',
                $qb->expr()->andX(
                    $qb->expr()->eq("$alias.resource", $adapter->getEntityClass() . '.id'),
                    $qb->expr()->eq("$alias.property", (int) $query['numeric']['ts']['after']['pid'])
                )
            );
            $qb->andWhere($qb->expr()->gt(
                "$alias.value",
                $adapter->createNamedParameter($qb, (int) $query['numeric']['ts']['after']['ts'])
            ));
        }
        if (isset($query['numeric']['int']['lt']['int']) && isset($query['numeric']['int']['lt']['pid'])) {
            $alias = $adapter->createAlias();
            $qb->leftJoin(
                'NumericDataTypes\Entity\NumericDataTypesInteger',
                $alias,
                'WITH',
                $qb->expr()->andX(
                    $qb->expr()->eq("$alias.resource", $adapter->getEntityClass() . '.id'),
                    $qb->expr()->eq("$alias.property", (int) $query['numeric']['int']['lt']['pid'])
                )
            );
            $qb->andWhere($qb->expr()->lt(
                "$alias.value",
                $adapter->createNamedParameter($qb, (int) $query['numeric']['int']['lt']['int'])
            ));
        }
        if (isset($query['numeric']['int']['gt']['int']) && isset($query['numeric']['int']['gt']['pid'])) {
            $alias = $adapter->createAlias();
            $qb->leftJoin(
                'NumericDataTypes\Entity\NumericDataTypesInteger',
                $alias,
                'WITH',
                $qb->expr()->andX(
                    $qb->expr()->eq("$alias.resource", $adapter->getEntityClass() . '.id'),
                    $qb->expr()->eq("$alias.property", (int) $query['numeric']['int']['gt']['pid'])
                )
            );
            $qb->andWhere($qb->expr()->gt(
                "$alias.value",
                $adapter->createNamedParameter($qb, (int) $query['numeric']['int']['gt']['int'])
            ));
        }
    }
}
