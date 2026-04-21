<?php

// --- FUNKCJE ---

function getBMI($weight, $height) {
    $h = $height / 100;
    return round($weight / ($h * $h), 1);
}

function getBMILabel($bmi) {
    if ($bmi < 18.5) return 'niedowaga';
    if ($bmi < 25)   return 'waga prawidłowa';
    if ($bmi < 30)   return 'nadwaga';
    return 'otyłość';
}

function getBMR($w, $h, $a, $g) {
    $base = (10 * $w) + (6.25 * $h) - (5 * $a);

    if ($g === 'male') return round($base + 5);
    return round($base - 161);
}

function getTDEE($bmr, $activity) {
    if ($activity === "sedentary")   return round($bmr * 1.2);
    if ($activity === "light")       return round($bmr * 1.375);
    if ($activity === "moderate")    return round($bmr * 1.55);
    if ($activity === "active")      return round($bmr * 1.725);
    if ($activity === "very_active") return round($bmr * 1.9);
    return round($bmr * 1.2);
}

function getTargetCalories($tdee, $goal) {
    if ($goal === "lose")  return $tdee - 500;
    if ($goal === "build") return $tdee + 300;
    return $tdee;
}

function getProtein($w, $goal) {
    if ($goal === "lose")  return round($w * 2.2);
    if ($goal === "build") return round($w * 2.0);
    return round($w * 1.6);
}

function getFat($calories) {
    return round(($calories * 0.25) / 9);
}

function getCarbs($calories, $protein, $fat) {
    $remaining = $calories - ($protein * 4) - ($fat * 9);
    if ($remaining < 0) return 0;
    return round($remaining / 4);
}


// --- OBRÓBKA FORMULARZA ---

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $gender   = $_POST["gender"] ?? "";
    $age      = (int) $_POST["age"];
    $weight   = (float) $_POST["weight"];
    $height   = (float) $_POST["height"];
    $activity = $_POST["activity"] ?? "sedentary";
    $goal     = $_POST["goal"] ?? "maintain";

    $bmi      = getBMI($weight, $height);
    $bmiLabel = getBMILabel($bmi);
    $bmr      = getBMR($weight, $height, $age, $gender);
    $tdee     = getTDEE($bmr, $activity);
    $target   = getTargetCalories($tdee, $goal);
    $protein  = getProtein($weight, $goal);
    $fat      = getFat($target);
    $carbs    = getCarbs($target, $protein, $fat);

    echo '<div class="result-box">';
    echo "<h2>Wyniki</h2>";
    echo "<div class='result-line'>BMI: $bmi ($bmiLabel)</div>";
    echo "<div class='result-line'>BMR: $bmr</div>";
    echo "<div class='result-line'>TDEE: $tdee</div>";
    echo "<div class='result-line'>Kalorie docelowe: $target</div>";
    echo "<div class='result-line'>Białko: $protein g</div>";
    echo "<div class='result-line'>Tłuszcze: $fat g</div>";
    echo "<div class='result-line'>Węglowodany: $carbs g</div>";
    echo '</div>';


}
?>
