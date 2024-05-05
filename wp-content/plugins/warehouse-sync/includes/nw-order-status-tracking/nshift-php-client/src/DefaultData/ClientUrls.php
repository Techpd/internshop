<?php

/*
 * This file is part of the BringApi package.
 *
 * (c) Martin Madsen <crakter@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Crakter\nShift\DefaultData;

/**
 * BringApi ClientUrls
 *
 * Specify which client urls are available by Bring Api
 *
 * Quick example: <code>ClientUrls::REPORTS_LIST_AVAILABLE_CUSTOMERS</code>
 *
 * @author Martin Madsen <crakter@gmail.com>
 */
abstract class ClientUrls
{
    use \Crakter\nShift\Traits\Validate;

    /**
     * Link to login to nShift API
     */
    const CONNECT_TOKEN = 'https://www.consignorportal.com/idsrv/connect/token';

    /**
     * Links to search consignor portal
     */
    const SHIPMENTS_BY_ORDER_NUMBER = 'https://customer-api.consignorportal.com/ApiGateway/ShipmentData/Operational/Shipments/ByOrderNumber';
    const SHIPMENTS_BY_BARCODE = 'https://customer-api.consignorportal.com/ApiGateway/ShipmentData/Operational/Shipments/byBarcode';

    const SHIPMENT = 'https://customer-api.consignorportal.com/ApiGateway/ShipmentData/Operational/Shipments/%s';
}
