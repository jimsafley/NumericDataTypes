<?php
namespace Timestamp;

use Doctrine\Common\Collections\Criteria;
use Omeka\Module\AbstractModule;
use Timestamp\Entity\TimestampTimestamp;
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
CREATE TABLE timestamp_timestamp (id INT AUTO_INCREMENT NOT NULL, resource_id INT NOT NULL, property_id INT NOT NULL, timestamp BIGINT NOT NULL, INDEX IDX_2BD8E9B89329D25 (resource_id), INDEX IDX_2BD8E9B549213EC (property_id), INDEX property_timestamp (property_id, timestamp), INDEX timestamp (timestamp), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;
ALTER TABLE timestamp_timestamp ADD CONSTRAINT FK_2BD8E9B89329D25 FOREIGN KEY (resource_id) REFERENCES resource (id) ON DELETE CASCADE;
ALTER TABLE timestamp_timestamp ADD CONSTRAINT FK_2BD8E9B549213EC FOREIGN KEY (property_id) REFERENCES property (id) ON DELETE CASCADE;
');
    }

    public function uninstall(ServiceLocatorInterface $services)
    {
        $services->get('Omeka\Connection')->exec('
DROP TABLE IF EXISTS timestamp_timestamp;
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
            [$this, 'saveData']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.advanced_search',
            function (Event $event) {
                $partials = $event->getParam('partials');
                $partials[] = 'common/timestamp-advanced-search';
                $event->setParam('partials', $partials);
            }
        );
    }

    public function prepareResourceForm(Event $event)
    {
        $view = $event->getTarget();
        //~ $view->headLink()->appendStylesheet($view->assetUrl('css/timestamp.css', 'Timestamp'));
        $view->headScript()->appendFile($view->assetUrl('js/timestamp.js', 'Timestamp'));
    }

    /**
     * Save timestamp data to the timestamp table.
     *
     * This clears all existing timestamps and (re)saves them during create and
     * update operations for a resource (item, item set, media). We do this as
     * an easy way to ensure that the timestamps in the timestamp table are in
     * sync with the timestamps in the value table.
     *
     * @param Event $event
     */
    public function saveData(Event $event)
    {
        $entity = $event->getParam('entity');
        if (!$entity instanceof \Omeka\Entity\Resource) {
            return;
        }
        $em = $this->getServiceLocator()->get('Omeka\EntityManager');

        // The resource must already be created for any timestamps to exist.
        if ($entity->getId()) {
            $dql = 'DELETE Timestamp\Entity\TimestampTimestamp t WHERE t.resource = :resource';
            $query = $em->createQuery($dql);
            $query->setParameter('resource', $entity);
            $query->execute();
        }

        // Save or re-save all timestamps of this resource.
        $criteria = Criteria::create()->where(Criteria::expr()->eq('type', 'timestamp'));
        $values = $entity->getValues()->matching($criteria);
        foreach ($values as $value) {
            $timestamp = new TimestampTimestamp;
            $timestamp->setResource($entity);
            $timestamp->setProperty($value->getProperty());
            $timestamp->setTimestamp($value->getValue());
            $em->persist($timestamp);
        }
    }

    public function prepareQuery(Event $event)
    {
        $query = $event->getParam('request')->getContent();
        if (isset($query['ts'])) {
            $qb = $event->getParam('queryBuilder');
            $adapter = $event->getTarget();
            if (isset($query['ts']['before']['ts']) && isset($query['ts']['before']['pid'])) {
                $alias = $adapter->createAlias();
                $qb->leftJoin(
                    'Timestamp\Entity\TimestampTimestamp',
                    $alias,
                    'WITH',
                    $qb->expr()->andX(
                        $qb->expr()->eq("$alias.resource", $adapter->getEntityClass() . '.id'),
                        $qb->expr()->eq("$alias.property", (int) $query['ts']['before']['pid'])
                    )
                );
                $qb->andWhere($qb->expr()->lt(
                    "$alias.timestamp",
                    $adapter->createNamedParameter($qb, (int) $query['ts']['before']['ts'])
                ));
            }
            if (isset($query['ts']['after']['ts']) && isset($query['ts']['after']['pid'])) {
                $alias = $adapter->createAlias();
                $qb->leftJoin(
                    'Timestamp\Entity\TimestampTimestamp',
                    $alias,
                    'WITH',
                    $qb->expr()->andX(
                        $qb->expr()->eq("$alias.resource", $adapter->getEntityClass() . '.id'),
                        $qb->expr()->eq("$alias.property", (int) $query['ts']['after']['pid'])
                    )
                );
                $qb->andWhere($qb->expr()->gt(
                    "$alias.timestamp",
                    $adapter->createNamedParameter($qb, (int) $query['ts']['after']['ts'])
                ));
            }
        }
    }
}
