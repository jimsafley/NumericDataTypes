<?php
namespace Timestamp\Entity;

use Omeka\Entity\AbstractEntity;
use Omeka\Entity\Property;
use Omeka\Entity\Resource;

/**
 * @Entity
 * @Table(
 *     indexes={
 *         @Index(name="property_timestamp", columns={"property_id", "timestamp"}),
 *         @Index(name="timestamp", columns={"timestamp"}),
 *     }
 * )
 */
class TimestampTimestamp extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @ManyToOne(
     *     targetEntity="Omeka\Entity\Resource"
     * )
     * @JoinColumn(
     *     nullable=false,
     *     onDelete="CASCADE"
     * )
     */
    protected $resource;

    /**
     * @ManyToOne(
     *     targetEntity="Omeka\Entity\Property"
     * )
     * @JoinColumn(
     *     nullable=false,
     *     onDelete="CASCADE"
     * )
     */
    protected $property;

    /**
     * @Column(type="bigint")
     */
    protected $timestamp;

    public function getId()
    {
        return $this->id;
    }

    public function setResource(Resource $resource)
    {
        $this->resource = $resource;
    }

    public function getResource()
    {
        return $this->resource;
    }

    public function setProperty(Property $property)
    {
        $this->property = $property;
    }

    public function getProperty()
    {
        return $this->property;
    }

    public function setTimestamp($timestamp)
    {
        $this->timestamp = (int) $timestamp;
    }

    public function getTimestamp()
    {
        return $this->timestamp;
    }
}
