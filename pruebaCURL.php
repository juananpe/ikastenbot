<?php
/**
 * Created by PhpStorm.
 * User: amaia
 * Date: 23/04/18
 * Time: 1:46
 */
$google_apiKey ='AIzaSyAR1kfTI-hjT5bRZoXSRgoZHRzjKZmUeS4';
$imagePath ='image-006.ppm';
$image = file_get_contents($imagePath);
$image64 = base64_encode($image);
//echo $image64;


$postData = array(
    'requests' => array(
        'image'=>array(
            'content'=> "$image64"
        ),
        'features'=>array(
            "type"=> "WEB_DETECTION",
            "maxResults" => 4,
        )
    ),
);
//echo json_encode($postData);

$ch = curl_init('https://vision.googleapis.com/v1/images:annotate?key='.$google_apiKey);
curl_setopt_array($ch, array(
    CURLOPT_POST => TRUE,
    CURLOPT_RETURNTRANSFER => TRUE,
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json'
    ),
//    CURLOPT_POSTFIELDS => json_encode(json_decode($data,true))
    CURLOPT_POSTFIELDS => json_encode($postData)
));

// Send the request
$response = curl_exec($ch);

echo $response;

