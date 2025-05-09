import { initializeApp } from "https://www.gstatic.com/firebasejs/11.1.0/firebase-app.js";
import { getAuth, onAuthStateChanged } from "https://www.gstatic.com/firebasejs/11.1.0/firebase-auth.js";
import { getDatabase, ref, set, get, child } from "https://www.gstatic.com/firebasejs/11.1.0/firebase-database.js";
import { getStorage, ref as storageRef, uploadBytes, getDownloadURL } from "https://www.gstatic.com/firebasejs/11.1.0/firebase-storage.js";

// Firebase configuration
const firebaseConfig = {
    apiKey: "AIzaSyBwDUs6323Ys9ijJAyvMfMvBDUnbteJiKE",
    authDomain: "fitnesskulture-47638.firebaseapp.com",
    databaseURL: "https://fitnesskulture-47638-default-rtdb.asia-southeast1.firebasedatabase.app",
    projectId: "fitnesskulture-47638",
    storageBucket: "fitnesskulture-47638.appspot.com",
    messagingSenderId: "411955828320",
    appId: "1:411955828320:web:9a8dd46cb32f902bb75505"
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);
const auth = getAuth(app);
const database = getDatabase(app);
const storage = getStorage(app);

// Elements
const profileForm = document.getElementById("profileForm");
const profilePictureInput = document.getElementById("profilePicture");
const profileFullName = document.getElementById("profileFullName");
const profileUsername = document.getElementById("profileUsername");
const profileEmail = document.getElementById("profileEmail");
const profilePhone = document.getElementById("profilePhone");
const profileAddress = document.getElementById("profileAddress");
const profileImage = document.getElementById("profileImage");

// Save profile data
profileForm.addEventListener("submit", async (e) => {
    e.preventDefault();

    const fullname = document.getElementById("fullname").value.trim();
    const username = document.getElementById("username").value.trim();
    const email = document.getElementById("email").value.trim();
    const phone = document.getElementById("phone").value.trim();
    const address = document.getElementById("address").value.trim();

    try {
        const user = auth.currentUser;
        if (!user) {
            alert("You must be logged in to save your profile.");
            return;
        }

        const userId = user.uid;
        const profileRef = ref(database, `profiles/${userId}`);

        let profilePictureURL = "";

        // Handle profile picture upload if a file is selected
        if (profilePictureInput.files.length > 0) {
            const file = profilePictureInput.files[0];
            const pictureRef = storageRef(storage, `profilePictures/${userId}`);
            await uploadBytes(pictureRef, file);
            profilePictureURL = await getDownloadURL(pictureRef);
        }

        // Save profile data to Firebase
        await set(profileRef, {
            fullname,
            username,
            email,
            phone,
            address,
            profilePictureURL
        });

        alert("Profile updated successfully!");
        displayProfile(); // Update profile details
    } catch (error) {
        console.error("Error saving profile:", error);
        alert("Failed to save profile. Please try again.");
    }
});

// Display profile data
function displayProfile() {
    const user = auth.currentUser;
    if (!user) {
        alert("You must be logged in to view your profile.");
        window.location.href = "index.html"; // Redirect to login page
        return;
    }

    const userId = user.uid;
    const profileRef = ref(database, `profiles/${userId}`);

    get(profileRef)
        .then((snapshot) => {
            if (snapshot.exists()) {
                const profileData = snapshot.val();

                // Display profile data
                profileFullName.textContent = profileData.fullname || "Not Provided";
                profileUsername.textContent = profileData.username || "Not Provided";
                profileEmail.textContent = profileData.email || "Not Provided";
                profilePhone.textContent = profileData.phone || "Not Provided";
                profileAddress.textContent = profileData.address || "Not Provided";

                // Display profile picture
                if (profileData.profilePictureURL) {
                    profileImage.src = profileData.profilePictureURL;
                } else {
                    profileImage.src = "";
                    profileImage.alt = "No Image Uploaded";
                }
            } else {
                alert("No profile data found.");
            }
        })
        .catch((error) => {
            console.error("Error fetching profile:", error);
            alert("Failed to fetch profile data.");
        });
}

// Check user authentication status
onAuthStateChanged(auth, (user) => {
    if (user) {
        displayProfile(); // Display profile details when the user is logged in
    } else {
        alert("You must be logged in to access your profile.");
        window.location.href = "index.html"; // Redirect to login page
    }
});
