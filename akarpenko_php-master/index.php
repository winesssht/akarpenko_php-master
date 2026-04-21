<?php 

function getBMI($weight, $height) { // waga i wzrost w cm 
    $h = $height / 100;
    return round($weight / ($h * $h), 1);
}

function getBMILabel($bmi) { // wartość BMI
    if ($bmi < 18.5) {
        return 'niedowaga';
    } elseif ($bmi < 25) {
        return 'waga prawidłowa';
    } elseif ($bmi < 30) {
        return 'nadwaga';
    } else {
        return 'otyłość';
    }
}

function getBMR($w, $h, $a, $g) { // waga, wzrost, wiek, płeć
    $base = (10 * $w) + (6.25 * $h) - (5 * $a);
    if ($g === 'm') {
        return round($base + 5);
    } else {
        return round($base - 161);
    }
}

function getTDEE($bmr, $activity){ // poziom aktywności
  if ($activity == "siedzący tryb życia");
  if ($activity == "lekka aktywność");
  if ($activity == "umiarkowana aktywność");
  if ($activity == "duża aktywność");
  if ($activity == "bardzo duża aktywność");
}

function getTargetCalories($tdee, $goal) { // cel diety
    if ($goal == "utrata wagi") return $tdee - 500;
    if ($goal == "przyrost wagi") return $tdee + 300;
    return $tdee;
}