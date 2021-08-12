<?php

use MediaWiki\Extension\PDBHandler\Utils;

if (!isset($argv[1]) || !file_exists($argv[1])) {
    echo 'Usage: php test.php PDBFILE.pdb'.PHP_EOL;
    exit();
}
define('PDBHANDLER_TEST_FILE', $argv[1]);

require_once dirname(dirname(dirname(dirname(__FILE__)))).'/maintenance/Maintenance.php';

class PDBHandlerConvertTest extends Maintenance
{
    public function __construct()
    {
        parent::__construct();
        $this->mDescription = 'Check PDFHandler';
        $this->setBatchSize(100);
    }

    public function execute()
    {
        $this->doCheck();
        $this->output("Done!\n");
    }

    private function doCheck()
    {
        $input = PDBHANDLER_TEST_FILE;
        $output = preg_replace('/\.pdb$/', '', PDBHANDLER_TEST_FILE).'.png';
        $pdbId = Utils::getPdbId($input);
        Utils::convertToPNG($pdbId, $input, $output);
    }
}

$maintClass = 'PDBHandlerConvertTest';
require_once RUN_MAINTENANCE_IF_MAIN;
