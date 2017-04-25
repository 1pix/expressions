<?php
namespace Cobweb\Expressions;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Interface which defines the method to implement when creating a special key processor for expressions.
 *
 * @author Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package TYPO3
 * @subpackage tx_expressions
 */
interface KeyProcessorInterface
{
    /**
     * This method must be implemented to handle the "indices" part it receives from the parser.
     *
     * Assuming an expression such as:
     *
     * mykey:foo|bar
     *
     * the method will receive the string foo|bar.
     * It is expected to return a value that makes sense or else throw an exception.
     *
     * @param string $indices The string to be interpreted
     * @return mixed The resulting value
     */
    public function getValue($indices);
}
