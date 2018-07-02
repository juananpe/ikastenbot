<?php
/**
 * Created by PhpStorm.
 * User: amaia
 * Date: 7/05/18
 * Time: 11:44
 */


$text = "La casa ez rojo. Y el sielo hazul.";


$data = array ('language'=>'es', 'text' => $text);
$data = http_build_query($data);
$context_options = array (
    'http' => array (
        'method' => 'POST',
        'header'=> "Content-type: application/x-www-form-urlencoded"
            .'Accept: application/json',
        'content' => $data
    )
);

$context = stream_context_create($context_options);
$fp = fopen('https://languagetool.org/api/v2/check', 'r', false, $context);


$contents = stream_get_contents($fp);

$json = json_decode($contents, true);

echo $json_string = json_encode($json, JSON_PRETTY_PRINT);

foreach ($json['matches'] as $error){
   echo $error['message'];
}

