<?php

/*
 * This file is part of the BringApi package.
 *
 * (c) Martin Madsen <crakter@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Crakter\nShift\Clients;

use GuzzleHttp\ClientInterface;
use Crakter\nShift\DefaultData\ClientUrls;
use Crakter\nShift\Clients\Base;
use Crakter\nShift\Clients\ClientsInterface;
use Crakter\nShift\DefaultData\HttpMethods;
use Crakter\nShift\Exception\nShiftClientException;
use Crakter\nShift\Exception\ApiEntityNotCorrectException;
use Crakter\nShift\Entity\ApiEntityInterface;

/**
 * BringApi Authorization
 *
 * A Class to facility for the Authorization for Bring Api
 *
 * Quick setup: <code>$auth = new Authorization('1234abc-abcd-1234-5678-abcd1234abcd',
 *                                              'john.doe@example.com', 'https://example.com/');</code>
 *
 * @author Martin Madsen <crakter@gmail.com>
 */
class Authorization extends Base implements ClientsInterface
{
    /**
     * @var string $clientUrl    The clients url
     */
    protected $clientUrl = ClientUrls::CONNECT_TOKEN;

    /**
     * @var string $httpMethod  The Method for HTTP
     */
    protected $httpMethod = HttpMethods::POST;

    public function __construct(ApiEntityInterface $apiEntity = null, ClientInterface $client = null)
    {
        parent::__construct($apiEntity, $client);
        //Sets the reponse to XML as default - can be changed to XLS by ->setReturnXls()
        $this->setXForm();
    }

    /**
     * {@inheritdoc}
     */
    public function processClientUrlVariables(): ClientsInterface
    {
        return $this;
    }

    public function getAccessToken(): string
    {
        return $this->toArray()['access_token'];
    }

    /**
     * {@inheritdoc}
     */
    public function checkErrors(): ClientsInterface
    {
        $checkIfNotError = $this->isJson($this->toJson());

        if ($checkIfNotError === false) {
            throw new nShiftClientException($this->toJson());
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
        foreach ($this->getApiEntity()->toArray() as $key => $value) {
            $this->setForm($key, $value);
        }

        return $this;
    }
}
