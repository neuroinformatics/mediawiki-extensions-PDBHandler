<?php

namespace MediaWiki\Extension\PDBHandler;

class Utils
{
    /**
     * get rasterized file path.
     *
     * @param string $input
     *
     * @return bool|string
     */
    public static function getRasterizedFilePath($input)
    {
        global $wgUploadDirectory, $wgPDBHandlerCacheDirectory;
        $cachedir = '' !== $wgPDBHandlerCacheDirectory ? $wgPDBHandlerCacheDirectory : $wgUploadDirectory.'/pdbhandler';
        $fileHash = hash_file('sha256', $input);
        $pdbId = self::getPdbId($input);
        if (!$pdbId) {
            wfDebugLog('pdbhandler', sprintf('PDBHandler Error on %s: invalid PDB file found "%s"', wfHostname(), $input));

            return false;
        }
        $workDir = $cachedir.'/'.substr($fileHash, 0, 1).'/'.substr($fileHash, 0, 2).'/'.$fileHash;
        if (!is_dir($workDir)) {
            if (false === @mkdir($workDir, 0777, true)) {
                wfDebugLog('pdbhandler', sprintf('PDBHandler Error on %s: faield to create cache directory "%s"', wfHostname(), $workDir));

                return false;
            }
        }
        $srcPath = $workDir.'/'.$pdbId.'.pdb';
        if (!file_exists($srcPath)) {
            if (false === @copy($input, $srcPath)) {
                wfDebugLog('pdbhandler', sprintf('PDBHandler Error on %s: faield to copy PDB file "%s" to cache directory "%s"', wfHostname(), $input, $srcPath));

                return false;
            }
        }
        $dstPath = $workDir.'/'.$pdbId.'.png';
        if (!file_exists($dstPath)) {
            if (!self::convertToPNG($pdbId, $srcPath, $dstPath)) {
                wfDebugLog('pdbhandler', sprintf('PDBHandler Error on %s: faield to convert PDB to PNG "%s"', wfHostname(), $srcPath));

                return false;
            }
        }

        return $dstPath;
    }

    /**
     * resize png image file.
     *
     * @param $input string
     * @param $output string
     * @param $width int
     * @param $height int
     *
     * @return bool
     */
    public static function resizePNG($input, $output, $width, $height)
    {
        global $wgImageMagickTempDir, $wgImageMagickConvertCommand;

        $env = ['OMP_NUM_THREADS' => 1];
        if ('' !== strval($wgImageMagickTempDir)) {
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
        if (0 !== $retval) {
            wfDebugLog('pdbhandler', sprintf('PDBHandler Error on %s: unexpected error code returned: "%s"', wfHostname(), $cmd));
        }

        return 0 === $retval;
    }

    /**
     * convert pdb file to png image.
     *
     * @param string $pdbId  PDB ID
     * @param string $input  input .pdb file
     * @param string $output output .png file
     *
     * @return bool false if failure
     */
    public static function convertToPNG($pdbId, $input, $output)
    {
        global $wgImageMagickTempDir, $wgImageMagickConvertCommand, $wgPyMOLCommand;
        // try to check input file
        if (!file_exists($input)) {
            return false;
        }
        // try to remove existing output file
        if (file_exists($output) && !@unlink($output)) {
            return false;
        }
        clearstatcache();

        $width = 1000;

        $spec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $pymol = escapeshellcmd($wgPyMOLCommand).' -cp';
        $proc = proc_open($pymol, $spec, $pipes);
        if (!is_resource($proc)) {
            return false;
        }
        $poutput = dirname($output).'/pymol-'.basename($output);
        $commands = <<<EOD
load $input, $pdbId
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
orient $pdbId
set opaque_background, 0
set show_alpha_checker, 1
set cartoon_transparency, 0
set depth_cue, 0
set ray_trace_fog, 0
set max_threads, 2
ray $width
png $poutput, $width
EOD;
        fwrite($pipes[0], $commands);
        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $ret = proc_close($proc);
        if (0 != $ret || !file_exists($poutput)) {
            wfDebugLog('pdbhandler', sprintf('PDBHandler Error on %s: unexpected error occured in `pymol` command.', wfHostname(), $cmd));

            return false;
        }
        // trim image
        $env = ['OMP_NUM_THREADS' => 1];
        if ('' !== strval($wgImageMagickTempDir)) {
            $env['MAGICK_TMPDIR'] = $wgImageMagickTempDir;
        }

        $cmd = wfEscapeShellArg($wgImageMagickConvertCommand).
            ' '.wfEscapeShellArg($poutput).
            ' -trim '.wfEscapeShellArg($output).
            ' 2>&1';
        $retval = 0;
        $err = wfShellExec($cmd, $retval, $env);
        if (0 !== $retval) {
            wfDebugLog('pdbhandler', sprintf('PDBHandler Error on %s: unexpected error occured in `convert` command.', wfHostname(), $cmd));

            return false;
        }

        return true;
    }

    /**
     * get PDB ID from pdb file.
     *
     * @param string $input input .pdb file
     *
     * @return string|bool PDB ID, false if failure
     */
    public static function getPdbId($input)
    {
        $fp = fopen($input, 'rb');
        if (false === $fp) {
            wfDebugLog('pdbhandler', sprintf('PDBHandler Error on %s: filed to open file "%s".', wfHostname(), $input));

            return false;
        }
        $header = fread($fp, 80);
        fclose($fp);
        if ('HEADER' != substr($header, 0, 6)) {
            wfDebugLog('pdbhandler', sprintf('PDBHandler Error on %s: filed to parse file header "%s".', wfHostname(), $input));

            return false;
        }
        $pdbId = trim(substr($header, 62, 4));
        if (!preg_match('/^[A-Z0-9]+$/', $pdbId)) {
            wfDebugLog('pdbhandler', sprintf('PDBHandler Error on %s: invalid PDB ID found in file header "%s".', wfHostname(), $input));

            return false;
        }

        return strtoupper($pdbId);
    }
}
