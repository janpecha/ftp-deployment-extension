<?php
use Tester\Assert;
use JP\FtpDeployment\Configurator;

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/../../src/Configurator.php';

$configurator = new Configurator;

$configurator->addConfig(array(
	'log' => 'ftp.log',
	'path' => '.',
));

$configurator->addConfig(array(
	'Path' => 'web',
	'remote.host' => 'example.org',
));

$configurator->addConfig(array('remote' => 'ftp://example.com/path'));

Assert::same(array(
	'log' => 'ftp.log',
	'' => array(
		'path' => 'web',
		'remote' => 'ftp://example.org/path',
	),
), $configurator->getConfig());

$configurator->addConfig(array(
	'remote.host' => 'example.org',

	'mysite' => array(
		'remote.scheme' => 'ftps',
	),

	'mysite2' => array(
		'remote.user' => 'user',
	),
));

Assert::same(array(
	'log' => 'ftp.log',
	'mysite' => array(
		'path' => 'web',
		'remote' => 'ftps://example.org/path',
	),
	'mysite2' => array(
		'path' => 'web',
		'remote' => 'ftp://user@example.org/path',
	)
), $configurator->getConfig());
