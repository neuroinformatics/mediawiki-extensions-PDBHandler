<?php

namespace MediaWiki\Extension\PDBHandler;

class PDBHandler extends \ImageHandler
{
    /**
     * false if the handler is disabled for all files.
     *
     * @return bool
     */
    public function isEnabled()
    {
        global $wgImageMagickConvertCommand, $wgPyMOLCommand;
        if (!isset($wgImageMagickConvertCommand) || '' === $wgImageMagickConvertCommand || '' === $wgPyMOLCommand) {
            return false;
        }

        return true;
    }

    /**
     * true if the handled types can be transformed.
     *
     * @param \File $file
     *
     * @return bool
     */
    public function canRender($file)
    {
        return true;
    }

    /**
     * true if the handled types cannot be displayed directry in a browser.
     *
     * @param \File $file
     *
     * @return bool
     */
    public function mustRender($file)
    {
        return true;
    }

    /**
     * validate a thumbnail parameter at parse time.
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return bool
     */
    public function validateParam($name, $value)
    {
        if (in_array($name, ['width'])) {
            return $value > 0;
        }

        return false;
    }

    /**
     * marge a parameter array into a string approriate for inclusion in filenames.
     *
     * @param array $params
     *
     * @return bool|string
     */
    public function makeParamString($params)
    {
        if (!isset($params['width'])) {
            return false;
        }

        return $params['width'].'px';
    }

    /**
     * parse a param string makde with makeParamString back into a array.
     *
     * @param string $str
     *
     * @return array|bool
     */
    public function parseParamString($str)
    {
        if (preg_match('/^(\d+)px$/', $str, $matches)) {
            return array('width' => $matches[1]);
        }

        return false;
    }

    /**
     * get a MediaTransformOutput object representing the transformed output.
     *
     * @param \File  $file
     * @param string $dstPath
     * @param string $dstUrl
     * @param array  $params
     * @param int    $flags
     *
     * @return TransformOutput|\MediaTransformError|\TransformParameterError|
     */
    public function doTransform($file, $dstPath, $dstUrl, $params, $flags = 0)
    {
        if (!$this->normaliseParams($file, $params)) {
            return new \TransformParameterError($params);
        }
        $width = $params['width'];
        $height = $params['height'];
        if ($flags & self::TRANSFORM_LATER) {
            return new TransformOutput($file, $dstUrl, $width, $height, false, 1);
        }

        $path = $file->getLocalRefPath();
        if (false === $path) {
            wfDebugLog('thumbnail', sprintf('Thumbnail failed on %s: could not get local copy of "%s"', wfHostname(), $file->getName()));

            return new \MediaTransformError('thumbnail_error', $width, $height, wfMessage('filemissing'));
        }
        $srcPath = Utils::getRasterizedFilePath($path);
        if (false === $srcPath) {
            wfDebugLog('thumbnail', sprintf('Thumbnail failed on %s: could not get local copy of "%s"', wfHostname(), $file->getName()));

            return new \MediaTransformError('thumbnail_error', $width, $height, 'failed to get rasterized image');
        }

        $ret = Utils::resizePNG($srcPath, $dstPath, $width, $height);
        if ($this->removeBadFile($dstPath, ($ret ? 0 : 1))) {
            wfDebugLog('thumbnail', sprintf('Thumbnail failed on %s: could not resize image of "%s" to width:%s, height:%s', wfHostname(), $file->getName(), $width, $height));

            return new \MediaTransformError('thumbnail_error', $width, $height, 'failed to resize image');
        }

        return new TransformOutput($file, $dstUrl, $width, $height, $dstPath, 1);
    }

    /**
     * get the thumbnail extension and mime type for a given source mime type.
     *
     * @param string $ext
     * @param string $mime
     * @param array  $params
     *
     * @return array
     */
    public function getThumbType($ext, $mime, $params = null)
    {
        return ['png', 'image/png'];
    }

    /**
     * get image size.
     *
     * @param \File      $file
     * @param string     $path
     * @param bool|array $metadata
     *
     * @return array
     */
    public function getImageSize($file, $path, $metadata = false)
    {
        $path = Utils::getRasterizedFilePath($path);
        if (false === $path) {
            return [0, 0, 'PDB', 'width="0" height="0"'];
        }
        list($width, $height) = getimagesize($path);

        return [$width, $height, 'PDB', sprintf('width="%d" height="%d"', $width, $height)];
    }
}
