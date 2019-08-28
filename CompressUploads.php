<?php

class CompressUploads {
    public static function onUploadFormBeforeProcessing($upload) {
        global $wgCUCompressPdf;
        if (!isset($wgCUCompressPdf)) $wgCUCompressPdf = true;
        global $wgCUConvertImages;
        if (!isset($wgCUConvertImages)) $wgCUConvertImages = array(
            "image/bmp",
            "image/x-bmp",
            "image/x-ms-bmp",
            "image/tiff",
            "image/tiff-fx");
        global $wgCUPngCompression;
        if (!isset($wgCUPngQuality)) $wgCUPngQuality = 9;
        global $wgCUPngFilter;
        if (!isset($wgCUPngFilter)) $wgCUPngFilter = 6;
        global $wgCUJpgQuality;
        if (!isset($wgCUJpgQuality)) $wgCUJpgQuality = 80;
        global $wgCUMaxWidth;
        global $wgCUMaxHeight;
        global $wgCUStripExif;
        if (!isset($wgCUStripExif)) $wgCUStripExif = true;

        $mUpload = $upload->mUpload;
        $tempPath = $mUpload->getTempPath();

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
            // If the output is somehow larger, we don't want to continue.
            if (filesize($tempPath) <= filesize($outPath)) {
                return true;
            }

            copy($outPath, $tempPath);
            unlink($outPath);
        }

        if ($convert || $isPng || $isJpg) {
            $image = new Imagick($tempPath);
            if ($convert) {
                $image->setImageFormat("png");
                $isPng = true;

                // We have to add .png to the file name, otherwise mediawiki gets ornery.
                $tempPath = $tempPath . ".png";
                $upload->mDesiredDestName = $upload->mDesiredDestName . ".png";
                $mUpload->initializePathInfo($upload->mDesiredDestName, $tempPath, $mUpload->getFileSize());
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
                CompressUploads::autoRotate($image);
                $image->stripImage();
                if (!empty($icc)) {
                    $image->profileImage("icc", $icc["icc"]);
                }
            }

            $image->writeImage($tempPath);
        }

        return true;
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
