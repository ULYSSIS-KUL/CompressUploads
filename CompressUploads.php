<?php

use MediaWiki\MediaWikiServices;

class CompressUploads {
    public static function onUploadVerifyFile($upload) {
        // Stashed files are already processed.
        if ($upload instanceof UploadFromStash) {
            return;
        }

        global $wgCUCompressPdf;
        $wgCUCompressPdf ??= true;
        global $wgCUConvertImages;
        $wgCUConvertImages ??= array(
            "image/bmp",
            "image/x-bmp",
            "image/x-ms-bmp",
            "image/tiff",
            "image/tiff-fx");
        global $wgCUPngCompression;
        $wgCUPngQuality ??= 9;
        global $wgCUPngFilter;
        $wgCUPngFilter ??= 6;
        global $wgCUJpgQuality;
        $wgCUJpgQuality ??= 80;
        global $wgCUMaxWidth;
        $wgCUMaxWidth ??= 2000;
        global $wgCUMaxHeight;
        $wgCUMaxHeight ??= 2000;
        global $wgCUStripExif;
        $wgCUStripExif ??= true;

        $rp = new ReflectionProperty("UploadBase", "mDesiredDestName");
        $rp->setAccessible(true);
        $name = $rp->getValue($upload);
        $rp = new ReflectionProperty("UploadBase", "mFinalExtension");
        $rp->setAccessible(true);
        $extension = $rp->getValue($upload);
        $tempPath = $upload->getTempPath();

        $isPdf = false;
        $convert = false;
        $isPng = false;
        $isJpg = false;
        $mime = mime_content_type($tempPath);
        if ($mime === "application/pdf") {
            $isPdf = true;
        // If someone configures jpg files to be converted to png files,
        // we don't want to mark them as $isJpg.
        } else if (in_array($mime, $wgCUConvertImages)) {
            $convert = true;
        } else if ($mime === "image/png") {
            $isPng = true;
        } else if ($mime === "image/jpeg") {
            $isJpg = true;
        }

        if ($isPdf && $wgCUCompressPdf) {
            $outPath = tempnam("/tmp", "cut");
            exec("qpdf --object-streams=generate " . escapeshellarg($tempPath) . " " . escapeshellarg($outPath));
            // Only finalize if our output is smaller.
            if (filesize($outPath) < filesize($tempPath)) {
                copy($outPath, $tempPath);
                unlink($outPath);
            }
        }

        if ($convert || $isPng || $isJpg) {
            $image = new Imagick($tempPath);
            if ($convert) {
                $image->setImageFormat("png");
                $isPng = true;
                $i = strrpos($name, ".");
                $name = substr($name, 0, $i) . ".png";
                $extension = "png";
            }

            if ($isPng) {
                $image->setImageCompression(Imagick::COMPRESSION_ZIP);
                $image->setImageCompressionQuality($wgCUPngCompression * 10 + $wgCUPngFilter);
            }

            if ($isJpg) {
                $image->setImageCompression(Imagick::COMPRESSION_JPEG);
                $image->setImageCompressionQuality($wgCUJpgQuality);
            }

            $width = $image->getImageWidth();
            $height = $image->getImageHeight();
            if (isset($wgCUMaxWidth) && $width > $wgCUMaxWidth || isset($wgCUMaxHeight) && $height > $wgCUMaxHeight) {
                $image->scaleImage($wgCUMaxWidth, $wgCUMaxHeight, true);
            }

            if ($wgCUStripExif) {
                $icc = $image->getImageProfiles("icc", true);
                self::autoRotate($image);
                $image->stripImage();
                if (!empty($icc)) {
                    $image->profileImage("icc", $icc["icc"]);
                }
            }

            unlink($tempPath);
            $image->writeImage($tempPath);
        }

        $upload->initializePathInfo($name, $tempPath, filesize($tempPath));
        $rp = new ReflectionProperty("UploadBase", "mFinalExtension");
        $rp->setAccessible(true);
        $rp->setValue($upload, $extension);
        $rp = new ReflectionProperty("UploadBase", "mTitle");
        $rp->setAccessible(true);
        $rp->setValue($upload, false);
        $rp = new ReflectionProperty("UploadBase", "mFileProps");
        $rp->setAccessible(true);
        $mwProps = new MWFileProps(MediaWikiServices::getInstance()->getMimeAnalyzer());
        $rp->setValue($upload, $mwProps->getPropsFromPath($tempPath, $extension));
    }

    public static function autoRotate($image) {
        $orientation = $image->getImageOrientation();
        switch ($orientation) {
            case Imagick::ORIENTATION_TOPLEFT:
                break;
            case Imagick::ORIENTATION_TOPRIGHT:
                $image->flopImage();
                break;
            case Imagick::ORIENTATION_BOTTOMRIGHT:
                $image->rotateImage("#000", 180);
                break;
            case Imagick::ORIENTATION_BOTTOMLEFT:
                $image->flopImage();
                $image->rotateImage("#000", 180);
                break;
            case Imagick::ORIENTATION_LEFTTOP:
                $image->flopImage();
                $image->rotateImage("#000", -90);
                break;
            case Imagick::ORIENTATION_RIGHTTOP:
                $image->rotateImage("#000", 90);
                break;
            case Imagick::ORIENTATION_RIGHTBOTTOM:
                $image->flopImage();
                $image->rotateImage("#000", 90);
                break;
            case Imagick::ORIENTATION_LEFTBOTTOM:
                $image->rotateImage("#000", -90);
                break;
            default:
                break;
        }

        $image->setImageOrientation(Imagick::ORIENTATION_TOPLEFT);
    }
}
