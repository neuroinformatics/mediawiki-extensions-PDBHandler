# MediaWiki Extension PDFHandler

This extension generates 2D/3D previews and thumbnailes for uploaded the [PDB: Protain Data Bank](https://www.wwpdb.org) files.

## Install

### Pre-requisites

This extension requires the following tool to be installed first:

- [PyMOL](https://pymol.org/)
- [ImageMagick](https://imagemagick.org/)

### Setup

To install this extension, add the following to LocalSettings.php.

```PHP
// enable image uploads and setup ImageMagik, if you haven't already configured yet.
$wgEnableUploads = true;
$wgUseImageMagick = true;
$wgImageMagickConvertCommand = "/usr/bin/convert";

// load extention
wfLoadExtension("PDBHandler");
```

### Optional settings

- `$wgPyMOLCommand`
  - `pymol` command path.
  - default: `"/usr/bin/pymol"`
- `$wgPDBHandlerUseWebGL`
  - enables to use the WebGL 3D viewer based on the [GLmol](https://webglmol.osdn.jp/).
  - default: `true`
- `$wgPDBHandlerCacheDirectory`
  - cache directory to store rasterized PDB image files by the `pymol` command.
  - default: `"${wgUploadDirectory}/pdbhandler"`

### Syntax to embed a PDB file

The PDB files can be included on a page by using image embedding syntax, e.g.:

```MediaWiki
[[File:2A07.pdb]]
```

## License

This software is licensed under the [GNU General Public License 2.0 or later](COPYING).

## Authors

- [Yoshihiro Okumura](https://github.com/orrisroot)
- [Emw](https://www.mediawiki.org/wiki/User:Emw) (Original Author)

## Usage examples

- https://bsd.neuroinf.jp/ : Brain Science Dictionary project in Japanese.
