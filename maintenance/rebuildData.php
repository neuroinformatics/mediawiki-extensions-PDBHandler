<?php

use MediaWiki\Extension\PDBHandler\Utils;
use MediaWiki\MediaWikiServices;

require_once dirname(dirname(dirname(dirname(__FILE__)))).'/maintenance/Maintenance.php';

class PDBHandlerRebuildData extends Maintenance
{
    private $dbw;

    public function __construct()
    {
        parent::__construct();
        $this->mDescription = 'Script to rebuild PDFHandler media data.';
    }

    public function execute()
    {
        $this->dbw = $this->getDB(DB_MASTER);

        $this->doRebuild();
        $this->output("Done!\n");
    }

    private function doRebuild()
    {
        $service = MediaWikiServices::getInstance();
        $magic = $service->getMimeAnalyzer();
        $this->output('PDBHandler: rebuild media data'."\n");
        $result = $this->dbw->select('image', ['img_name'], ['img_name LIKE \'%.PDB\' OR img_name LIKE \'%.pdb\''], __METHOD__);
        $this->output(sprintf("'image' table: %d number of PDB media entries found.\n", $result->numRows()));
        $n = 1;
        foreach ($result as $row) {
            $success = false;
            $name = $row->img_name;
            $file = $service->getRepoGroup()->findFile($name);
            $fpath = $file->getLocalRefPath();
            $pdbId = Utils::getPdbId($fpath);
            if ($pdbId) {
                $rfpath = Utils::getRasterizedFilePath($fpath);
                if ($rfpath) {
                    list($width, $height) = getimagesize($rfpath);
                    $mime = $magic->guessMimeType($fpath, false);
                    list($majorMime, $minorMime) = File::splitMime($mime);
                    $mediaType = $magic->getMediaType(null, $mime);
                    $this->dbw->update('image', [
                        'img_width' => $width,
                        'img_height' => $height,
                        'img_bits' => 0,
                        'img_media_type' => $mediaType,
                        'img_major_mime' => $majorMime,
                        'img_minor_mime' => $minorMime,
                    ], ['img_name' => $row->img_name], __METHOD__);
                    $file->purgeCache();
                    $file->purgeThumbnails();
                    $success = true;
                }
            }
            $this->output(sprintf("%4d. %s - %s\n", $n++, $fpath, $success ? 'OK' : 'ERROR'));
            foreach ($file->getHistory() as $ofile) {
                $osuccess = false;
                $ofpath = $ofile->getLocalRefPath();
                $opdbId = Utils::getPdbId($ofpath);
                if ($opdbId) {
                    $orfpath = Utils::getRasterizedFilePath($ofpath);
                    if ($orfpath) {
                        list($owidth, $oheight) = getimagesize($orfpath);
                        $omime = $magic->guessMimeType($ofpath, false);
                        list($omajorMime, $ominorMime) = File::splitMime($omime);
                        $omediaType = $magic->getMediaType(null, $omime);
                        $this->dbw->update('oldimage', [
                            'oi_width' => $owidth,
                            'oi_height' => $oheight,
                            'oi_bits' => 0,
                            'oi_media_type' => $omediaType,
                            'oi_major_mime' => $omajorMime,
                            'oi_minor_mime' => $ominorMime,
                        ], [
                            'oi_name' => $ofile->getName(),
                            'oi_archive_name' => $ofile->getArchiveName(),
                        ], __METHOD__);
                        $ofile->purgeCache();
                        $ofile->purgeThumbnails();
                        $osuccess = true;
                    }
                }
                $this->output(sprintf("      + %s - %s\n", $ofpath, $osuccess ? 'OK' : 'ERROR'));
            }
        }
    }
}

$maintClass = 'PDBHandlerRebuildData';
require_once RUN_MAINTENANCE_IF_MAIN;
