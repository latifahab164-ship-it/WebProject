<?php

$imageFile = $_FILES["image"]["tmp_name"];
$filter = $_POST["filter"];

$image = imagecreatefromstring(file_get_contents($imageFile));

if ($image == false) {
    die("Invalid image");
}

if ($filter == "edge") {

    imagefilter($image, IMG_FILTER_EDGEDETECT);

} elseif ($filter == "grayscale") {

    imagefilter($image, IMG_FILTER_GRAYSCALE);

} elseif ($filter == "reverse") {

    imagefilter($image, IMG_FILTER_NEGATE);

} elseif ($filter == "pixelation") {

    imagefilter($image, IMG_FILTER_PIXELATE, 10, true);

}

imagepng($image, "filtered_image.png");

imagedestroy($image);

?>

<!DOCTYPE html>
<html>

<head>

    <title>Filtered Image</title>

    <style>

        body {
            margin: 0;
            background-color: #f4fafa;
            font-family: Arial, sans-serif;
            text-align: center;
        }

        .back {
            position: absolute;
            top: 25px;
            left: 30px;

            width: 45px;
            height: 45px;

            background-color: white;
            color: #45bdb8;

            border-radius: 50%;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);

            text-decoration: none;
            font-size: 28px;
            font-weight: bold;

            line-height: 45px;
        }

        .back:hover {
            background-color: #45bdb8;
            color: white;
        }

        .container {
            width: 750px;
            margin: 50px auto;
        }

        .logo {
            font-family: "Brush Script MT", "Segoe Script", cursive;
            font-size: 65px;
            color: #263238;
            margin-bottom: 30px;
        }

        .logo span {
            color: #45bdb8;
        }

        .result {
            width: 700px;
            min-height: 500px;

            background-color: white;

            margin: auto;
            padding: 25px;

            border-radius: 20px;

            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        }

        .result img {
            max-width: 650px;
            max-height: 500px;

            border-radius: 12px;
        }

    </style>

</head>

<body>

    <a href="filterForm.html" class="back">←</a>

    <div class="container">

        <h1 class="logo">
            Image <span>Filter</span>
        </h1>

        <div class="result">

            <h2>Filtered Image</h2>

            <img src="filtered_image.png" alt="Filtered Image">

        </div>

    </div>

</body>

</html>
