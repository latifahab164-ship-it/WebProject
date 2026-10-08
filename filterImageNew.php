<?php
// get the uploaded image and the chosen filter
$file = $_FILES['image']['tmp_name'];
$filter = $_POST['filter'];

// find the image type
$info = getimagesize($file);
$type = $info[2];

// create the image using GD
if ($type == IMAGETYPE_PNG) {
    $img = imagecreatefrompng($file);
} else if ($type == IMAGETYPE_GIF) {
    $img = imagecreatefromgif($file);
} else {
    $img = imagecreatefromjpeg($file);
}

// apply the filter
if ($filter == "edge") {
    imagefilter($img, IMG_FILTER_EDGEDETECT);
} else if ($filter == "grayscale") {
    imagefilter($img, IMG_FILTER_GRAYSCALE);
} else if ($filter == "reverse") {
    imagefilter($img, IMG_FILTER_NEGATE);
} else if ($filter == "pixelation") {
    imagefilter($img, IMG_FILTER_PIXELATE, 20, true);
}

// send the image to the browser
if ($type == IMAGETYPE_PNG) {
    header("Content-Type: image/png");
    imagepng($img);
} else if ($type == IMAGETYPE_GIF) {
    header("Content-Type: image/gif");
    imagegif($img);
} else {
    header("Content-Type: image/jpeg");
    imagejpeg($img);
}

imagedestroy($img);
?>
