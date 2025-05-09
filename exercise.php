<?php
// Database connection
$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "gym";

// Start the session for user authentication
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];  // Get the logged-in user ID from session

// Initialize selected exercises in session if not already set
if (!isset($_SESSION['selected_exercises'])) {
    $_SESSION['selected_exercises'] = [];
}

// Establish database connection
$conn = new mysqli($servername, $db_username, $db_password, $dbname);
// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch the logged-in user's username
$query = "SELECT username FROM users WHERE uid = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);  // Bind the user ID as an integer
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$username = $user['username'];  // Get the username

// Fetch exercises grouped by workout type and muscle group
$query = "SELECT * FROM exercise";
$result = $conn->query($query);

$exercisesByCategory = [];

// Group exercises by workout type and muscle group
while ($exercise = $result->fetch_assoc()) {
    $workout_type = $exercise['workout_type'];
    $muscle_group = $exercise['muscle_group'];

    if (!isset($exercisesByCategory[$workout_type])) {
        $exercisesByCategory[$workout_type] = [];
    }
    if (!isset($exercisesByCategory[$workout_type][$muscle_group])) {
        $exercisesByCategory[$workout_type][$muscle_group] = [];
    }

    $exercisesByCategory[$workout_type][$muscle_group][] = $exercise;
}

// Handle adding an exercise to the selected list
if (isset($_POST['save_exercise']) && isset($_POST['exercise']) && $_POST['exercise'] != '') {
    $exercise_id = $_POST['exercise'];
    // Add the selected exercise to the session
    if (!in_array($exercise_id, $_SESSION['selected_exercises'])) {
        $_SESSION['selected_exercises'][] = $exercise_id;
    }
}

// Handle removing an exercise from the selected list
if (isset($_POST['remove_exercise']) && isset($_POST['exercise_id'])) {
    $exercise_id = $_POST['exercise_id'];
    // Remove the exercise from the session
    if (($key = array_search($exercise_id, $_SESSION['selected_exercises'])) !== false) {
        unset($_SESSION['selected_exercises'][$key]);
    }
}

// Handle saving BMI in session and database
if (isset($_POST['calculate_bmi']) && isset($_POST['height']) && isset($_POST['weight'])) {
    $height = floatval($_POST['height']);
    $weight = floatval($_POST['weight']);

    // Calculate BMI
    $bmi = $weight / ($height * $height); // BMI formula

    // Store BMI in session
    $_SESSION['bmi'] = round($bmi, 2);

    // Save BMI to the database
    $date = date('Y-m-d H:i:s'); // Timestamp for when the BMI is saved

    // Prepare and execute the insert query for the bmi_records table
    $stmt = $conn->prepare("INSERT INTO bmi_records (uid, bmi, date) VALUES (?, ?, ?)");
    $stmt->bind_param("ids", $user_id, $bmi, $date);
    $stmt->execute();
    $stmt->close();
}

// Handle saving the custom workout
if (isset($_POST['save_custom_workout']) && !empty($_SESSION['selected_exercises'])) {
    $workout_name = "Custom Workout " . date("Y-m-d H:i:s");  // Default name or user-defined

    // Insert the custom workout into the custom_workouts table
    $query = "INSERT INTO custom_workouts (user_id, workout_name) VALUES (?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $user_id, $workout_name);  // Bind the user ID and workout name
    $stmt->execute();
    
    $custom_workout_id = $stmt->insert_id;  // Get the ID of the newly inserted workout

    // Insert exercises into the workout_exercises relationship table
    foreach ($_SESSION['selected_exercises'] as $exercise_id) {
        $query = "INSERT INTO workout_exercises (custom_workout_id, exercise_id) VALUES (?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $custom_workout_id, $exercise_id);  // Bind the custom workout ID and exercise ID
        $stmt->execute();
    }

    // Optionally, clear the selected exercises from the session after saving
    $_SESSION['selected_exercises'] = [];
}

// Fetch all the BMI records from the database to plot on the chart
$sql = "SELECT bmi, date FROM bmi_records WHERE uid = ? ORDER BY date ASC";  // Replace 1 with the actual user ID
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);  // Bind the user ID
$stmt->execute();
$result = $stmt->get_result();

// Arrays to hold the BMI values and timestamps
$bmi_values = [];
$dates = [];

while ($row = $result->fetch_assoc()) {
    $bmi_values[] = $row['bmi'];
    $dates[] = $row['date'];
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exercise Page</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="style.css">
    <style>
        /* General Body Styling */
body {
    font-family: Arial, sans-serif;
    background-color: #1a1a1a;
    color: #fff;
    margin: 0;
    padding: 0;
}
h2 {
            text-align: center;
            margin-bottom: 20px;
        }

/* Container Styling */
.container {
    background-color: rgba(0, 0, 0, 0.1);
    max-width: 800px;
    margin: 50px auto;
    padding: 20px 30px;
    border-radius: 8px;
    box-shadow: 0px 4px 10px rgba(255, 255, 255, 0.43);
    position: relative;
}


/* Headings */
h2 {
    margin-bottom: 15px;
    color: #fff;
    text-align: center;
    font-size: 24px;
}
h3 {
    margin-bottom: 15px;
    color: #fff;
}
/* Form Elements */
.container select,
.container button,
.container input[type="text"] {
    display: block;
    width: 100%;
    background-color: rgba(0, 0, 0, 0.2);
    color: white;
    padding: 10px;
    box-shadow: 0px 4px 10px rgba(255, 255, 255, 0.43);
    border-radius: 5px;
    margin: 10px 0;
    font-size: 16px;
}

/* Workout List */
.workout {
    margin-top: 20px;
}

.workout li {
    list-style: none;
    margin-bottom: 15px;
    padding: 10px;
    color: white;
    background-color: rgba(0, 0, 0, 0.4);
    border-radius: 5px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.workout span {
    font-size: 16px;
    margin-right: 10px;
}

/* Buttons */
.workout button {
    background-color: rgb(255, 0, 0);
    color: white;
    border: none;
    padding: 8px 12px;
    border-radius: 5px;
    cursor: pointer;
    font-size: 14px;
    transition: background-color 0.3s;
}

.workout button:hover {
    background-color: #cc0000;
}

#addedWorkoutsList li {
    margin-bottom: 10px;
    padding: 10px;
    background-color: rgba(0, 0, 0, 0.3);
    color: white;
    border-radius: 5px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

/* Calorie Counter */
#calorieCounter {
    margin-top: 20px;
    font-size: 18px;
    font-weight: bold;
    text-align: center;
    color: rgb(255, 0, 0);
}

/* Logo */
.logo-container {
    padding-top:0px;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 150px;
    padding: 20px;
}

.logo {
    width: 120px;
    height: auto;
    cursor: pointer;
}

/* Side Navigation */
.navbar {
            height: 100%;
    width: 250px;
    position: fixed;
    top: 0;
    left: -250px;
    background-color: #333;
    color: white;
    transition: 0.3s;
    padding-top: 60px;
        }

        .navbar a {
            padding: 12px 20px;
    text-decoration: none;
    font-size: 18px;
    color: white;
    display: block;
    transition: 0.3s;
    margin: 5px 0;
        }

        .navbar a:hover {
            color: #f1f1f1;
            background-color:rgb(255, 0, 0);
        }

        .hamburger {
            position: absolute;
            top: 20px;
            left: 20px;
            font-size: 30px;
            cursor: pointer;
            color: white;
            z-index: 1001;
        }


/* Username Display */
.username {
    position: absolute;
    top: 20px;
    right: 20px;
    color: white;
    font-weight: bold;
    font-size: 16px;
}

/* Logo Container */
.logo-container {
    margin-bottom: 30px;
    text-align: center;
}

/* Form Input Styling */
form input[type="text"] {
    border: 1px solid #fff;
}

/* Responsive Design */
@media (max-width: 768px) {
    .container {
        width: 90%;
        padding: 15px;
    }

    h2 {
        font-size: 20px;
    }

    .sidenav {
        width: 200px;
    }

    .sidenav a {
        font-size: 16px;
    }

    .openbtn {
        font-size: 16px;
        padding: 8px 15px;
    }

    .username {
        font-size: 14px;
    }
}
.selected-exercise {
            margin: 5px;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 5px;
            color: rgb(255, 0, 0);
            background-color: #f9f9f9;
        }
        .remove-button {
            background-color: rgb(255, 0, 0);
            color : rgb(255, 0, 0);
            border: none;
            padding: 3px 8px;
            margin-left: 10px;
            border-radius: 5px;
            cursor: pointer;
        }
        .remove-button:hover {
            background-color: #cc0000;
        }
       /* Containers Styling */
#container1,
#container2 {
    display: block; /* Ensures block layout to stack vertically */
    width: 100%; /* Full width */
    max-width: 800px; /* Restrict to desired maximum width */
    margin: 20px auto; /* Center align and add spacing between */
    text-align: center; /* Align text in center */
    background-color: rgba(0, 0, 0, 0.1);
    padding: 20px 30px;
    border-radius: 8px;
    box-shadow: 0px 4px 10px rgba(255, 255, 255, 0.43);
    clear: both; /* Prevent floating elements from affecting layout */
}

/* Optional: Parent container override */
.parent-container {
    display: block; /* Ensure parent does not enforce side-by-side */
    width: 100%;
}
        .btn{
            background-color: rgb(255, 0, 0);
            color : rgb(0, 0, 0);
            border: none;
            padding: 3px 8px;
            margin-left: 10px;
            border-radius: 5px;
            width: 150px;
            cursor: pointer;
        }
        .btn:hover {
            background-color:rgba(204, 0, 0, 0.6);
            color: white;
        }
    </style>
</head>
<body>

<div class="hamburger" onclick="toggleNavbar()">&#9776;</div>
<div class="navbar" id="navbar">
    <h1 style="align-text:left;">Welcome, <?php echo htmlspecialchars($username); ?>!</h1>
    <a href="home.php">Home</a>
    <a href="exercise.php">Exercise</a>
    <a href="profile.php">Profile</a>
    <a href="diet.php">Diet</a>
    <a href="membership.php">Membership</a>
</div>

<!-- Logo centered on the page -->
<div class="logo-container">
    <a href="home.php"><img src="images/logo1.png" alt="Home Logo" class="logo"></a>
</div>

<!-- Main content -->
<div class="parent-container">
<div class="container" style="width: 900px; margin-top: 680px;position: relative;">
    <h2>Select an Exercise</h2>
    <form method="post" action="exercise.php">
        <h3>Choose Exercise</h3>
        <select name="exercise" id="exercise">
            <option value="">Select Exercise</option>
            <?php foreach ($exercisesByCategory as $workoutType => $muscleGroups): ?>
                <optgroup label="<?php echo htmlspecialchars($workoutType); ?>">
                    <?php foreach ($muscleGroups as $muscleGroup => $exercises): ?>
                        <optgroup label="<?php echo htmlspecialchars($muscleGroup); ?>">
                            <?php foreach ($exercises as $exercise): ?>
                                <option value="<?php echo htmlspecialchars($exercise['id']); ?>">
                                    <?php echo htmlspecialchars($exercise['workout_name']); ?> (<?php echo htmlspecialchars($exercise['calories']); ?> Calories)
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </optgroup>
            <?php endforeach; ?>
        </select>
        <button type="submit" name="save_exercise">Save Exercise</button>
    </form>

    <h3>Selected Exercises</h3>
    <div>
        <?php if (!empty($_SESSION['selected_exercises'])): ?>
            <?php
            // Fetch exercises' details based on their IDs
            $selected_exercises_ids = implode(',', $_SESSION['selected_exercises']);
            $query = "SELECT * FROM exercise WHERE id IN ($selected_exercises_ids)";
            $conn = new mysqli($servername, $db_username, $db_password, $dbname);
            $result = $conn->query($query);

            while ($exercise = $result->fetch_assoc()):
            ?>
                <div class="selected-exercise">
                    <span><?php echo htmlspecialchars($exercise['workout_name']); ?> (<?php echo htmlspecialchars($exercise['calories']); ?> Calories)</span>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="exercise_id" value="<?php echo $exercise['id']; ?>">
                        <button type="submit" name="remove_exercise" class="remove-button">Remove</button>
                    </form>
                </div>
            <?php endwhile; ?>
            <?php $conn->close(); ?>
        <?php else: ?>
            <p>No exercises selected.</p>
        <?php endif; ?>
    </div>
    <form method="post" action="exercise.php">
        <button type="submit" name="save_custom_workout">Save Custom Workout</button>
    </form>
</div>

<!-- BMI Section (Moved Below Exercise Section) -->
<div id="container2">
    <h3>Enter Your Height and Weight to Calculate BMI</h3>
    <form method="post" action="exercise.php">
        <label for="height">Height (in meters):</label>
        <input type="number" name="height" step="0.01" min="0.1" max="3.0" placeholder="Enter height" required style="width:105px;">

        <label for="weight">Weight (in kg):</label>
        <input type="number" name="weight" step="0.1" min="1" max="300" placeholder="Enter weight" required style="width:105px;"><br><br>

        <button type="submit" name="calculate_bmi" class="btn" >Calculate BMI</button>
    </form>
</div>

<!-- Your BMI Chart -->
<div id="container1" style="">
    <h3>Your BMI Over Time</h3>
    <div style="max-width: 600px; margin: 0 auto;background-color: rgb(255, 255, 255);">
        <canvas id="bmiChart" width="400" height="200"></canvas>
    </div>
</div>

<script>
    var ctx = document.getElementById('bmiChart').getContext('2d');
    var bmiChart = new Chart(ctx, {
        type: 'line', // Use line chart to create an area chart
        data: {
            labels: <?php echo json_encode($dates); ?>, // Dates as labels
            datasets: [{
                label: 'BMI',
                data: <?php echo json_encode($bmi_values); ?>, // BMI values
                backgroundColor: 'rgba(75, 192, 192, 0.2)', // Light green fill color
                borderColor: 'rgba(75, 192, 192, 1)', // Dark green border color
                borderWidth: 1,
                fill: true // Makes the chart an area chart
            }]
        },
        options: {
            responsive: true,
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Date'
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'BMI'
                    },
                    min: 10, // You can set a minimum BMI for better visualization
                    max: 50 // Adjust according to your data
                }
            }
        }
    });
</script>

<script>
    let selectedExercises = [];

    function addWorkout(workoutName, calories, workoutId) {
        const addedWorkoutsList = document.getElementById('addedWorkoutsList');
        const li = document.createElement('li');
        li.innerHTML = `
            ${workoutName} (${calories} Calories)
            <button onclick="removeWorkout(this, ${calories}, ${workoutId})">Remove</button>
        `;
        addedWorkoutsList.appendChild(li);

        // Add to selected exercises
        selectedExercises.push(workoutId);

        // Update total calories
        updateCalorieCounter(calories);
    }

    function updateCalorieCounter(change) {
        const calorieCounter = document.getElementById('totalCalories');
        let currentCalories = parseInt(calorieCounter.textContent) || 0;
        currentCalories += change;
        calorieCounter.textContent = currentCalories;
    }

    function removeWorkout(button, calories, workoutId) {
        const workoutItem = button.parentElement;
        workoutItem.remove();

        // Remove from selected exercises
        selectedExercises = selectedExercises.filter(id => id !== workoutId);

        // Update total calories
        updateCalorieCounter(-calories);
    }

    // When the form is submitted, send the selected exercises to the server
    document.querySelector('form[action="exercise.php"]').onsubmit = function() {
        document.getElementById('exercises').value = JSON.stringify(selectedExercises);
    };

    function createBMIChart(bmi) {
        // Define categories for BMI ranges
        const categories = ["Underweight", "Normal", "Overweight", "Obese"];
        const categoryLimits = [18.5, 24.9, 29.9, 40];

        // Assign category based on the BMI value
        let categoryIndex = 0;
        if (bmi >= 18.5 && bmi <= 24.9) {
            categoryIndex = 1; // Normal
        } else if (bmi >= 25 && bmi <= 29.9) {
            categoryIndex = 2; // Overweight
        } else if (bmi >= 30) {
            categoryIndex = 3; // Obese
        }

        // Prepare data for the chart
        const data = {
            labels: categories,
            datasets: [{
                label: 'BMI Category',
                data: [categoryLimits[0], categoryLimits[1], categoryLimits[2], categoryLimits[3]],
                backgroundColor: categoryIndex === 0 ? "rgba(255, 99, 132, 0.2)" : 
                                 categoryIndex === 1 ? "rgba(75, 192, 192, 0.2)" : 
                                 categoryIndex === 2 ? "rgba(255, 159, 64, 0.2)" : "rgba(255, 159, 64, 0.5)",
                borderColor: categoryIndex === 0 ? "rgba(255, 99, 132, 1)" : 
                             categoryIndex === 1 ? "rgba(75, 192, 192, 1)" : 
                             categoryIndex === 2 ? "rgba(255, 159, 64, 1)" : "rgba(255, 159, 64, 1)",
                borderWidth: 1
            }]
        };

        // Configure the chart
        const config = {
            type: 'bar',
            data: data,
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 40
                    }
                }
            }
        };

        // Create the chart
        const ctx = document.getElementById('bmiChart').getContext('2d');
        new Chart(ctx, config);
    }

    // Run this when the page loads and the BMI is available
    <?php if (isset($_SESSION['bmi']) && $_SESSION['bmi'] !== null): ?>
        createBMIChart(<?php echo $_SESSION['bmi']; ?>);
    <?php endif; ?>

    function toggleNavbar() {
        const navbar = document.getElementById('navbar');
        const mainContent = document.getElementById('mainContent');
        navbar.style.left = (navbar.style.left === '0px') ? '-250px' : '0px';
        mainContent.style.marginLeft = (mainContent.style.marginLeft === '0px') ? '250px' : '0px';
    }
</script>
</body>
</html>