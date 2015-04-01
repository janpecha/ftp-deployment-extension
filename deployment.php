<?php

namespace JP\FtpDeployment;

$vendorDir = __DIR__ . '/vendor';

if (!is_dir($vendorDir)) {
	$vendorDir = __DIR__ . '/../../../vendor';

	if (!is_dir($vendorDir)) {
		throw new \RuntimeException('Vendor directory not found. Use Composer to get dependencies.');
	}
}

require "$vendorDir/autoload.php";


$runner = new CliRunner;
die($runner->run());
