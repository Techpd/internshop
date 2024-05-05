<?php

/*
 * This file is part of the BringApi package.
 *
 * (c) Martin Madsen <crakter@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Crakter\nShift\Response\Tracking;

use Crakter\nShift\SetGetCall;

class Events
{
    use SetGetCall;
    public $uuid;
    public $originId;
    public $configurationUuid;
    public $configurationName;
    public $normalizedStatusId;
    public $normalizedStatusName;
    public $date;
    public $cityName;
    public $postalCode;
    public $country;
    public $moneyAmounts;
    public $details;
}