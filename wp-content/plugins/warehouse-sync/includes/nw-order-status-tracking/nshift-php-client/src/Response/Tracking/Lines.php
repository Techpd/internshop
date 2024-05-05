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

class Lines
{
    use SetGetCall;
    public $uuid;
    public $packageWeight;
    public $packageVolume;
    public $goodsTypeId;
    public $recycleCount;
    public $recycleTypeId;
    public $itemNumber;
    public $isDeleted;
    public $references;
    public $packages;
    public $dangerousGoods;
    public $moneyAmounts;
}