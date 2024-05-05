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
 * BringApi ReturnFileContentTypes
 *
 * Specify which file content types are available by Bring Api
 *
 * Quick example: <code>ReturnFileContentTypes::JSON</code>
 *
 * @author Martin Madsen <crakter@gmail.com>
 */
abstract class ReturnFileContentTypes
{
    use \Crakter\nShift\Traits\Validate;

    /**
     * Accepted return filecontenttypes from nShift API.
     * All methods are not supportive of all return filetypes.
     */
    const X_FORM = 'application/x-www-form-urlencoded';
    const JSON = 'application/json';
}
