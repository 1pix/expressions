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
 * Interface which defines the method to implement when creating a hook to post-process parsed expressions.
 *
 * @author Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package TYPO3
 * @subpackage tx_expressions
 */
interface ValuePostProcessorInterface
{
    /**
     * This method must be implemented for post-processing a parsed value.
     *
     * It must return a value too, even if that is the unchanged input value.
     *
     * @param mixed $value The value that resulted from parsing an expression
     * @return mixed The resulting value
     */
    public function postprocessReturnValue($value);
}
