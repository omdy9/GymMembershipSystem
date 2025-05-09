// Wait for the DOM to be loaded before attaching event listeners
document.addEventListener("DOMContentLoaded", function() {

  // Get the login form and attach an event listener for form submission
  const loginForm = document.getElementById("login-form");

  loginForm.addEventListener("submit", function(event) {
    event.preventDefault(); // Prevent default form submission

    // Get values from the form inputs
    const email = document.getElementById("email").value;
    const password = document.getElementById("password").value;

    // Validate the form fields
    if (!email || !password) {
      alert("Please enter both email and password.");
      return;
    }

    // Send the login data to the server using Fetch API
    const loginData = new FormData();
    loginData.append("email", email);
    loginData.append("password", password);

    fetch("login.php", {
      method: "POST",
      body: loginData,
    })
      .then((response) => response.json())
      .then((data) => {
        // If the response is successful and contains user data
        if (data.success) {
          // Redirect to home page on successful login
          window.location.href = "home.html";
        } else {
          // If the login is unsuccessful, show an error message
          alert(data.message || "Login failed. Please check your credentials.");
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        alert("An error occurred during login.");
      });
  });

});
