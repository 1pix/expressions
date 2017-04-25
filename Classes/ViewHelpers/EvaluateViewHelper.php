<?php
namespace Cobweb\Expressions\ViewHelpers;

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

use Cobweb\Expressions\ExpressionParser;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * This class is a Expressions view helper for the Fluid templating engine.
 *
 * In TypoScript templates there exists a function called “getText”, which makes it possible
 * to retrieve values from a great number of sources (GET/POST, TSFE, rootline, etc.).
 * Extension "expressions" provides a library which aims to reproduce this capability,
 * so that it can be used by developers where Typoscript is not used for whatever reasons.
 *
 * Like for the TypoScript function, an expression – at a minimum – is comprised of a key, a colon (:) and a value.
 * Example: tsfe:id
 *
 * Expression keys reference:
 *
 * config:    get a value from the “config” object, as stored in $TSFE->config['config']
 * date:      get values related to the current time, using formats from the PHP date() function.
 * extra:     see documentation
 * env:       get a value from the environment variables
 * fe_user:   get values from FE User
 * gp:        get a value from either the $_GET or $_POST superglobal arrays
 * page:      get a value related to the current page, as stored in $TSFE->page
 * plugin:    get a TypoScript property for a plugin as stored in $GLOBALS['TSFE']->tmpl->setup['plugin.'].
 * session:   get values from some structure stored in the temporary session
 * strtotime: get a timestamp by interpreting a human-readable date.
 * tsfe:      get a value from the $TSFE global variable
 * vars:      get a value from "internal" variables.
 *
 * More documentation can be found at
 * https://docs.typo3.org/typo3cms/extensions/expressions/2.0.0/User/ExpressionKeys/Index.html
 *
 * = Examples =
 *
 * Assuming that namespace has been declared as follows:
 * {namespace expression = Cobweb\Expressions\ViewHelpers}
 *
 * <code title="Simple string with expression to evaluate">
 * <expression:evaluate>Current page id is \{tsfe:id\}</expression:evaluate>
 * </code>
 * <output>
 * Current page id is 1
 * (assuming the current page has uid = 1, of course)
 * </output>
 * Note the escaped curly braces, so that Fluid is not confused
 *
 * <code title="Inline notation">
 * {expression:evaluate(expression:'Current user is \{fe_user:username\}')}
 * </code>
 * <output>
 * Current user is zaphod
 * (assuming the current FE user's username is "zaphod")
 * </output>
 *
 * @package TYPO3
 * @subpackage tx_expressions
 * @author Fabien Udriot <fabien.udriot@ecodev.ch>
 * @author Francois Suter <typo3@cobweb.ch>
 */
class EvaluateViewHelper extends AbstractViewHelper
{

    /**
     * Evaluates expression throughout the Expression Parser.
     *
     * @param string $expression Expression to be evaluated
     * @return string the evaluated string.
     */
    public function render($expression = null)
    {
        if ($expression === null) {
            $expression = $this->renderChildren();
        }
        // Replace escaped curly braces
        $searches[] = '\{';
        $replaces[] = '{';
        $searches[] = '\}';
        $replaces[] = '}';
        $expression = str_replace($searches, $replaces, $expression);
        // Evaluate and return
        return ExpressionParser::evaluateString($expression);
    }

}
