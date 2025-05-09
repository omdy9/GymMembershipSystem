// Import Firebase functions (if using Firebase)


// Firebase configuration
const firebaseConfig = {
  apiKey: "AIzaSyBwDUs6323Ys9ijJAyvMfMvBDUnbteJiKE",
  authDomain: "fitnesskulture-47638.firebaseapp.com",
  databaseURL: "https://fitnesskulture-47638-default-rtdb.asia-southeast1.firebasedatabase.app",
  projectId: "fitnesskulture-47638",
  storageBucket: "fitnesskulture-47638.firebasestorage.app",
  messagingSenderId: "411955828320",
  appId: "1:411955828320:web:9a8dd46cb32f902bb75505"
};

// Initialize Firebase
const app = firebase.initializeApp(firebaseConfig);
const database = firebase.database(app);

// Function to display available workouts based on selection
function displayWorkouts() {
  const workoutType = document.getElementById('workoutType').value;
  const intensity = document.getElementById('intensity').value;
  const muscleGroup = document.getElementById('muscleGroup').value;

  // Dynamically update muscle group options based on workout type
  const muscleGroupDropdown = document.getElementById('muscleGroup');
  muscleGroupDropdown.innerHTML = ''; // Clear current options

  if (workoutType === 'lowerbody') {
    muscleGroupDropdown.innerHTML = '<option value="legs">Legs</option>';
  } else if (workoutType === 'cardio') {
    muscleGroupDropdown.innerHTML = `  
      <option value="core">Core</option>
      <option value="running">Running</option>
      <option value="cycling">Cycling</option>
    `;
  } else {  // Upper Body
    muscleGroupDropdown.innerHTML = ` 
      <option value="chest">Chest</option>
      <option value="arms">Arms</option>
    `;
  }

  // Show available workouts for selected type and intensity
  const workoutsList = document.getElementById('workoutsList');
  workoutsList.innerHTML = ''; // Clear current list

  const workouts = getWorkouts(workoutType, intensity, muscleGroup);
  workouts.forEach(workout => {
    const li = document.createElement('li');
    li.classList.add('workout-item');
    li.innerHTML = `${workout.name} <span>(${workout.calories} Calories)</span>`;
    li.addEventListener('click', () => addWorkout(workout)); // Add workout to list
    workoutsList.appendChild(li);
  });
}

// Simulate fetching workouts based on workout type, intensity, and muscle group
function getWorkouts(type, intensity, muscleGroup) {
  const sampleWorkouts = {
    upperbody: {
      chest: {
        low: { name: 'Push-up', calories: 50 },
        medium: { name: 'Incline Push-up', calories: 70 },
        high: { name: 'Barbell Chest Press', calories: 100 }
      },
      arms: {
        low: { name: 'Dumbbell Curls', calories: 60 },
        medium: { name: 'Hammer Curls', calories: 80 },
        high: { name: 'Barbell Curls', calories: 90 }
      }
    },
    cardio: {
      core: {
        low: { name: 'Walking', calories: 60 },
        medium: { name: 'Jogging', calories: 120 },
        high: { name: 'HIIT', calories: 150 }
      },
      running: {
        low: { name: 'Slow Jogging', calories: 100 },
        medium: { name: 'Moderate Jogging', calories: 150 },
        high: { name: 'Sprinting', calories: 200 }
      },
      cycling: {
        low: { name: 'Leisure Cycling', calories: 80 },
        medium: { name: 'Moderate Cycling', calories: 130 },
        high: { name: 'High-Intensity Cycling', calories: 180 }
      }
    },
    lowerbody: {
      legs: {
        low: { name: 'Bodyweight Squats', calories: 50 },
        medium: { name: 'Lunges', calories: 80 },
        high: { name: 'Barbell Squats', calories: 120 }
      }
    }
  };

  return [sampleWorkouts[type][muscleGroup][intensity]];
}

// Add selected workout to the added workouts list
function addWorkout(workout) {
  const addedWorkoutsList = document.getElementById('addedWorkoutsList');
  const li = document.createElement('li');
  li.innerHTML = `${workout.name} <span>(${workout.calories} Calories)</span>`;
  addedWorkoutsList.appendChild(li);
}

// Save added workouts to Firebase
function saveWorkouts() {
  const addedWorkoutsList = document.getElementById('addedWorkoutsList').children;
  const workouts = [];

  // Collect workout details from the added list
  for (let i = 0; i < addedWorkoutsList.length; i++) {
    workouts.push({
      name: addedWorkoutsList[i].textContent.split('(')[0].trim(),
      calories: parseInt(addedWorkoutsList[i].textContent.split('(')[1].split(' ')[0]),
    });
  }

  if (workouts.length > 0) {
    const userId = "user123"; // Replace with actual user ID from authentication
    const workoutsRef = firebase.database().ref('workouts/' + userId);

    workouts.forEach(workout => {
      workoutsRef.push(workout);
    });

    alert("Workouts saved to Firebase!");
  } else {
    alert("No workouts to save.");
  }
}

// Initialize page with workouts based on default values
window.onload = function() {
  displayWorkouts();
};