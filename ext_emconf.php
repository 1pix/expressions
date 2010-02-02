<?php

########################################################################
# Extension Manager/Repository config file for ext: "expressions"
#
# Auto generated 02-02-2010 11:02
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Generic expression parser',
	'description' => 'A library for bringing into extensions something like TypoScript\'s getText function.',
	'category' => 'misc',
	'author' => 'Francois Suter (Cobweb)',
	'author_email' => 'typo3@cobweb.ch',
	'shy' => '',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => '',
	'version' => '1.1.1',
	'constraints' => array(
		'depends' => array(
			'php' => '5.0.0-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
			'context' => '5.0.0-0.0.0',
		),
	),
	'_md5_values_when_last_written' => 'a:11:{s:9:"ChangeLog";s:4:"0bc0";s:10:"README.txt";s:4:"ee2d";s:39:"class.tx_expressions_contextstorage.php";s:4:"67d8";s:31:"class.tx_expressions_parser.php";s:4:"576c";s:16:"ext_autoload.php";s:4:"38a1";s:12:"ext_icon.gif";s:4:"160e";s:17:"ext_localconf.php";s:4:"6d4d";s:14:"doc/manual.sxw";s:4:"f4ce";s:52:"interfaces/interface.tx_expressions_keyprocessor.php";s:4:"6cf4";s:58:"interfaces/interface.tx_expressions_valuepostprocessor.php";s:4:"d945";s:50:"samples/class.tx_expressions_functionProcessor.php";s:4:"c39a";}',
	'suggests' => array(
	),
);

?>