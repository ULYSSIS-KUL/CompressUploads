# CompressUploads
This extension uses various techniques to reduce the size of uploaded files. Made for ULYSSIS VZW by Joachim Vandersmissen.

## Features
* Compress PDF files using qpdf.
* Convert inefficient file formats (by default: BMP and TIFF) to PNG files.
* Compress PNG files using configurable levels and filters.
* Compress JPG files to configurable quality.
* Automatically resize images exceeding a maximum width or height (or both).
* Strip all EXIF data (orientation data will be processed, and ICC profiles are kept).

## Installation
* Install [qpdf](http://qpdf.sourceforge.net/) for PDF compression.
* Install [ImageMagick](https://imagemagick.org/) and [Imagick](https://pecl.php.net/package/imagick) for image processing.
* Download [the latest release](https://github.com/ULYSSIS-KUL/CompressUploads/releases/latest/CompressUploads.zip), and put the `CompressUploads` folder in the `extensions` directory.
* Add the following to `LocalSettings.php`:
```
wfLoadExtension( 'CompressUploads' );
```

## Configuration
As usual, configuration options can be added to `LocalSettings.php` using global variables.

| Option | Value | Default Value | Description |
| --- | --- | --- | --- |
| `$wgCUCompressPdf` | `boolean` | `true` | Whether PDF files should be compressed using qpdf. This technique uses [PDF Object Streams](https://en.wikipedia.org/wiki/PDF#File_structure), which means the compression will have the most effect on PDF files containing a lot of indirect objects. |
| `$wgCUConvertImages` | `String[]` | `["image/bmp", "image/x-bmp", "image/x-ms-bmp", "image/tiff", "image/tiff-fx"]` | The [MIME types](https://developer.mozilla.org/en-US/docs/Web/HTTP/Basics_of_HTTP/MIME_types/Complete_list_of_MIME_types) of all inefficient image file formats that should be converted to PNG. |
| `$wgCUPngCompression` | `int` | `9` | The PNG [compression level](https://www.imagemagick.org/script/command-line-options.php#define) (0-9). This compression is [lossless](https://en.wikipedia.org/wiki/Lossless_compression) and should not cause any artifacts. |
| `$wgCUPngFilter` | `int` | `6` | The PNG  [compression filter](https://www.imagemagick.org/script/command-line-options.php#define). For advanced users. |
| `$wgCUJpgQuality` | `int` | `80` | The JPG compression quality (0-100). This compression is [lossy](https://en.wikipedia.org/wiki/Lossy_compression): lower quality levels will cause compression artifacts. |
| `$wgCUMaxWidth` | `int` | `2000` | The maximum image width in pixels, larger images will be scaled accordingly. If not set, image width is unlimited. |
| `$wgCUMaxHeight` | `int` | `2000` | The maximum image height in pixels, larger images will be scaled accordingly. If not set, image height is unlimited. |
| `$wgCUStripExif` | `boolean` | `true` | Whether [EXIF data](https://en.wikipedia.org/wiki/Exif) should be stripped from uploaded images. |

## Tips
* MediaWiki keeps temporary uploaded files in `images/temp/`. You could periodically delete these to free up space.
* MediaWiki keeps deleted uploaded files in `images/deleted/`. You could delete these, provided you are absolutely sure you won't have to 'undelete' them.
* The MediaWiki page `Special:ListFiles` shows every uploaded file on the wiki. These files can be sorted by size to quickly find out which files take up most space.
* If EXIF data is stripped, metadata such as camera brand, aspect ratio, location is also removed. A win for privacy!
