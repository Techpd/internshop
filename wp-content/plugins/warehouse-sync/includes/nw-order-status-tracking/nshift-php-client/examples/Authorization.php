<?php

/*
 * This file is part of the BringApi package.
 *
 * (c) Martin Madsen <crakter@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once dirname(__DIR__).'/vendor/autoload.php';

use Crakter\nShift\Entity\Connect;
use Crakter\nShift\Entity\Tracking;
use Crakter\nShift\Clients\Authorization;
use Crakter\nShift\Clients\Tracking\TrackingByOrderNumber;

// Gets the environment variables we have set
$clientId = '**';
$clientSecret = '**';

//Sets the contact for the Sender address
$connect = (new Connect)->setClient_id($clientId)->setClient_secret($clientSecret);
// Try to send the booking - or catch the error.
try {
    $authorization = (new Authorization)->setApiEntity($connect)->send();
    print_r($authorization->getAccessToken());
} catch (\Exception $e) {
    print_r($e->getMessage());
}

$token = $authorization->getAccessToken();

//Sets the contact for the Sender address
$query = (new Tracking)
    ->setQuery('CRAFT101016')
    ->setStartDate('2022-09-12T09:07:53+00')
    ->setEndDate('2022-09-18T11:07:53+00')
    ->setPageSize(20)
    ->setPageIndex(0)
    ->setInstallationTags(['3bb2f09c-c2b1-4472-ac77-c9da101c8639'])
    ->setActorTags(['daa19ea2-1b0e-4e6f-acef-6e2d71a70882'])
    ->setCarrierTags([]);
// Try to send the booking - or catch the error.
try {
    $tracking = (new TrackingByOrderNumber)->setToken($token)->setApiEntity($query)->send();
    print_r($tracking->toArray());
} catch (\Exception $e) {
    print_r($e->getMessage());
}