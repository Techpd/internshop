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
use GuzzleHttp\Client;
use Crakter\nShift\DefaultData\ReturnFileContentTypes;
use Crakter\nShift\DefaultData\ReturnFileTypes;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Crakter\nShift\Exception\nShiftClientException;
use Crakter\nShift\DefaultData\HttpMethods;
use Crakter\nShift\DefaultData\Languages;
use Crakter\nShift\Entity\ApiEntityInterface;

/**
 * BringApi Base Client
 *
 * A facility for Client classes to be extended from
 *
 * Quick setup: <code>class ReportsGenerateReport extends Base {}</code>
 *
 * @author Martin Madsen <crakter@gmail.com>
 */
abstract class Base
{
    /**
     * @var string $token Send this as Bearer
     */
    protected $token = null;

    /**
     * @var object $client Implements the ClientsInterface
     */
    protected $client;

    /**
     * @var string $clientUrl    The clients url
     */
    protected $clientUrl;
    /**
     * @var array $clientUrlVariables    The clients url Variables.
     */
    protected $clientUrlVariables = [];

    /**
     * @var string $response     The response
     */
    protected $response;

    /**
     * @var array $options     The options for Request
     */
    protected $options;

    /**
     * @var string $httpMethod  The Method for HTTP
     */
    protected $httpMethod;

    /**
     * @var ApiEntityInterface $apiEntity   Instance of ApiEntityInterface with all request body
     */
    protected $apiEntity;

    /**
     * @var bool $isFullAddress   Set if URL is fixed and not parsed by UrlParameters
     */
    protected $isFullAddress = false;

    /**
     * @var string $alternativeAuthorizedUrl    Alternative url if we are logged in (required for tracking client)
     */
    protected $alternativeAuthorizedUrl;

    /**
     * Set authorizationModule if provided and ClientInterface if provided, defaults to default GuzzleHttp Client
     * @param  ApiEntityInterface     $apiEntity
     * @param  AuthorizationInterface $authorizationModule
     * @param  ClientInterface        $client
     * @return ClientsInterface       All clients must implement ClientsInterface
     */
    public function __construct(ApiEntityInterface $apiEntity = null, ClientInterface $client = null)
    {
        if ($client === null) {
            $client = new Client();
        }
        $this->client = $client;
        //Not all clients need ApiEntity.
        if ($apiEntity != null) {
            $this->apiEntity = $apiEntity;
        }

        return $this;
    }

    public function setToken(string $token): ClientsInterface
    {
        $this->token = $token;

        return $this;
    }

    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set Client
     * @param  ClientInterface  $client client interface
     * @return ClientsInterface All clients must implement ClientsInterface
     */
    public function setClient(ClientInterface $client): ClientsInterface
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Get Client
     * @return ClientInterface All clients must implement ClientsInterface
     */
    public function getClient(): ClientInterface
    {
        return $this->client;
    }

    /**
     * Set Accept-Language
     * @param  string                        $value value of option
     * @throws InputValueNotAllowedException if value does not existing
     * @return ClientsInterface              All clients must implement ClientsInterface
     */
    public function setAcceptLanguage(string $value): ClientsInterface
    {
        $this->options['headers']['Accept-Language'] = Languages::get($value);

        return $this;
    }

    /**
     * Set header for request
     * @param  string           $option options name
     * @param  string           $value  value of option
     * @return ClientsInterface All clients must implement ClientsInterface
     */
    public function setHeader(string $option, string $value): ClientsInterface
    {
        $this->options['headers'][$option] = $value;

        return $this;
    }

    public function setForm(string $option, string $value): ClientsInterface
    {
        $this->options['form_params'][$option] = $value;

        return $this;
    }

    /**
     * Set query for request
     * @param  array            $value value of option
     * @return ClientsInterface All clients must implement ClientsInterface
     */
    public function setOptionsQuery(array $value): ClientsInterface
    {
        foreach ($value as &$var) {
            if (is_bool($var)) {
                $var = $var ? 'true' : 'false';
            }
        }
        $this->options['query'] = preg_replace('/%5B(?:[0-9]|[1-9][0-9]+)%5D=/', '=', http_build_query($value));

        return $this;
    }

    /**
     * Set json for request
     * @param  string           $value value of option
     * @return ClientsInterface All clients must implement ClientsInterface
     */
    public function setOptionsJson(array $value): ClientsInterface
    {
        $this->options['json'] = $value;

        return $this;
    }

    /**
     * Set options for request
     * @param  string           $value value of option
     * @return ClientsInterface All clients must implement ClientsInterface
     */
    public function setOptions(array $value): ClientsInterface
    {
        $this->options = $value;

        return $this;
    }

    /**
     * Gets Options to be sent with request
     * @return array
     */
    public function getOptions(): array
    {
        //$this->options['debug'] = true;

        return $this->options;
    }

    /**
     * Sets the Header to say we want JSON back from Bring API
     * @return ClientsInterface All clients must implement ClientsInterface
     */
    public function setReturnJson(): ClientsInterface
    {
        $this->setHeader('Accept', ReturnFileContentTypes::JSON);
        $this->setHeader('Content-Type', ReturnFileContentTypes::JSON);

        return $this;
    }

    /**
     * Sets the Header to say that the Content-Type is 
     * @return ClientsInterface All clients must implement ClientsInterface
     */
    public function setXForm(): ClientsInterface
    {
        $this->setHeader('Content-Type', ReturnFileContentTypes::X_FORM);

        return $this;
    }

    /**
     * Sets the Client URL for request
     * @param  string           $clientUrl     Url to be sent with request
     * @param  bool             $isFullAddress Set if URL is full address - not parsed by UrlVariables
     * @return ClientsInterface All clients must implement ClientsInterface
     */
    public function setClientUrl(string $clientUrl, bool $isFullAddress = false): ClientsInterface
    {
        $this->clientUrl = $clientUrl;
        $this->setIsFullAddress($isFullAddress);

        return $this;
    }

    /**
     * Gets the Client URL for request
     * @return string
     */
    public function getClientUrl(): string
    {
        return $this->clientUrl;
    }

    /**
     * Sets the $isFullAddress variable so that we can supply with full address.
     * @param  bool             $isFullAddress Set if URL is full address - not parsed by UrlVariables
     * @return ClientsInterface All clients must implement ClientsInterface
     */
    public function setIsFullAddress(bool $isFullAddress): ClientsInterface
    {
        $this->isFullAddress = $isFullAddress;

        return $this;
    }

    /**
     * Gets the $isFullAddress variable
     * @return bool $isFullAddress  if URL is full address true/false - not parsed by UrlVariables
     */
    public function getIsFullAddress(): bool
    {
        return $this->isFullAddress;
    }

    /**
     * Sets the Variables for Client URL
     * @param  array            ...$args
     * @return ClientsInterface All clients must implement ClientsInterface
     */
    public function setClientUrlVariables(...$args): ClientsInterface
    {
        $this->clientUrlVariables = $args;

        return $this;
    }

    /**
     * Gets the Variables for Client URL, used in send function.
     * @return array
     */
    public function getClientUrlVariables(): array
    {
        return $this->clientUrlVariables;
    }

    /**
     * Set the response from request
     * @param  Response         $response PSR-7 complient Response Interface.
     * @return ClientsInterface All clients must implement ClientsInterface
     */
    public function setResponse(Response $response): ClientsInterface
    {
        $this->response = $response;

        return $this;
    }

    /**
     * get the response from request
     * @return Response PSR-7 complient Response Interface.
     */
    public function getResponse(): Response
    {
        return $this->response;
    }

    /**
     * Returns the response in JSON format
     * @return string
     */
    public function toJson(): string
    {
        return (string) $this->getResponse()->getBody();
    }

    /**
     * Check if string is compatible json string
     * @param  string $string JSON string
     * @return bool   true if correct json, false if not
     */
    public function isJson(string $string): bool
    {
        $array = json_decode($string, true);

        return (json_last_error() === JSON_ERROR_NONE) && is_array($array);
    }

    /**
     * Returns the response in Array format
     * @return array
     */
    public function toArray(): array
    {
        return json_decode($this->toJson(), true);
    }

    /**
     * toXml returnes the object at hand in form of an xml string
     * @param  string $xmlRoot Name of element root
     * @return string
     */
    public function toXml(string $xmlRoot): string
    {
        $xml = new \SimpleXMLElement("<{$xmlRoot}/>");
        $result = $this->toArray();
        $this->recursiveXml($xml, $result);

        $dom = new \DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());

        return $dom->saveXML();
    }

    /**
     * Recursive XML function to loop through all values.
     * @param  SimpleXMLElement $object
     * @param  array            $data
     * @return void
     */
    private function recursiveXml(\SimpleXMLElement $object, array $data): bool
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $new_object = $object->addChild($key);
                $this->recursiveXml($new_object, $value);
            } else {
                $object->addChild($key, $value);
            }
        }

        return true;
    }

    /**
     * Sets the HTTP method to GET
     * @return ClientsInterface All clients must implement ClientsInterface
     */
    public function setGet(): ClientsInterface
    {
        $this->setHttpMethod(HttpMethods::GET);

        return $this;
    }

    /**
     * Sets the HTTP method to POST
     * @return ClientsInterface All clients must implement ClientsInterface
     */
    public function setPost(): ClientsInterface
    {
        $this->setHttpMethod(HttpMethods::POST);

        return $this;
    }

    /**
     * Sets the HTTP method
     * @param  string           $name HTTP method to be sent with request(POST, GET)
     * @return ClientsInterface All clients must implement ClientsInterface
     */
    public function setHttpMethod(string $name): ClientsInterface
    {
        $this->httpMethod = $name;

        return $this;
    }

    /**
     * Gets the HTTP method
     * @return string
     */
    public function getHttpMethod(): string
    {
        return $this->httpMethod;
    }

    /**
     * Sets the ApiEntity
     * @param  ApiEntityInterface $apiEntity Entity must implement ApiEntityInterface
     * @return ClientsInterface   All clients must implement ClientsInterface
     */
    public function setApiEntity(ApiEntityInterface $apiEntity): ClientsInterface
    {
        $this->apiEntity = $apiEntity;

        return $this;
    }

    /**
     * Gets the ApiEntity
     * @return ApiEntityInterface Entity must implement ApiEntityInterface
     */
    public function getApiEntity(): ApiEntityInterface
    {
        return $this->apiEntity;
    }

    /**
     * Sets the AlternativeAuthorizedUrl
     * @param  string           $alternativeAuthorizedUrl
     * @return ClientsInterface All clients must implement ClientsInterface
     */
    public function setAlternativeAuthorizedUrl(string $alternativeAuthorizedUrl): ClientsInterface
    {
        $this->alternativeAuthorizedUrl = $alternativeAuthorizedUrl;

        return $this;
    }

    /**
     * Gets the AlternativeAuthorizedUrl
     * @return string
     */
    public function getAlternativeAuthorizedUrl(): string
    {
        return $this->alternativeAuthorizedUrl ?? '';
    }

    /**
     * Sends the request to Bring API with processed information.
     * @see Base::setReturnJson()
     * @see Base::setClientUrl()
     * @see Base::setHeader()
     * @see Base::getClientUrlVariables()
     * @see Base::getHttpMethod()
     * @see Base::getClientUrl()
     * @see Base::getOptions()
     * @see AuthorizationInterface::getClientUrl()
     * @see AuthorizationInterface::getClientId()
     * @see AuthorizationInterface::getApiKey()
     * @see AuthorizationInterface::hasAuthorization()
     * @see ClientInterface::request()
     * @see Response::getResponse()
     * @return ClientsInterface All clients must implement ClientsInterface
     */
    public function send()
    {
        if ($this->getToken() !== null) {
            $this->setHeader('Authorization', 'Bearer ' . $this->getToken());
        }
        if ($this->getIsFullAddress() === false) {
            if (empty($this->getClientUrlVariables())) {
                $this->processClientUrlVariables();
            }
            if (!empty($this->getClientUrlVariables())) {
                $this->setClientUrl(vsprintf($this->getClientUrl(), $this->getClientUrlVariables()));
            }
        }
        $this->processEntity();
        try {
            $request = $this->client->request(
                $this->getHttpMethod(),
                $this->getClientUrl(),
                $this->getOptions()
            );
        } catch (ClientException $e) {
            throw new nShiftClientException(
                sprintf('Error returned from nShift API when creating from %s. Error message from nShift: %s', get_called_class(), $e->getResponse()->getBody(true)),
                0,
                $e
            );
        } catch (RequestException $e) {
            throw new nShiftClientException(
                sprintf('Error returned from nShift API when creating from %s. Error message from nShift: %s', get_called_class(), $e->getMessage()),
                0,
                $e
            );
        }
        $this->setResponse($request);

        return $this;
    }
}
