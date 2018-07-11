<?php
namespace Timestamp;

use Omeka\Module\AbstractModule;
use Zend\EventManager\Event;
use Zend\EventManager\SharedEventManagerInterface;

class Module extends AbstractModule
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
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

    public function prepareQuery(Event $event)
    {
        $query = $event->getParam('request')->getContent();
        if (isset($query['ts'])) {
            $qb = $event->getParam('queryBuilder');
            $adapter = $event->getTarget();
            if (isset($query['ts']['before']['ts']) && isset($query['ts']['before']['pid'])) {
                $valuesAlias = $adapter->createAlias();
                $qb->leftJoin(
                    $adapter->getEntityClass() . '.values',
                    $valuesAlias,
                    'WITH',
                    $qb->expr()->andX(
                        $qb->expr()->eq("$valuesAlias.type", "'timestamp'"),
                        $qb->expr()->eq("$valuesAlias.property", (int) $query['ts']['before']['pid'])
                    )
                );
                $qb->andWhere($qb->expr()->lt(
                    "CAST_SIGNED($valuesAlias.value)",
                    $adapter->createNamedParameter($qb, (int) $query['ts']['before']['ts'])
                ));
            }
            if (isset($query['ts']['after']['ts']) && isset($query['ts']['after']['pid'])) {
                $valuesAlias = $adapter->createAlias();
                $qb->leftJoin(
                    $adapter->getEntityClass() . '.values',
                    $valuesAlias,
                    'WITH',
                    $qb->expr()->andX(
                        $qb->expr()->eq("$valuesAlias.type", "'timestamp'"),
                        $qb->expr()->eq("$valuesAlias.property", (int) $query['ts']['after']['pid'])
                    )
                );
                $qb->andWhere($qb->expr()->gt(
                    "CAST_SIGNED($valuesAlias.value)",
                    $adapter->createNamedParameter($qb, (int) $query['ts']['after']['ts'])
                ));
            }
        }
    }
}
