import { initializeApp } from "https://www.gstatic.com/firebasejs/11.1.0/firebase-app.js";
import { getAuth, createUserWithEmailAndPassword, signInWithEmailAndPassword } from "https://www.gstatic.com/firebasejs/11.1.0/firebase-auth.js";
import { getDatabase, ref, set } from "https://www.gstatic.com/firebasejs/11.1.0/firebase-database.js";

// Firebase configuration
const firebaseConfig = {
  apiKey: "AIzaSyBwDUs6323Ys9ijJAyvMfMvBDUnbteJiKE",
  authDomain: "fitnesskulture-47638.firebaseapp.com",
  databaseURL: "https://fitnesskulture-47638-default-rtdb.asia-southeast1.firebasedatabase.app",
  projectId: "fitnesskulture-47638",
  storageBucket: "fitnesskulture-47638.appspot.com",
  messagingSenderId: "411955828320",
  appId: "1:411955828320:web:9a8dd46cb32f902bb75505",
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);
const auth = getAuth(app);
const database = getDatabase(app);

// Handle Signup
export function handleSignup(formId) {
  const form = document.getElementById(formId);

  form.addEventListener("submit", (e) => {
    e.preventDefault();

    const fullname = document.getElementById("fullname").value;
    const username = document.getElementById("username").value;
    const email = document.getElementById("email").value;
    const password = document.getElementById("password").value;

    createUserWithEmailAndPassword(auth, email, password)
      .then((userCredential) => {
        const user = userCredential.user;

        // Save additional user data to Firebase Realtime Database
        set(ref(database, `users/${user.uid}`), {
          fullname,
          username,
          email,
          createdAt: new Date().toISOString(),
        });

        // Send data to PHP server to store in MySQL
        const userData = {
          uid: user.uid,
          fullname,
          username,
          email,
          createdAt: new Date().toISOString()
        };

        // Send a POST request to PHP script
        fetch("save_user_data.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/json"
          },
          body: JSON.stringify(userData)
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            alert("Sign-up successful! Redirecting...");
            window.location.href = "index.html";
          } else {
            alert("Error saving data to PHP: " + data.message);
          }
        })
        .catch(error => {
          alert("Error: " + error.message);
        });
      })
      .catch((error) => {
        alert(`Error: ${error.message}`);
      });
  });
}

// Handle Login
export function handleLogin(formId) {
  const form = document.getElementById(formId);

  form.addEventListener("submit", (e) => {
    e.preventDefault(); // Prevent default form submission

    const email = document.getElementById("email").value;
    const password = document.getElementById("password").value;

    signInWithEmailAndPassword(auth, email, password)
      .then((userCredential) => {
        const user = userCredential.user;

        // Store user ID in localStorage for session management
        localStorage.setItem("userId", user.uid);

        alert("Login successful! Redirecting...");
        window.location.href = "home.html";
      })
      .catch((error) => {
        alert(`Error: ${error.message}`);
      });
  });
}
