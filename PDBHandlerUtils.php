<?php

/**
 * Utilities for the PDBHandler
 */
class PDBHandlerUtils {

	/**
	 * get rasterized file path
	 *
	 * @param $file File
	 * @return bool|string
	 */
	public static function getRasterizedFilePath($srcPath) {
		global $wgUploadDirectory, $wgPDBHandlerCacheDirectory;
		$relPath = str_replace($wgUploadDirectory, '', $srcPath);
		$dstPath = $wgPDBHandlerCacheDirectory . $relPath . '/' . basename($srcPath) . '.png';
		$dstDir = dirname($dstPath);
		if (!is_dir($dstDir)) {
			$dpath = $wgPDBHandlerCacheDirectory;
			$rpathes = explode('/', $relPath);
			foreach ($rpathes as $rpath) {
				if ($rpath == '')
					continue;
				$dpath .= '/' . $rpath;
				if (!is_dir($dpath)) {
					if (@mkdir($dpath) === false) {
						error_log(__CLASS__ . '::' . __METHOD__ . ': failed to create cache directory : ' . $dpath);
						return false;
					}
				}
			}
		}
		$doCreate = !file_exists($dstPath);
		if (!$doCreate && filemtime($srcPath) > filemtime($dstPath)) {
			@unlink($dstPath);
			$doCreate = true;
		}
		if ($doCreate) {
			if (!self::convertToPNG($srcPath, $dstPath)) {
				error_log(__CLASS__ . '::' . __METHOD__ . ': failed to rasterize PDB file : ' . $dstPath . ', src:' . $srcPath);
				return false;
			}
		}
		return $dstPath;
	}

	/**
	 * resize png image file
	 *
	 * @param $input string
	 * @param $output string
	 * @param $width int
	 * @param $height int
	 * @return bool
	 */
	public static function resizePNG($input, $output, $width, $height) {
		global $wgImageMagickTempDir, $wgImageMagickConvertCommand;

		$env = array('OMP_NUM_THREADS' => 1);
		if (strval($wgImageMagickTempDir) !== '') {
			$env['MAGICK_TMPDIR'] = $wgImageMagickTempDir;
		}
		$cmd =
			wfEscapeShellArg($wgImageMagickConvertCommand).
			' -quality 95'.
			' -background white'.
			' '.wfEscapeShellArg($input).
			' -thumbnail '.wfEscapeShellArg($width.'x'.$height.'!').
			' -depth 8'.
			' '.wfEscapeShellArg($output).
			' 2>&1';
		$retval = 0;
		$err = wfShellExec($cmd, $retval, $env);
		if ($retval !== 0)
			error_log('failed to resize png: ' . $cmd);
		return ($retval === 0);
	}

	/**
	 * convert pdb file to png image
	 *
	 * @param string $input input .pdb file
	 * @param string $output output .png file
	 * @return bool false if failure
	 */
	public static function convertToPNG($input, $output) {
		global $wgImageMagickTempDir, $wgImageMagickConvertCommand,
			$wgPyMOLCommand;
		// try to check input file
		if (!file_exists($input))
			return false;
		// try to remove existing output file
		if (file_exists($output) && !@unlink($output))
			return false;
		clearstatcache();

		// load PDB Id from HEADER
		$fp = fopen($input, 'rb');
		if ($fp === false)
			return false;
		$header = fread($fp, 80);
		fclose($fp);
		if (substr($header, 0, 6) != 'HEADER')
			return false;
		$pdb_id = trim(substr($header, 62, 4));

		$width = 1000;

		$spec = array(
			0 => array('pipe', 'r'),
			1 => array('pipe', 'w'),
			2 => array('pipe', 'w')
		);
		$pymol = escapeshellcmd($wgPyMOLCommand).' -cp';
		$proc = proc_open($pymol, $spec, $pipes);
		if (!is_resource($proc))
			return false;
		$commands = <<<EOD
load $input, $pdb_id
hide everything, all
show cartoon, all
set_color cytosine, [255,154,154]
set_color guanine, [161,161,255]
set_color thymine, [233,149,255]
set_color adenine, [120,255,120]
util.chainbow !resn da+dt+dg+dc+du+hetatm
color cytosine, resn dc
color guanine, resn dg
color thymine, resn dt
color adenine, resn da
color tv_red, name O5'
color raspberry, name O3'
color orange, name p
orient $pdb_id
set opaque_background, 0
set show_alpha_checker, 1
set cartoon_transparency, 0
set depth_cue, 0
set ray_trace_fog, 0
set max_threads, 2
ray $width
png $output, $width
EOD;
		fwrite($pipes[0], $commands);
		fclose($pipes[0]);
		fclose($pipes[1]);
		fclose($pipes[2]);
		$ret = proc_close($proc);
		// echo $commands;
		if ($ret != 0 || !file_exists($output))
			return false;

		// trim image
		$env = array('OMP_NUM_THREADS' => 1);
		if (strval($wgImageMagickTempDir) !== '')
			$env['MAGICK_TMPDIR'] = $wgImageMagickTempDir;

		$cmd = wfEscapeShellArg($wgImageMagickConvertCommand).
			' '.wfEscapeShellArg($output).
			' -trim '.wfEscapeShellArg($output).
			' 2>&1';
		$retval = 0;
		$err = wfShellExec($cmd, $retval, $env);
		if ($retval !== 0)
			return false;
		return true;
	}
}
