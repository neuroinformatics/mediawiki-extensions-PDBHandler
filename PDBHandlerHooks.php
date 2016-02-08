<?php

class PDBHandlerHooks {

	/**
	 * Hook: MimeMagicInit
	 *
	 * @param MimeMagic $mimeMagic
	 * @param object $addToList Callback function
	 * @return bool Always true
	 */
	public static function MimeMagicInit($mimeMagic) {
		static $extraTypes = 'model/x-pdb	pdb';
		static $extraInfo = 'model/x-pdb	[MODEL]';
		$mimeMagic->addExtraTypes($extraTypes);
		$mimeMagic->addExtraInfo($extraInfo);
		return true;
	}

	/**
	 * Hook: MimeMagicImproveFromExtension.
	 *
	 * @param MimeMagic $mimeMagic
	 * @param string $ext file extension
	 * @param string $mime [in]: previously detected mime, [out]: improved MIME
	 * @return bool always true
	 */
	public static function MimeMagicImproveFromExtension($mimeMagic, $ext, &$mime) {
		static $extraMimes = array('chemical/x-pdb', 'text/plain');
		static $extraExts = array('pdb');
		if (in_array($mime, $extraMimes) && in_array($ext, $extraExts))
			$mime = 'model/x-pdb';
		return true;
	}

	/**
	 * Hook: MimeMagicGuessFromContent.
	 *
	 * @param MimeMagic $mimeMagic
	 * @param string $head
	 * @param string $tail
	 * @param string $file
	 * @param string $mime
	 * @return bool Always true
	 */
	public static function MimeMagicGuessFromContent($mimeMagic, &$head, &$tail, $file, &$mime) {
		static $sections = array('HEADER', 'OBSLTE', 'TITLE', 'SPLT', 'CAVEAT', 'COMPND', 'SOURCE', 'KEYWDS', 'EXPDTA', 'NUMMDL', 'MDLTYP', 'AUTHOR', 'REVDAT', 'SPRSDE', 'JRNL', 'REMARK', 'DBREF', 'DBREF1', 'DBREF2', 'SEQADV', 'SEQRES', 'MODRES', 'HET', 'FORMUL', 'HETNAM', 'HETSYN', 'HELIX', 'SHEET', 'SSBOND', 'LINK', 'CISPEP', 'SITE', 'SCALE1', 'SCALE2', 'SCALE3', 'CRYST1', 'MTRIX1', 'MTRIX2', 'MTRIX3', 'ORIGX1', 'ORIGX2', 'ORIGX3', 'MODEL', 'ATOM', 'ANISOU', 'TER', 'HETATM', 'ENDMDL', 'CONECT', 'MASTER', 'END');
		$hlen = strlen($head);
		$hdata = substr($head, 0, min($hlen, 1024));
		$tlen = strlen($tail);
		$tdata = substr($tail, max($tlen - 1024, 0), min(1024, $tlen));
		if ($hlen > 6 && strncmp($hdata, 'HEADER', 6) == 0) {
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
			if ($ok)
				$mime = 'model/x-pdb';
		}
		return true;
	}
}
