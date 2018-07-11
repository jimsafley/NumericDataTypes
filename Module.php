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
            function (Event $event) {
                $query = $event->getParam('request')->getContent();
                if (isset($query['ts'])) {
                    $qb = $event->getParam('queryBuilder');
                    $adapter = $event->getTarget();
                    if (isset($query['ts']['lt']['ts']) && isset($query['ts']['lt']['pid'])) {
                        $valuesAlias = $adapter->createAlias();
                        $qb->leftJoin(
                            $adapter->getEntityClass() . '.values',
                            $valuesAlias,
                            'WITH',
                            $qb->expr()->andX(
                                $qb->expr()->eq("$valuesAlias.type", "'timestamp'"),
                                $qb->expr()->eq("$valuesAlias.property", (int) $query['ts']['lt']['pid'])
                            )
                        );
                        $qb->andWhere($qb->expr()->lt(
                            "$valuesAlias.value",
                            $adapter->createNamedParameter($qb, $query['ts']['lt']['ts'])
                        ));
                    }
                    if (isset($query['ts']['gt']['ts']) && isset($query['ts']['gt']['pid'])) {
                        $valuesAlias = $adapter->createAlias();
                        $qb->leftJoin(
                            $adapter->getEntityClass() . '.values',
                            $valuesAlias,
                            'WITH',
                            $qb->expr()->andX(
                                $qb->expr()->eq("$valuesAlias.type", "'timestamp'"),
                                $qb->expr()->eq("$valuesAlias.property", (int) $query['ts']['gt']['pid'])
                            )
                        );
                        $qb->andWhere($qb->expr()->gt(
                            "$valuesAlias.value",
                            $adapter->createNamedParameter($qb, $query['ts']['gt']['ts'])
                        ));
                    }
                }
                //~ echo $qb->getDQL();exit;
            }
        );
    }

    public function prepareResourceForm(Event $event)
    {
        $view = $event->getTarget();
        //~ $view->headLink()->appendStylesheet($view->assetUrl('css/timestamp.css', 'Timestamp'));
        $view->headScript()->appendFile($view->assetUrl('js/timestamp.js', 'Timestamp'));
    }
}
