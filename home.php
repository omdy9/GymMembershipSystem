<?php
// Database connection using MySQLi
$servername = "localhost";
$username = "root";  // Replace with your DB username
$password = "";  // Replace with your DB password
$dbname = "gym";  // Database name

// Start the session for user authentication
session_start();

// Check if the user is logged in, if not, redirect to index.php
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];  // Get the logged-in user ID from session

$conn = new mysqli($servername, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch the logged-in user's username from the users table
$query = "SELECT username FROM users WHERE uid = $user_id";
$user_result = $conn->query($query);
$user = $user_result->fetch_assoc();
$username = $user['username'];  // Get the username

// Handle Clock In/Clock Out
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['clock_in'])) {
        // Insert clock-in record (use clock_in_time instead of clock_in)
     $query = "INSERT INTO attendance (user_id, clock_in_time, attendance_date) VALUES ($user_id, NOW(), CURDATE())";

        $conn->query($query);
    } elseif (isset($_POST['clock_out'])) {
        // Update clock-out record (use clock_out_time instead of clock_out)
        $query = "UPDATE attendance SET clock_out_time = NOW() WHERE user_id = $user_id AND clock_out_time IS NULL ORDER BY clock_in_time DESC LIMIT 1";
        $conn->query($query);
    }
}

// Fetch current month's attendance
$current_month = date('m');
$current_year = date('Y');
$first_day_of_month = date('N', strtotime("{$current_year}-{$current_month}-01")); // Get first day of the month (1-7, Mon-Sun)
$days_in_month = date('t', strtotime("{$current_year}-{$current_month}-01")); // Get number of days in the month

// Fetch attendance for the current month
$query = "SELECT DAY(clock_in_time) AS day, 'present' AS status FROM attendance WHERE user_id = $user_id AND MONTH(clock_in_time) = $current_month AND YEAR(clock_in_time) = $current_year";
$attendance_result = $conn->query($query);

// Map attendance to days
$attendance = [];
while ($attendance_row = $attendance_result->fetch_assoc()) {
    $attendance[$attendance_row['day']] = $attendance_row['status'];
}

$query = "SELECT clock_in_time, clock_out_time, attendance_date FROM attendance WHERE user_id = $user_id ORDER BY clock_in_time DESC";
$attendance_result = $conn->query($query);

// Fetch saved custom workouts
// Fetch saved custom workouts for the logged-in user
$query = "SELECT * FROM custom_workouts WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Gym</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Global styles */
        /* Global styles */
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    margin: 0;
    padding: 0;
    background-color: #f7f7f7;
    color: #333;
}
h1 {
            text-align: center;
            margin-bottom: 20px;
        }


h2 {
    color: #333;
    margin-bottom: 20px;
}

/* Centering the logo */
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

/* Display username in the top right corner */
.username {
    position: absolute;
    top: 20px;
    right: 20px;
    font-size: 18px;
    font-weight: bold;
    color: white;
}

/* Clock In/Clock Out buttons */
form button {
    padding: 12px 25px;
    margin: 10px;
    background-color: rgba(255, 0, 0, 0.37);
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
    transition: background-color 0.3s ease;
}

form button[type="submit"]:hover {
    background-color: rgba(255, 0, 0, 0.37);
}

/* Dropdown styles */
.dropdown {
    position: relative;
    display: inline-block;
}

.btn {
    background-color:rgba(255, 0, 0, 0.37);
    color: white;
    padding: 12px 20px;
    border: none;
    cursor: pointer;
    border-radius: 5px;
    font-size: 16px;
    transition: background-color 0.3s ease;
}

.btn:hover {
    background-color:rgb(255, 0, 0);
}

.dropdown-content {
    display: none;
    position: absolute;
    background-color: #fff;
    min-width: 250px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    z-index: 1;
    padding: 15px;
    border-radius: 5px;
    margin-top: 10px;
    transition: max-height 0.3s ease;
}

.dropdown-content h2 {
    margin-top: 0;
    font-size: 18px;
    color: #333;
}

.calendar {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 5px;
    font-size: 14px;
    color: #333;
}

.calendar .day {
    padding: 12px;
    text-align: center;
    border-radius: 5px;
    font-weight: bold;
    transition: background-color 0.3s ease;
}

.calendar .present {
    background-color: #4CAF50;
    color: white;
}

.calendar .absent {
    background-color: #f44336;
    color: white;
}

.calendar .empty {
    background-color: #e0e0e0;
}

/* Slideshow */
.slideshow {
    width: 100%;
    height: 300px;
    overflow: hidden;
    position: relative;
    margin-bottom: 20px;
    border-radius: 8px;
    box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
}

.slides {
    display: flex;
    transition: transform 1s ease-in-out;
}

.slides img {
    width: 100%;
    height: 300px;
    object-fit: cover;
    border-radius: 8px;
}

/* Side navigation styles */
.sidenav {
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

.sidenav a {
    padding: 12px 20px;
    text-decoration: none;
    font-size: 18px;
    color: white;
    display: block;
    transition: 0.3s;
    margin: 5px 0;
}

.sidenav a:hover {
    color: #f1f1f1;
    background-color:rgb(255, 0, 0);
}

.sidenav .closebtn {
    position: absolute;
    top: 0;
    right: 25px;
    font-size: 36px;
    margin-left: 50px;
}

.openbtn {
    font-size: 20px;
    cursor: pointer;
    background-color: #333;
    color: white;
    padding: 12px 20px;
    border: none;
    position: absolute;
    top: 20px;
    left: 20px;
    border-radius: 5px;
    transition: background-color 0.3s ease;
}

.openbtn:hover {
    background-color:rgb(255, 0, 0);
}

/* Attendance History */
.attendance-history {
    margin-top: 30px;
    background-color: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
}
.attendance-history h2 {
    color: rgb(255, 0, 0);
}

.attendance-history ul {
    list-style-type: none;
    padding: 0;
}

.attendance-history li {
    padding: 10px 0;
    border-bottom: 1px solid #ddd;
}

.attendance-history li span {
    font-weight: bold;
}

/* Workout History */
.workout-history {
    margin-top: 30px;
    background-color: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
}
.workout-history h2 {
    color: rgb(255, 0, 0);
}
.workout-history ul {
    list-style-type: none;
    padding: 0;
}

.workout-history li {
    padding: 10px 0;
    border-bottom: 1px solid #ddd;
}

.workout-history li strong {
    color: #333;
}

.workout-history li ul {
    list-style-type: square;
    margin-left: 20px;
}

.workout-history li ul li {
    font-size: 14px;
}

/* Transition for dropdown */
.dropdown-content.open {
    display: block;
}

/* Miscellaneous */
.container {
    padding-top: 460px;
}


    </style>
</head>
<body>

<!-- Side Navigation -->
<div id="mySidenav" class="sidenav">
    <a href="javascript:void(0)" class="closebtn" onclick="closeNav()">×</a>
    <h1 style="align-text:center;">Welcome, <?php echo htmlspecialchars($username); ?>!</h1>
    <a href="home.php">Home</a>
    <a href="exercise.php">Exercise</a>
    <a href="profile.php">Profile</a>
    <a href="diet.php">Diet</a>
    <a href="membership.php">Membership</a>
    <a href="logout.php">Logout</a>
</div>

<!-- Open Button to open the side navigation -->
<button class="openbtn" onclick="openNav()">☰ </button>

<!-- Username display on the top right -->
<div class="username"><?php echo $username; ?></div>

<!-- Logo centered on the page -->
<div class="logo-container">
    <a href="home.php"><img src="images/logo1.png" alt="Home Logo" class="logo"></a>
</div>

<!-- Main Container -->
<div class="container"style="width: 900px; height: 900px;align-text: center;padding-top: 550px; margin: 0 auto;">
     <!-- Slideshow -->
     <div class="slideshow">
        <div class="slides">
            <img src="images/slide2.jpeg" alt="Slide 1">
            <img src="images/slide3.jpeg" alt="Slide 2">
            <img src="images/slide5.jpeg" alt="Slide 3">
        </div>
    </div>
    <!-- Clock In/Clock Out -->
    <h2 style="color:white;">Clock In/Clock Out</h2>
    <form method="POST">
        <button type="submit" name="clock_in">Clock In</button>
        <button type="submit" name="clock_out">Clock Out</button>
    </form>
     <!-- Attendance History -->
     <div class="attendance-history">
        <h2>Your Attendance History</h2>
        <?php if ($attendance_result->num_rows > 0) : ?>
            <ul>
                <?php while ($attendance = $attendance_result->fetch_assoc()) : ?>
                    <li>
                    <span>Date:</span> <?php echo $attendance['attendance_date']; ?>
                        <span>Clock In:</span> <?php echo $attendance['clock_in_time']; ?>
                        <?php if ($attendance['clock_out_time']) : ?>
                            | <span>Clock Out:</span> <?php echo $attendance['clock_out_time']; ?>
                        <?php else : ?>
                            | Still Clocked In
                        <?php endif; ?>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else : ?>
            <p>No attendance history found.</p>
        <?php endif; ?>
    </div>

    <!-- Custom Workouts -->
    <div class="workout-history">
    <h2>Your Custom Workouts</h2>
<ul>
    <?php while ($workout = $result->fetch_assoc()): ?>
        <li>
            <strong><?php echo htmlspecialchars($workout['workout_name']); ?></strong>
            (Created on: <?php echo $workout['created_at']; ?>)
            <br>
            Exercises: 
            <?php
            // Fetch exercises associated with the custom workout
            $custom_workout_id = $workout['id'];
            $query_exercises = "SELECT e.workout_name FROM exercise e
                                JOIN workout_exercises we ON e.id = we.exercise_id
                                WHERE we.custom_workout_id = ?";
            $stmt_exercises = $conn->prepare($query_exercises);
            $stmt_exercises->bind_param("i", $custom_workout_id);
            $stmt_exercises->execute();
            $result_exercises = $stmt_exercises->get_result();

            while ($exercise = $result_exercises->fetch_assoc()) {
                echo htmlspecialchars($exercise['workout_name']) . " ";
            }
            ?>
        </li>
    <?php endwhile; ?>
</ul>

</div>


    <!-- View Attendance Dropdown -->
    <div class="dropdown">
        <button class="btn" onclick="toggleDropdown()">View Attendance</button>
        <div class="dropdown-content" id="attendanceDropdown">
            <div class="calendar-container" id="calendarContainer">
                <h2>Attendance Calendar</h2>
                <div class="calendar" id="calendar">
                    <?php
                        // Empty cells before the first day of the month
                        for ($i = 1; $i < $first_day_of_month; $i++) {
                            echo "<div class='empty'></div>";
                        }

                        // Display the days of the month
                        for ($day = 1; $day <= $days_in_month; $day++) {
                            $class = '';
                            if (isset($attendance[$day])) {
                                $class = $attendance[$day] == 'present' ? 'present' : 'absent';
                            }
                            echo "<div class='day $class'>$day</div>";
                        }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Slideshow functionality
    let slideIndex = 0;

    function showSlides() {
        let slides = document.querySelectorAll(".slides img");
        for (let i = 0; i < slides.length; i++) {
            slides[i].style.display = "none";
        }
        slideIndex++;
        if (slideIndex > slides.length) { slideIndex = 1 }
        slides[slideIndex - 1].style.display = "block";
        setTimeout(showSlides, 2000); // Change image every 2 seconds
    }
    showSlides();

    // Side Navigation Open and Close
    function openNav() {
        document.getElementById("mySidenav").style.left = "0";
    }

    function closeNav() {
        document.getElementById("mySidenav").style.left = "-250px";
    }

    // Toggle attendance dropdown
    function toggleDropdown() {
        const dropdown = document.getElementById("attendanceDropdown");
        dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
    }
    
</script>

</body>
</html>

<?php
$conn->close();
?>
