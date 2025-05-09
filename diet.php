<?php
// Database connection
$servername = "localhost";
$db_username = "root";  // Replace with your database username
$db_password = "";  // Replace with your database password
$dbname = "gym";  // Database name

session_start();

// Check if the user is logged in, if not, redirect to index.php
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];  // Get the logged-in user ID from session

// Establish database connection
$conn = new mysqli($servername, $db_username, $db_password, $dbname);
// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch the logged-in user's username from the users table
$query = "SELECT username FROM users WHERE uid = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);  // Bind the user ID as an integer
$stmt->execute();
$result = $stmt->get_result();

// Check if the user was found
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $username = $user['username'];  // Get the username
} else {
    // Handle the case where no user is found (optional)
    $username = "Unknown User";
}


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $meal_type = $conn->real_escape_string($_POST['meal_type']);
    $food_item = $conn->real_escape_string($_POST['food_item']);
    $calories = (int) $_POST['calories'];
    $protein = (float) $_POST['protein'];
    $carbs = (float) $_POST['carbs'];
    $fats = (float) $_POST['fats'];
    $date = $conn->real_escape_string($_POST['date']);

    $query = "INSERT INTO diet (user_id, meal_type, food_item, calories, protein, carbs, fats, date) 
              VALUES ($user_id, '$meal_type', '$food_item', $calories, $protein, $carbs, $fats, '$date')";

    if ($conn->query($query) === TRUE) {
        $success_message = "Diet record added successfully.";
    } else {
        $error_message = "Error: " . $query . "<br>" . $conn->error;
    }
}

// Fetch existing diet records for the user
$query = "SELECT * FROM diet WHERE user_id = $user_id ORDER BY date DESC";
$result = $conn->query($query);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diet Tracker</title>
    <link rel="stylesheet" href="style.css"> <!-- Assuming you have a shared style file -->
    <style>
        /* General Styles */
        body {
            font-family: Arial, sans-serif;
            background-color: #1a1a1a;
            margin: 0;
            padding: 20px;
            color: #333;
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
        }
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
        form {
            background-color: rgba(0, 0, 0, 0.2);
            max-width: 600px;
            margin: 0 auto 30px;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0px 4px 10px rgba(255, 255, 255, 0.43);
        }

        form div {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
        }

        input, select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        button {
            width: 100%;
            padding: 10px;
            background-color:rgb(255, 0, 0);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background-color:rgb(253, 0, 0);
        }

        .table-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: rgba(0, 0, 0, 0.2);
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0px 4px 10px rgba(255, 255, 255, 0.43);
        }
        .table-container h2 {
            text-align: center;
            margin: 10px;
            color:white;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }

        tbody{
            color:white;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }

        th {
            background-color:rgba(255, 0, 0, 0.87);
            color: white;
        }

        .success {
            color: green;
            text-align: center;
            margin-bottom: 10px;
        }

        .error {
            color: red;
            text-align: center;
            margin-bottom: 10px;
        }

        /* Navbar Styles (From home and exercise) */
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

        .main-content {
            margin-left: 0;
            padding: 20px;
            transition: margin-left 0.3s ease-in-out;
            margin-top: 350px;
        }

        .main-content.shifted {
            margin-left: 250px;
        }

        /* Add responsive behavior */
        @media (max-width: 768px) {
            .navbar {
                left: 0;
            }

            .main-content.shifted {
                margin-left: 250px;
            }
        }
        
        .main-content label{
            color:red;
        }

    </style>
</head>
<body>

<!-- Navbar (from home and exercise) -->
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
<!-- Main Content -->
<div class="main-content" >
    <h1>Diet Tracker</h1>

    <!-- Success or Error message -->
    <?php if (isset($success_message)): ?>
        <p class="success"><?= $success_message ?></p>
    <?php endif; ?>
    <?php if (isset($error_message)): ?>
        <p class="error"><?= $error_message ?></p>
    <?php endif; ?>

    <!-- Diet Form -->
    <form method="POST" action="">
        <div class="lb">
            <label for="meal_type">Meal Type:</label>
            <select id="meal_type" name="meal_type" required>
                <option value="Breakfast">Breakfast</option>
                <option value="Lunch">Lunch</option>
                <option value="Dinner">Dinner</option>
                <option value="Snack">Snack</option>
            </select>
        </div>
        <div>
            <label for="food_item">Food Item:</label>
            <input type="text" id="food_item" name="food_item" required>
        </div>
        <div>
            <label for="calories">Calories:</label>
            <input type="number" id="calories" name="calories">
        </div>
        <div>
            <label for="protein">Protein (g):</label>
            <input type="number" id="protein" name="protein" step="0.01">
        </div>
        <div>
            <label for="carbs">Carbs (g):</label>
            <input type="number" id="carbs" name="carbs" step="0.01">
        </div>
        <div>
            <label for="fats">Fats (g):</label>
            <input type="number" id="fats" name="fats" step="0.01">
        </div>
        <div>
            <label for="date">Date:</label>
            <input type="date" id="date" name="date" required>
        </div>
        <button type="submit">Add Diet Record</button>
    </form>

    <!-- Diet Records Table -->
    <div class="table-container" >
        <h2>Your Diet Records</h2>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Meal Type</th>
                    <th>Food Item</th>
                    <th>Calories</th>
                    <th>Protein</th>
                    <th>Carbs</th>
                    <th>Fats</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['date'] ?></td>
                        <td><?= $row['meal_type'] ?></td>
                        <td><?= $row['food_item'] ?></td>
                        <td><?= $row['calories'] ?></td>
                        <td><?= $row['protein'] ?></td>
                        <td><?= $row['carbs'] ?></td>
                        <td><?= $row['fats'] ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Javascript for navbar -->
<script>
    function toggleNavbar() {
        const navbar = document.getElementById('navbar');
        const mainContent = document.querySelector('.main-content');
        navbar.style.left = (navbar.style.left === '0px') ? '-250px' : '0px';
        mainContent.classList.toggle('shifted');
    }
</script>

</body>
</html>
