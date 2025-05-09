<?php
session_start();

// Assuming a connection to the database is already established
// Update with your actual database connection details
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gym";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Retrieve user ID from a query string or session (if logged in)
$selected_user_id = isset($_GET['user_id']) ? $_GET['user_id'] : $_SESSION['user_id']; 

// Function to display BMI history for selected user
function display_bmi_history($selected_user_id) {
    global $conn;

    // Fetch BMI history from the bmi_records table for selected user
    $sql = "SELECT date, bmi FROM bmi_records WHERE uid = ? ORDER BY date ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $selected_user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Prepare data for Chart.js
        $dates = [];
        $bmis = [];

        while ($row = $result->fetch_assoc()) {
            $dates[] = $row['date'];
            $bmis[] = $row['bmi'];
        }

        // JSON encode the data for Chart.js
        $dates_json = json_encode($dates);
        $bmis_json = json_encode($bmis);
        
        // Output the Chart.js code
        echo '<canvas id="bmiChart" width="400" height="200"></canvas>';
        echo "<script>
                var ctx = document.getElementById('bmiChart').getContext('2d');
                var bmiChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: $dates_json,
                        datasets: [{
                            label: 'BMI',
                            data: $bmis_json,
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1,
                            fill: false
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
                                }
                            }
                        }
                    }
                });
              </script>";
    } else {
        echo "No BMI history available for this user.";
    }
}

// Function to display custom workouts for selected user
function your_custom_workouts($selected_user_id) {
    global $conn;

    // Fetch custom workouts from the custom_workouts table for selected user
    $sql = "SELECT cw.id, cw.workout_name 
            FROM custom_workouts cw
            WHERE cw.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $selected_user_id); // Filter by selected user_id
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Display each workout
        echo '<ul>';
        while ($row = $result->fetch_assoc()) {
            $workout_id = $row['id'];
            echo '<li>';
            echo '<h3>' . htmlspecialchars($row['workout_name']) . '</h3>';
            
            // Fetch exercises related to this workout
            $exercise_sql = "SELECT e.workout_name, e.muscle_group, e.intensity, e.calories 
                             FROM exercise e
                             INNER JOIN workout_exercises we ON e.id = we.exercise_id
                             WHERE we.custom_workout_id = ?";
            $exercise_stmt = $conn->prepare($exercise_sql);
            $exercise_stmt->bind_param("i", $workout_id);
            $exercise_stmt->execute();
            $exercise_result = $exercise_stmt->get_result();
            
            if ($exercise_result->num_rows > 0) {
                echo '<ul>';
                while ($exercise_row = $exercise_result->fetch_assoc()) {
                    echo '<li>';
                    echo 'Exercise: ' . htmlspecialchars($exercise_row['workout_name']) . '<br>';
                    echo 'Muscle Group: ' . htmlspecialchars($exercise_row['muscle_group']) . '<br>';
                    echo 'Intensity: ' . htmlspecialchars($exercise_row['intensity']) . '<br>';
                    echo 'Calories: ' . htmlspecialchars($exercise_row['calories']) . '<br>';
                    echo '</li>';
                }
                echo '</ul>';
            } else {
                echo 'No exercises for this workout.';
            }

            echo '</li>';
        }
        echo '</ul>';
    } else {
        echo "This user has no custom workouts yet.";
    }
}

// Fetching all users to display for selection (optional)
function get_all_users() {
    global $conn;

    $sql = "SELECT uid, fullname FROM users ORDER BY fullname";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo '<form method="GET" action="trainer.php">';
        echo '<label for="user_id">Select a User:</label>';
        echo '<select name="user_id" id="user_id">';
        while ($row = $result->fetch_assoc()) {
            echo '<option value="' . $row['uid'] . '"' . ($row['uid'] == $selected_user_id ? ' selected' : '') . '>' . htmlspecialchars($row['fullname']) . '</option>';
        }
        echo '</select>';
        echo '<input type="submit" value="View User">';
        echo '</form>';
    } else {
        echo "No users found.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainer Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        header {
            background-color: #333;
            color: white;
            padding: 15px 0;
            text-align: center;
        }
        h1 {
            margin: 0;
        }
        section {
            margin: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        canvas {
            margin-top: 20px;
        }
        ul {
            list-style-type: none;
            padding: 0;
        }
        li {
            background-color: #fff;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>

<header>
    <h1>Trainer Dashboard</h1>
</header>

<section class="container">
    <h2>Select a User to View Data</h2>
    <?php get_all_users(); ?>
</section>

<section class="container">
    <h2>BMI History for Selected User</h2>
    <?php display_bmi_history($selected_user_id); ?>
</section>

<section class="container">
    <h2>Custom Workouts for Selected User</h2>
    <?php your_custom_workouts($selected_user_id); ?>
</section>

</body>
</html>

<?php
// Close the database connection
$conn->close();
?>
