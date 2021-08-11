<?php

if (!isset($argv[1]) || !file_exists($argv[1])) {
    echo 'Usage: php test.php PDBFILE.pdb'.PHP_EOL;
    exit();
}
define('PDBHANDLER_TEST_FILE', $argv[1]);

require_once dirname(dirname(dirname(__FILE__))).'/maintenance/Maintenance.php';

class PDBCheck extends Maintenance
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
        MediaWiki\Extension\PDBHandler\Utils::convertToPNG($input, $output);
    }
}

$maintClass = 'PDBCheck';
require_once RUN_MAINTENANCE_IF_MAIN;
