<?php

/*
 * This file is part of the BringApi package.
 *
 * (c) Martin Madsen <crakter@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Crakter\nShift\Clients\Tracking;

use GuzzleHttp\ClientInterface;
use Crakter\nShift\DefaultData\ClientUrls;
use Crakter\nShift\Clients\Base;
use Crakter\nShift\Clients\ClientsInterface;
use Crakter\nShift\DefaultData\HttpMethods;
use Crakter\nShift\Exception\ApiEntityNotCorrectException;
use Crakter\nShift\Entity\ApiEntityInterface;

/**
 * BringApi GenerateReport
 *
 * A Client to send request to Bring API generate report endpoint
 *
 * Quick setup: <code>$generateReport = new GenerateReport();</code>
 *
 * @author Martin Madsen <crakter@gmail.com>
 */
class TrackingByUuid extends Base implements ClientsInterface
{
    /**
     * @var string $clientUrl    The clients url
     */
    protected $clientUrl = ClientUrls::SHIPMENT;

    /**
     * @var string $httpMethod  The Method for HTTP
     */
    protected $httpMethod = HttpMethods::GET;

    /**
     * uuid to be used in url
     * @var string $uuid
     */
    protected $uuid;

    public function __construct(ApiEntityInterface $apiEntity = null, ClientInterface $client = null)
    {
        parent::__construct($apiEntity, $client);
        $this->setReturnJson();
    }

    /**
     * Sets the uuid for clientUrl
     * @param string $uuid
     * @example da1dce0a-f336-46de-b8e9-c8a5566f4756
     * @return ClientsInterface All clients must implement ClientsInterface
     */
    public function setUuid(string $uuid): ClientsInterface
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * Gets the customerId for clientUrl
     * @example PARCELS_NORWAY-00012341234
     * @return string
     */
    public function getUuid(): string
    {
        return $this->uuid;
    }

    /**
     * {@inheritdoc}
     */
    public function processClientUrlVariables(): ClientsInterface
    {
        $this->setClientUrlVariables($this->getUuid());

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function checkErrors(): ClientsInterface
    {
        $array = $this->toArray();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function processEntity(): ClientsInterface
    {
        return $this;
    }
}
