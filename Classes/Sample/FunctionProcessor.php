<?php
namespace Cobweb\Expressions\Sample;

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
 * Sample class providing an example user function for post-processing expressions.
 *
 * To activate that function, enter the following is some localconf file:
 *
 * $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['expressions']['customFunction']['offset'] = 'Cobweb\\Expressions\\Sample\\FunctionProcessor->offset';
 *
 * To use it in an expression, enter something like:
 *
 * gp:foo|bar->offset:10
 *
 * This will take the foo[bar] GET/POST variable and offset it by 10
 *
 * @author Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package TYPO3
 * @subpackage tx_expressions
 */
class FunctionProcessor {
	/**
	 * Sample method to process expressions using the function-calling syntax
	 * i.e. this method might be called using:
	 *
	 * gp:foo|bar->offset:10
	 *
	 * The method receives an array of parameters and a reference to a dummy object
	 * (there's no real reference to a calling object, because the calling object is static).
	 * The array of parameters always contains the value from the expression first
	 * (i.e. in $parameters[0]).
	 * Any additional parameters required by the function come next. In this sample's
	 * case, the second parameter ($parameters[1]) is a number by which the value
	 * will be offset.
	 *
	 * The method *must* return the expression's value, even if unchanged.
	 *
	 * @param array	$parameters Indexed list of parameters. The first one is the expression's result.
	 * @param object $parentObject Dummy object. Not usable.
	 * @return mixed The processed value
	 */
	public function offset($parameters, $parentObject) {
		$value = (int)$parameters[0];
		$offset = 0;
		if (isset($parameters[1])) {
			$offset = (int)$parameters[1];
		}
		return $value + $offset;
	}
}
