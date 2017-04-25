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

use Cobweb\Expressions\Exception\Exception;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Utility class to parse strings and evaluate them as expressions or for any subexpressions they may contain.
 *
 * @author Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package TYPO3
 * @subpackage tx_expressions
 */
class ExpressionParser
{
    /**
     * @var string Extension key
     */
    public static $extKey = 'expressions';

    /**
     * @var array Local variables stored in the parser
     */
    public static $vars = array();

    /**
     * @var array Additional values stored in the parser
     */
    public static $extraData = array();

    /**
     * Takes a string and checks for sub-strings inside curly braces {}.
     *
     * Each such substring will be evaluated and replaced.
     * The resulting string is then evaluated or not, depending on the second argument.
     *
     * @param string $string The string to parse
     * @param boolean $doEvaluation TRUE if string should be evaluated before being returned (default)
     * @return string The parsed string
     */
    public static function evaluateString($string, $doEvaluation = true)
    {
        $matches = array();
        $numReplacements = preg_match_all('/(\{.*?\})/', $string, $matches, PREG_SET_ORDER);
        // If there was nothing to match or the matching failed, return the string as is
        $result = $string;
        if (!empty($numReplacements)) {
            $searches = array();
            $replacements = array();
            foreach ($matches as $aMatch) {
                $searches[] = $aMatch[1];
                $expression = substr($aMatch[1], 1, strlen($aMatch[1]) - 2);
                $evaluatedExpression = $expression;
                try {
                    $evaluatedExpression = self::evaluateExpression($expression);
                } catch (Exception $e) {
                    if (TYPO3_DLOG) {
                        GeneralUtility::devLog(
                                'Bad subexpression: ' . $expression . ' (' . $e->getMessage() . ')',
                                'expressions',
                                2
                        );
                    }
                }
                $replacements[] = $evaluatedExpression;
            }
            $result = str_replace($searches, $replacements, $string);
        }
        // Evaluate the string, if necessary
        $finalString = $result;
        if ($doEvaluation) {
            try {
                $finalString = self::evaluateExpression($result);
            } // If the evaluation fails, return the original string
            catch (Exception $e) {
                if (TYPO3_DLOG) {
                    GeneralUtility::devLog(
                            'Could not evaluate string: ' . $result . ' (' . $e->getMessage() . ')',
                            'expressions',
                            2
                    );
                }
            }
        }
        return $finalString;
    }

    /**
     * Evaluates the value of a given expression.
     *
     * The expected syntax of a filter value is key:index1|index2|...
     * Simple values will be used as is.
     *
     * @param string $expression The expression to evaluate
     * @throws Exception
     * @return string The value for the filter
     */
    public static function evaluateExpression($expression)
    {
        $returnValue = '';
        $hasValue = false;
        if (!isset($expression) || $expression === '') {
            throw new Exception('Empty expression received');
        } else {
            // First of all, evaluate any subexpressions that may be contained in the expression
            $parsedExpression = self::evaluateString($expression, false);
            // An expression may contain several expressions as alternative values, separated by a double slash (//)
            $allExpressions = GeneralUtility::trimExplode('//', $parsedExpression, true);
            $numberOfAlternatives = count($allExpressions);
            foreach ($allExpressions as $anExpression) {
                // Decrease the number of remaining alternatives
                $numberOfAlternatives--;
                // Check if there's a function call
                $functions = array();
                if (strpos($anExpression, '->') !== false) {
                    // Split on the function call marker (->)
                    $expressionParts = GeneralUtility::trimExplode('->', $anExpression, true);
                    // The first part is the expression itself
                    $anExpression = array_shift($expressionParts);
                    // All other parts are function definitions
                    $functions = $expressionParts;
                }
                // If there's no colon (:) in the expression, take it to be a literal value and return it as is
                if (strpos($anExpression, ':') === false) {
                    $returnValue = $anExpression;
                    $hasValue = true;
                } else {
                    $subParts = GeneralUtility::trimExplode(':', $anExpression, true);
                    $key = array_shift($subParts);
                    $indices = implode(':', $subParts);
                    if (empty($indices)) {
                        throw new Exception('No indices in expression: ' . $expression);
                    }
                    $key = strtolower($key);
                    switch ($key) {
                        // Search for a value in the TSFE
                        case 'tsfe':
                            if (TYPO3_MODE === 'FE') {
                                try {
                                    $returnValue = self::getValue($GLOBALS['TSFE'], $indices);
                                    $hasValue = true;
                                } catch (Exception $e) {
                                    continue 2;
                                }
                            } else {
                                // Throw exception, but only if we have run out of alternatives
                                if ($numberOfAlternatives === 0) {
                                    throw new Exception('TSFE not available in this mode (' . TYPO3_MODE . ')');
                                }
                            }
                            break;
                        // Search for a value in the page record
                        // This is a convenience shortcut, as the page record is in the TSFE
                        case 'page':
                            if (TYPO3_MODE === 'FE') {
                                try {
                                    $returnValue = self::getValue($GLOBALS['TSFE']->page, $indices);
                                    $hasValue = true;
                                } catch (Exception $e) {
                                    continue 2;
                                }
                            } else {
                                // Throw exception, but only if we have run out of alternatives
                                if ($numberOfAlternatives === 0) {
                                    throw new Exception('TSFE->page not available in this mode (' . TYPO3_MODE . ')');
                                }
                            }
                            break;
                        // Search for a value in the template configuration
                        // This is a convenience shortcut, as the template configuration is in the TSFE
                        case 'config':
                            if (TYPO3_MODE === 'FE') {
                                try {
                                    $returnValue = self::getValue($GLOBALS['TSFE']->config['config'], $indices);
                                    $hasValue = true;
                                } catch (Exception $e) {
                                    continue 2;
                                }
                            } else {
                                // Throw exception, but only if we have run out of alternatives
                                if ($numberOfAlternatives === 0) {
                                    throw new Exception('TSFE->config not available in this mode (' . TYPO3_MODE . ')');
                                }
                            }
                            break;
                        // Search for a value in the merged GET and POST arrays
                        case 'plugin':
                            if (TYPO3_MODE === 'FE') {
                                try {
                                    $returnValue = self::getValue($GLOBALS['TSFE']->tmpl->setup['plugin.'], $indices);
                                    $hasValue = true;
                                } catch (Exception $e) {
                                    continue 2;
                                }
                            } else {
                                // Throw exception, but only if we have run out of alternatives
                                if ($numberOfAlternatives === 0) {
                                    throw new Exception('Plugin setup not available in this mode (' . TYPO3_MODE . ')');
                                }
                            }
                            break;
                        // Search for a value in the merged GET and POST arrays
                        case 'gp':
                            try {
                                $returnValue = self::getValue(
                                        array_merge(
                                                GeneralUtility::_GET(),
                                                GeneralUtility::_POST()
                                        ),
                                        $indices
                                );
                                $hasValue = true;
                            } catch (Exception $e) {
                                continue 2;
                            }
                            break;
                        // Search for a value in the local variables
                        case 'vars':
                            try {
                                $returnValue = self::getValue(self::$vars, $indices);
                                $hasValue = true;
                            } catch (Exception $e) {
                                continue 2;
                            }
                            break;
                        // Search for a value in the extra data
                        case 'extra':
                            try {
                                $returnValue = self::getValue(self::$extraData, $indices);
                                $hasValue = true;
                            } catch (Exception $e) {
                                continue 2;
                            }
                            break;
                        // Calculate a value using the PHP date() function
                        case 'date':
                            $returnValue = date($indices);
                            $hasValue = true;
                            break;
                        case 'strtotime':
                            $value = strtotime($indices);
                            if ($value === false || $value === -1) {
                                throw new Exception('Date string could not be parsed: ' . $indices);
                            }
                            $returnValue = $value;
                            $hasValue = true;
                            break;
                        // Get data from the session
                        // The session key is the first segment after the "session" keyword
                        case 'session':
                            $segments = GeneralUtility::trimExplode('|', $indices, true);
                            $cacheKey = array_shift($segments);
                            $indices = implode('|', $segments);
                            $cache = $GLOBALS['TSFE']->fe_user->getKey('ses', $cacheKey);
                            if (empty($cache)) {
                                // Throw exception, but only if we have run out of alternatives
                                if ($numberOfAlternatives === 0) {
                                    throw new Exception('No session data found for expression: ' . $expression);
                                }
                                continue 2;
                            }
                            try {
                                $returnValue = self::getValue($cache, $indices);
                                $hasValue = true;
                            } catch (Exception $e) {
                                continue 2;
                            }
                            break;
                        // Search for a value in FE User
                        case 'fe_user':
                            if (TYPO3_MODE === 'FE') {
                                try {
                                    $returnValue = self::getValue($GLOBALS['TSFE']->fe_user->user, $indices);
                                    $hasValue = true;
                                } catch (Exception $e) {
                                    continue 2;
                                }
                            } else {
                                // Throw exception, but only if we have run out of alternatives
                                if ($numberOfAlternatives === 0) {
                                    throw new Exception('TSFE->fe_user not available in this mode (' . TYPO3_MODE . ')');
                                }
                            }
                            break;
                        // Search for a value in the environment variables as returned by GeneralUtility::getIndpEnv()
                        case 'env':
                            $returnValue = GeneralUtility::getIndpEnv(strtoupper($indices));
                            $hasValue = true;
                            break;
                        // If none of the standard keys matched, try looking for a hook for that given key
                        default:
                            if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][self::$extKey]['keyProcessor'][$key])) {
                                /** @var $keyProcessor KeyProcessorInterface */
                                $keyProcessor = GeneralUtility::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][self::$extKey]['keyProcessor'][$key]);
                                if ($keyProcessor instanceof KeyProcessorInterface) {
                                    $returnValue = $keyProcessor->getValue($indices);
                                    $hasValue = true;
                                }
                            }
                            break;
                    }
                }
                // If a value was found, process it and exit the loop
                if ($hasValue) {
                    // Call functions, if any
                    if (count($functions) > 0) {
                        foreach ($functions as $functionDefinition) {
                            try {
                                $returnValue = self::processFunctionCall($returnValue, $functionDefinition);
                            } // Do nothing on exceptions, value is unchanged
                            catch (Exception $e) {
                                continue;
                            }
                        }
                    }
                    break;
                }
            }
        }
        // If a value was found, call a post-processing hook and return the value
        if ($hasValue) {
            if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][self::$extKey]['postprocessReturnValue'])) {
                foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][self::$extKey]['postprocessReturnValue'] as $className) {
                    /** @var $postProcessor ValuePostProcessorInterface */
                    $postProcessor = GeneralUtility::getUserObj($className);
                    if ($postProcessor instanceof ValuePostProcessorInterface) {
                        $returnValue = $postProcessor->postprocessReturnValue($returnValue);
                    }
                }
            }
            return $returnValue;

            // No value was found, throw an exception
        } else {
            throw new Exception('No value found for expression: ' . $expression);
        }
    }

    /**
     * Gets a value from inside a multi-dimensional array or object.
     *
     * NOTE: this code is largely inspired by \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::getGlobal().
     *
     * @param mixed $source Array or object to look into
     * @param string $indices "Path" of indices inside the multi-dimensional array, of the form index1|index2|...
     * @throws Exception
     * @return mixed Whatever value was found in the array, but it should be a simple type
     */
    protected static function getValue($source, $indices)
    {
        $value = $source;
        if (empty($indices)) {
            throw new Exception('No key given for source');
        } else {
            $indexList = GeneralUtility::trimExplode('|', $indices, true);
            foreach ($indexList as $key) {
                if (is_object($value) && isset($value->$key) && $value->$key !== '') {
                    $value = $value->$key;
                } elseif (is_array($value) && isset($value[$key]) && $value[$key] !== '') {
                    $value = $value[$key];
                } else {
                    throw new Exception('Key ' . $indices . ' not found in source');
                }
            }
        }
        return $value;
    }

    /**
     * Handles the function calls that can be made inside expressions.
     *
     * NOTE: if the function cannot be called, the original value is returned untouched.
     *
     * @param mixed $value The value to pass to the function (the result of an expression)
     * @param string $functionDefinition The definition of the function to call in the appropriate syntax
     * @return mixed The value, as processed by the function
     */
    static protected function processFunctionCall($value, $functionDefinition)
    {
        // Initializations
        $arguments = array();
        // Separate function key and list of arguments
        list($function, $argumentsString) = GeneralUtility::trimExplode(':', $functionDefinition, true);
        // Get arguments as array
        if (isset($argumentsString) && $argumentsString !== '') {
            $arguments = GeneralUtility::trimExplode(',', $argumentsString, true);
        }
        // Execute the function, recursively if value is an array
        if (is_array($value)) {
            $processedValue = self::executeFunctionOnArray($value, $function, $arguments);
        } else {
            $processedValue = self::executeFunctionOnItem($value, $function, $arguments);
        }
        return $processedValue;
    }

    /**
     * Loops on all items in an array and executes the function on it
     * (or calls itself recursively if item is itself an array).
     *
     * @param array $value List of values to call the function on
     * @param string $function The key of the function to call
     * @param array $arguments The list of arguments to pass in the function call
     * @return array The processed array of values
     */
    static protected function executeFunctionOnArray(array $value, $function, $arguments)
    {
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = self::executeFunctionOnArray($item, $function, $arguments);
            } else {
                $value[$key] = self::executeFunctionOnItem($item, $function, $arguments);
            }
        }
        return $value;
    }

    /**
     * Calls the processing function on a given value.
     *
     * @param mixed $value Value to call the function on
     * @param string $function The key of the function to call
     * @param array $arguments The list of arguments to pass in the function call
     * @throws Exception
     * @return array The processed values
     */
    static protected function executeFunctionOnItem($value, $function, $arguments)
    {
        $processedValue = $value;
        // Execute function
        switch ($function) {
            case 'fullQuoteStr':
                if (count($arguments) < 1) {
                    throw new Exception('fullQuoteStr() requires 1 argument (table name)');
                } elseif (!isset($GLOBALS['TYPO3_DB'])) {
                    throw new Exception('TYPO3_DB object not available');
                } else {
                    $processedValue = self::getDatabaseConnection()->fullQuoteStr($value, $arguments[0]);
                }
                break;
            case 'quoteStr':
                if (count($arguments) < 1) {
                    throw new Exception('quoteStr() requires 1 argument (table name)');
                } elseif (!isset($GLOBALS['TYPO3_DB'])) {
                    throw new Exception('TYPO3_DB object not available');
                } else {
                    $processedValue = self::getDatabaseConnection()->quoteStr($value, $arguments[0]);
                }
                break;
            case 'strip_tags':
                $functionArguments = array($value);
                if (isset($arguments[0])) {
                    $functionArguments[] = $arguments[0];
                }
                $processedValue = call_user_func_array('strip_tags', $functionArguments);
                break;
            case 'removeXSS':
                $processedValue = GeneralUtility::removeXSS($value);
                break;
            case 'intval':
                $functionArguments = array($value);
                if (isset($arguments[0])) {
                    $functionArguments[] = $arguments[0];
                }
                $processedValue = call_user_func_array('intval', $functionArguments);
                break;
            case 'floatval':
                $processedValue = (float)$value;
                break;
            case 'boolean':
                $processedValue = !empty($value);
                break;
            case 'hsc':
                $functionArguments = array($value);
                if (count($arguments) > 0) {
                    foreach ($arguments as $item) {
                        $functionArguments[] = $item;
                    }
                }
                $processedValue = call_user_func_array('htmlspecialchars', $functionArguments);
                break;
            case 'strftime':
                if (count($arguments) < 1) {
                    throw new Exception('strftime() requires 1 argument (date format)');
                } else {
                    $processedValue = strftime($arguments[0], $value);
                }
                break;

            // If no standard key matches, try to call user-defined function
            default:
                if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][self::$extKey]['customFunction'][$function])) {
                    $functionArguments = array($value);
                    if (count($arguments) > 0) {
                        foreach ($arguments as $item) {
                            $functionArguments[] = $item;
                        }
                    }
                    // Call user function
                    // Note the last parameter: since this class is purely static,
                    // a reference to it cannot be passed to the user function,
                    // so we instantiate a dummy object instead
                    $processedValue = GeneralUtility::callUserFunction(
                            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][self::$extKey]['customFunction'][$function],
                            $functionArguments,
                            new \stdClass
                    );
                }
                break;
        }
        return $processedValue;
    }

    /**
     * This method can be used to store whatever local variables make sense into the parser, for later retrieval
     * In the case of a FE plugin, it would be the piVars
     *
     * @param array $vars Array of values
     * @param boolean $reset TRUE if self::$vars must be reset
     * @return void
     */
    public static function setVars($vars, $reset = false)
    {
        if (is_array($vars)) {
            if ($reset) {
                self::$vars = $vars;
            } else {
                ArrayUtility::mergeRecursiveWithOverrule(self::$vars, $vars);
            }
        }
    }

    /**
     * Stores additional variables into the parser, that should not be mixed up
     * with the local variables stored in self::$vars.
     *
     * @param array $data Array of values
     * @param boolean $reset TRUE if self::$extraData must be reset
     * @return void
     * @see \Cobweb\Expressions\Parser::setVars()
     */
    public static function setExtraData($data, $reset = false)
    {
        if (is_array($data)) {
            if ($reset) {
                self::$extraData = $data;
            } else {
                ArrayUtility::mergeRecursiveWithOverrule(self::$extraData, $data);
            }
        }
    }

    /**
     * Returns the global database object.
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected static function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
