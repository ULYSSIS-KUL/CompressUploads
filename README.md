# CompressUploads
This extension uses various techniques to reduce the size of uploaded files. Made for ULYSSIS VZW by Joachim Vandersmissen.

## Features
* Compress PDF files using GhostScript
* Convert inefficient file formats (by default: BMP and TIFF) to PNG files
* Compress PNG files using configurable levels and filters
* Compress JPG files to configurable quality
* Automatically resize images exceeding a maximum width or height (or both)
* Strip all EXIF data (orientation data will be processed, and ICC profiles are kept)

## Installation
* Install [GhostScript](https://ghostscript.com/) for PDF compression.
* Install [ImageMagick](https://imagemagick.org/) and [Imagick](https://pecl.php.net/package/imagick) for image processing.
* Download this repository, and put the `CompressUploads` directory in the `extensions` directory
* Add the following to `LocalSettings.php`:
```
wfLoadExtension( 'CompressUploads' );
```

## Configuration
As usual, configuration options can be added to `LocalSettings.php` using global variables.

| Option | Value | Default Value | Description |
| --- | --- | --- | --- |
| `$wgCUCompressPdf` | `boolean` | `true` | Whether PDF files should be compressed using GhostScript |
| `$wgCUConvertImages` | `String[]` | `["image/bmp", "image/x-bmp", "image/x-ms-bmp", "image/tiff", "image/tiff-fx"]` | The [MIME types](https://developer.mozilla.org/en-US/docs/Web/HTTP/Basics_of_HTTP/MIME_types/Complete_list_of_MIME_types) of all inefficient image file formats that should be converted to PNG |
| `$wgCUPngQuality` | `int` | `96` | The PNG [compression level](https://www.imagemagick.org/script/command-line-options.php#define) (at the position of the tens, by default `9`) and [compression filter](https://www.imagemagick.org/script/command-line-options.php#define) (at the position of the ones, by default `6`) |
| `$wgCUJpgQuality` | `int` | `80` | The JPG compression quality (0-100) |
| `$wgCUMaxWidth` | `int` | _not set_ | The maximum image width, larger images will be scaled accordingly. If not set, image width is unlimited |
| `$wgCUMaxHeight` | `int` | _not set_ | The maximum image height, larger images will be scaled accordingly. If not set, image height is unlimited |
| `$wgCUStripExif` | `boolean` | `true` | Whether [EXIF data](https://en.wikipedia.org/wiki/Exif) should be stripped from uploaded images |

## Tips
* MediaWiki keeps temporary uploaded files in `images/temp/`. You could periodically delete these to free up space.
* MediaWiki keeps deleted uploaded files in `images/deleted/`. You could delete these, provided you are absolutely sure you won't have to 'undelete' them.
* The MediaWiki page `Special:ListFiles` shows every uploaded file on the wiki. These files can be sorted by size to quickly find out which files take up most space.
* If EXIF data is stripped, metadata such as camera brand, aspect ratio, location is also removed. A win for privacy!
