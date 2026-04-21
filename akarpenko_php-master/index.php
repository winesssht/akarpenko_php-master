<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kalkulator BMI</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">

    <!-- lewa kolumna — formularz -->
    <div class="left-panel">
        <h1>Kalkulator BMI</h1>

        <form method="post">
            <label>Płeć:</label>
            <div class="row">
                <label><input type="radio" name="gender" value="male" checked> Mężczyzna</label>
                <label><input type="radio" name="gender" value="female"> Kobieta</label>
            </div>

            <label>Wiek:</label>
            <input type="number" name="age" placeholder="np. 28">

            <label>Waga (kg)</label>
            <input type="number" name="weight" step="0.1" placeholder="np. 75">

            <label>Wzrost (cm)</label>
            <input type="number" name="height" step="0.1" placeholder="np. 170">

            <label>Aktywność</label> 
            <select name="activity">
                <option value="sedentary">Siedzący tryb życia</option>
                <option value="light">Lekka</option>
                <option value="moderate">Umiarkowana</option>
                <option value="active">Wysoka</option>
                <option value="very_active">Bardzo wysoka</option>
            </select>

            <label>Cel</label>
            <div class="row">
                <label><input type="radio" name="goal" value="lose"> Odchudzanie</label>
                <label><input type="radio" name="goal" value="maintain" checked> Utrzymanie</label>
                <label><input type="radio" name="goal" value="build"> Budowa mięśni</label>
            </div>

            <button type="submit">Oblicz</button>
        </form>
    </div>

    <!-- kolumna z prawej strony , pokazuje ciekawostki lub wyniki -->
    <div class="right-panel">

<?php

//funkcje do obliczeń
function getBMI($weight, $height) { // obliczanie BMI 
    $h = $height / 100;
    return round($weight / ($h * $h), 1);
}

function getBMILabel($bmi) { // klasyfikacja BMI - niedowaga, waga prawidłowa, nadwaga, otyłość
    if ($bmi < 18.5) return 'niedowaga';
    if ($bmi < 25)   return 'waga prawidłowa';
    if ($bmi < 30)   return 'nadwaga';
    return 'otyłość';
}

function getBMR($w, $h, $a, $g) { // obliczanie BMR - BMR to podstawowa przemiana materii, czyli ilość kalorii, którą spalam w spoczynku
    $base = (10 * $w) + (6.25 * $h) - (5 * $a);
    if ($g === 'male') return round($base + 5);
    return round($base - 161);
}

function getTDEE($bmr, $activity) { // obliczanie TDEE - TDEE to całkowita przemiana materii, czyli ilość kalorii, którą spalam
    if ($activity === "sedentary")   return round($bmr * 1.2);
    if ($activity === "light")       return round($bmr * 1.375);
    if ($activity === "moderate")    return round($bmr * 1.55);
    if ($activity === "active")      return round($bmr * 1.725);
    if ($activity === "very_active") return round($bmr * 1.9);
    return round($bmr * 1.2);
}

function getTargetCalories($tdee, $goal) { // obliczanie docelowej ilości kalorii w zależności od celu - odchudzanie, utrzymanie, budowa mięśni
    if ($goal === "lose")  return $tdee - 500;
    if ($goal === "build") return $tdee + 300;
    return $tdee;
}

 function getProtein($w, $goal) { // obliczanie ilości białka w zależności od celu - odchudzanie, utrzymanie, budowa mięśni
    if ($goal === "lose")  return round($w * 2.2);
    if ($goal === "build") return round($w * 2.0);
    return round($w * 1.6);
}

function getFat($calories) { 
    return round(($calories * 0.25) / 9);
}

function getCarbs($calories, $protein, $fat) { // obliczanie ilości węglowodanów na podstawie pozostałych kalorii po odjęciu białka i tłuszczu
    $remaining = $calories - ($protein * 4) - ($fat * 9);
    if ($remaining < 0) return 0;
    return round($remaining / 4);
}

// jesli formularz jeszcze nie został wysłany, pokazujemy ciekawostki
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo "<div class='facts-box'>";
    echo "<h2>Czy wiesz, że...</h2>";
    echo "<ul class='facts-list'>
            <li>Woda może zwiększyć spalanie kalorii nawet o 30%.</li>
            <li>Sen poniżej 7h spowalnia metabolizm.</li>
            <li>20 minut spaceru dziennie poprawia zdrowie serca.</li>
            <li>Białko zwiększa uczucie sytości.</li>
          </ul>";
    echo "</div>";
    echo "</div></div></body></html>";
    exit;
}

// jeśli formularz został wysłany, przetwarzamy dane i pokazujemy wyniki
$gender   = $_POST["gender"];
$age      = (int) $_POST["age"];
$weight   = (float) $_POST["weight"];
$height   = (float) $_POST["height"];
$activity = $_POST["activity"];
$goal     = $_POST["goal"];

$bmi      = getBMI($weight, $height);
$bmiLabel = getBMILabel($bmi);
$bmr      = getBMR($weight, $height, $age, $gender);
$tdee     = getTDEE($bmr, $activity);
$target   = getTargetCalories($tdee, $goal);
$protein  = getProtein($weight, $goal);
$fat      = getFat($target);
$carbs    = getCarbs($target, $protein, $fat);

// przykładowe posiłki dla każdego celu
$mealsLose = [
    "Owsianka z owocami i jogurtem naturalnym",
    "Sałatka z kurczakiem i warzywami",
    "Zupa krem z brokułów + grzanka pełnoziarnista",
    "Łosoś pieczony + warzywa na parze"
];

$mealsMaintain = [
    "Makaron pełnoziarnisty z kurczakiem i warzywami",
    "Kanapki z jajkiem i awokado",
    "Ryż + tofu + warzywa stir‑fry",
    "Zupa pomidorowa z ryżem"
];

$mealsBuild = [
    "Kurczak + ryż + warzywa",
    "Jajecznica + pieczywo pełnoziarniste",
    "Owsianka z masłem orzechowym",
    "Tortilla z wołowiną i warzywami"
];

$mealList = $goal === "lose" ? $mealsLose : ($goal === "build" ? $mealsBuild : $mealsMaintain);

// wyświetlanie wyników
echo "<div class='result-box'>";
echo "<h2>Wyniki</h2>";
echo "<div class='result-line'>BMI: $bmi ($bmiLabel)</div>";
echo "<div class='result-line'>BMR: $bmr</div>";
echo "<div class='result-line'>TDEE: $tdee</div>";
echo "<div class='result-line'>Kalorie docelowe: $target</div>";
echo "<div class='result-line'>Białko: $protein g</div>";
echo "<div class='result-line'>Tłuszcze: $fat g</div>";
echo "<div class='result-line'>Węglowodany: $carbs g</div>";
echo "</div>";

echo "<div class='meal-box'>";
echo "<h3>Proponowane posiłki:</h3><ul class='meal-list'>";
foreach ($mealList as $meal) echo "<li>$meal</li>";
echo "</ul></div>";

?>

    </div>
</div>

</body>
</html>
