<?php

/*
 * This file is part of the BringApi package.
 *
 * (c) Martin Madsen <crakter@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Crakter\nShift\Clients\Booking;

use Crakter\nShift\DefaultData\ClientUrls;
use Crakter\nShift\Clients\Base;
use Crakter\nShift\Clients\ClientsInterface;
use Crakter\nShift\DefaultData\HttpMethods;
use Crakter\nShift\Exception\BringClientException;
use Crakter\nShift\Exception\ApiEntityNotCorrectException;

/**
 * BringApi BookShipment
 *
 * A Client to send request to Bring API book shipment endpoint
 *
 * Quick setup: <code>$bookShipment = new BookShipment();</code>
 *
 * @author Martin Madsen <crakter@gmail.com>
 */
class BookShipment extends Base implements ClientsInterface
{
    /**
     * @var string $clientUrl    The clients url
     */
    protected $clientUrl = ClientUrls::BOOKING_BOOK_SHIPMENTS;

    /**
     * @var string $httpMethod  The Method for HTTP
     */
    protected $httpMethod = HttpMethods::POST;

    /**
     * {@inheritdoc}
     */
    public function processClientUrlVariables(): ClientsInterface
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function checkErrors(): ClientsInterface
    {
        $checkIfNotError = $this->isJson($this->toJson());

        if ($checkIfNotError === false) {
            throw new BringClientException($this->toJson());
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     * @throws ApiEntityNotCorrectException
     */
    public function processEntity(): ClientsInterface
    {
        try {
            $this->getApiEntity();
        } catch (\Throwable $e) {
            throw new ApiEntityNotCorrectException('Api Entity needs to be set.');
        }
        $this->setOptionsJson($this->getApiEntity()->toArray());

        return $this;
    }
}
