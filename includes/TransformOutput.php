<?php

namespace MediaWiki\Extension\PDBHandler;

class TransformOutput extends \ThumbnailImage
{
    public static $serial = 0;

    public function toHtml($options = array())
    {
        global $wgOut, $wgPDBHandlerUseWebGL;
        if ($wgPDBHandlerUseWebGL && 0 == self::$serial) {
            $wgOut->addModules('ext.PDBHandler');
        }
        ++self::$serial;
        $id = sprintf('glmol_%d', self::$serial);
        $ret = '';
        // parameters
        if ($wgPDBHandlerUseWebGL) {
            $ret .= '<script type="text/javascript">';
            $ret .= 'if (pdbHandlerParams == undefined) var pdbHandlerParams = {};';
            $ret .= sprintf('pdbHandlerParams[\'%s\'] = %s;', $id, \Xml::encodeJsVar($this->file->getUrl()));
            $ret .= '</script>';
        }
        // canvas
        $ret .= sprintf('<div id="%s" style="width:%dpx;height:%dpx;display:inline-block;">%s</div>', $id, $this->width, $this->height, parent::toHTML($options));

        return $ret;
    }
}
