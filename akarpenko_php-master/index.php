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

function getTDEE($bmr, $activity) {
    if ($activity === "sedentary")   return round($bmr * 1.2);
    if ($activity === "light")       return round($bmr * 1.375);
    if ($activity === "moderate")    return round($bmr * 1.55);
    if ($activity === "active")      return round($bmr * 1.725);
    if ($activity === "very_active") return round($bmr * 1.9);
    // default
    return round($bmr * 1.2);
}

function getTargetCalories($tdee, $goal) { // cel diety
    if ($goal == "utrata wagi") return $tdee - 500;
    if ($goal == "przyrost wagi") return $tdee + 300;
    return $tdee;
}

function getProtein($w, $goal) { // waga i cel diety
    if ($goal == "utrata wagi") return round($w * 2.2);
    if ($goal == "przyrost wagi") return round($w * 2.0);
    return round($w * 1.6);
}

function getFat($calories){// kalorie
    return round($calories * 0.25 / 9);
}

function getCarbs($calories, $protein, $fat){ // kalorie, białko i tłuszcze
  $remaining = $calories - ($protein * 4) - ($fat * 9);
  if ($remaining < 0) return 0;
  return round($remaining / 4);
}