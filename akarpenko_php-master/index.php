<?php 

function getBMI($weight, $height) { // waga i wzrost w cm 
    $h = $height / 100;
    return round($weight / ($h * $h), 1);
}