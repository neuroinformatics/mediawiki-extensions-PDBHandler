<?php

namespace MediaWiki\Extension\PDBHandler;

class Hooks
{
    /**
     * Hook: MimeMagicInit.
     *
     * @param \MimeAnalyzer $mimeMagic
     * @param object        $addToList
     */
    public static function onMimeMagicInit($mimeMagic)
    {
        static $extraTypes = 'chemical/x-pdb    pdb';
        static $extraInfo = 'chemical/x-pdb [DRAWING]';
        $mimeMagic->addExtraTypes($extraTypes);
        $mimeMagic->addExtraInfo($extraInfo);
    }

    /**
     * Hook: MimeMagicImproveFromExtension.
     *
     * @param \MimeAnalyzer $mimeMagic
     * @param string        $ext       file extension
     * @param string        $mime      [in]: previously detected mime, [out]: improved MIME
     */
    public static function onMimeMagicImproveFromExtension($mimeMagic, $ext, &$mime)
    {
        static $extraMimes = ['model/x-pdb', 'text/plain'];
        static $extraExts = ['pdb'];
        if (in_array($mime, $extraMimes) && in_array($ext, $extraExts)) {
            $mime = 'chemical/x-pdb';
        }
    }

    /**
     * Hook: MimeMagicGuessFromContent.
     *
     * @param \MimeAnalyzer $mimeMagic
     * @param string        $head
     * @param string        $tail
     * @param string        $file
     * @param string        $mime
     */
    public static function onMimeMagicGuessFromContent($mimeMagic, &$head, &$tail, $file, &$mime)
    {
        static $sections = [
            'HEADER', 'OBSLTE', 'TITLE', 'SPLT', 'CAVEAT', 'COMPND', 'SOURCE', 'KEYWDS', 'EXPDTA', 'NUMMDL',
            'MDLTYP', 'AUTHOR', 'REVDAT', 'SPRSDE', 'JRNL', 'REMARK', 'DBREF', 'DBREF1', 'DBREF2', 'SEQADV',
            'SEQRES', 'MODRES', 'HET', 'FORMUL', 'HETNAM', 'HETSYN', 'HELIX', 'SHEET', 'SSBOND', 'LINK',
            'CISPEP', 'SITE', 'SCALE1', 'SCALE2', 'SCALE3', 'CRYST1', 'MTRIX1', 'MTRIX2', 'MTRIX3', 'ORIGX1',
            'ORIGX2', 'ORIGX3', 'MODEL', 'ATOM', 'ANISOU', 'TER', 'HETATM', 'ENDMDL', 'CONECT', 'MASTER', 'END',
        ];
        $hlen = strlen($head);
        $hdata = substr($head, 0, min($hlen, 1024));
        $tlen = strlen($tail);
        $tdata = substr($tail, max($tlen - 1024, 0), min(1024, $tlen));
        if ($hlen > 6 && 0 == strncmp($hdata, 'HEADER', 6)) {
            $hdata = preg_replace("/\r\n|\r|\n/", "\n", $hdata);
            $tdata = preg_replace("/\r\n|\r|\n/", "\n", $tdata);
            $hdata = explode("\n", $hdata);
            $tdata = explode("\n", $tdata);
            array_pop($hdata); // skip tail line
            array_shift($tdata); // skip first line
            $ok = true;
            foreach ($hdata as $line) {
                $mark = strlen($line) > 6 ? trim(substr($line, 0, 6)) : '';
                if (!in_array($mark, $sections)) {
                    $ok = false;
                    break;
                }
            }
            if ($ok) {
                $mime = 'chemical/x-pdb';
            }
        }
    }
}
