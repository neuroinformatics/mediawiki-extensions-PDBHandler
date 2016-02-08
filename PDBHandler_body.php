<?php

/**
 * Handler for the PDB file format
 *
 * @ingroup Media
 */
class PDBHandler extends ImageHandler {

	/**
	 * @return bool
	 */
	function isEnabled() {
		global $wgImageMagickConvertCommand;
		if (!isset($wgImageMagickConvertCommand) || $wgImageMagickConvertCommand == '')
			return false;
		return true;
	}

	/**
	 * @param $file File
	 * @return bool
	 */
	function canRender($file) {
		return true;
	}

	/**
	 * @param $file
	 * @return bool
	 */
	function mustRender($file) {
		return true;
	}

	/**
	 * @param $name
	 * @param $value
	 * @return bool
	 */
	function validateParam($name, $value) {
		if (in_array($name, array('width')))
			return ($value > 0);
		return false;
	}

	/**
	 * @param $params array
	 * @return bool|string
	 */
	function makeParamString($params) {
		if (!isset($params['width']))
			return false;
		return $params['width'].'px';
	 }

	/**
	 * @param $str string
	 * @return array|bool
	 */
	function parseParamString($str) {
		if (preg_match('/^(\d+)px$/', $str, $matches))
			return array('width' => $matches[1]);
		return false;
	}

	/**
	 * @param $file File
	 * @param  $dstPath
	 * @param  $dstUrl
	 * @param  $params
	 * @param int $flags
	 * @return MediaTransformError|ThumbnailImage|TransformParameterError
	 */
	function doTransform($file, $dstPath, $dstUrl, $params, $flags = 0) {
		if (!$this->normaliseParams($file, $params)) {
			// error_log(sprintf('normalizeParams error: %s, $params=%s', $file->getLocalRefPath(),var_export($params,true)));
			return new TransformParameterError($params);
		}
		if ($flags & self::TRANSFORM_LATER) {
			$width = $params['width'];
			$height = $params['height'];
 			return new PDBHandlerTransformOutput($file, $dstUrl, $width, $height, false, 1);
		}

		$path = $file->getLocalRefPath();
		$srcPath = PDBHandlerUtils::getRasterizedFilePath($path);
		if ($srcPath === false)
			return new MediaTransformError('thumbnail_error', $width, $height, 'failed to get rasterized image');
		$width = $params['width'];
		$height = $params['height'];

		$ret = PDBHandlerUtils::resizePNG($srcPath, $dstPath, $width, $height);
		if ($this->removeBadFile($dstPath, ($ret ? 0 : 1))) {
			error_log(sprintf('thumbnail_error: failed to resize image of %s, width:%s, height:%s', $srcPath, $width, $height));
			return new MediaTransformError('thumbnail_error', $width, $height, 'failed to resize image');
		}
		return new PDBHandlerTransformOutput($file, $dstUrl, $width, $height, $dstPath, 1);
	}

	/**
	 * Render files as PNG
	 *
	 * @param $ext
	 * @param $mime
	 * @param $params
	 * @return array
	 */
	function getThumbType($ext, $mime, $params = null) {
		return array('png', 'image/png');
	}

       /**
         * @param File $file
         * @param string $path Unused
         * @param bool|array $metadata
         * @return array
         */
	function getImageSize($file, $path, $metadata = false) {
		$path = PDBHandlerUtils::getRasterizedFilePath($path);
		if ($path == false)
			return array(0, 0, 'PDB', 'width="0" height="0"');
		list($width, $height) = getimagesize($path);
		return array($width, $height, 'PDB', sprintf('width="%d" height="%d"', $width, $height));
        }

}

