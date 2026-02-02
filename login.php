<?php
session_start();
include "includes/db.php";

if (isset($_SESSION["role"])) {
    $role = strtolower($_SESSION["role"]);

    if ($role === "parent") {
        header("Location: parentsDashboard/parentdashboard.php");
        exit;
    }

    if ($role === "hospital") {
        header("Location: hospitalDashboard/hospitalDashboard.php");
        exit;
    }

    header("Location: mainadmin/index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Vaccination Portal</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/feather-icons"></script>
</head>
<body class="bg-gradient-to-r from-blue-200 via-blue-100 to-white flex items-center justify-center min-h-screen font-sans relative overflow-hidden">

  <div class="w-full max-w-md bg-white rounded-3xl shadow-2xl p-10 relative z-10">
    <div class="text-center mb-6">
      <h1 class="text-3xl font-bold text-gray-800">Vaccination Portal</h1>
      <p class="text-gray-500 mt-2">Secure Access for Admins, Parents & Hospitals</p>
    </div>

    <div class="flex justify-between mb-6">
      <button id="adminBtn" class="role-btn flex items-center gap-2 px-4 py-2 rounded-xl border border-blue-500 bg-blue-100 text-blue-600 font-semibold transition transform hover:scale-105">
        <i data-feather="user-check"></i> Admin
      </button>
      <button id="parentBtn" class="role-btn flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-300 text-gray-700 font-semibold transition transform hover:scale-105">
        <i data-feather="user"></i> Parent
      </button>
      <button id="hospitalBtn" class="role-btn flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-300 text-gray-700 font-semibold transition transform hover:scale-105">
        <i data-feather="home"></i> Hospital
      </button>
    </div>

    <div class="flex justify-center mb-6 border-b border-gray-200 relative">
      <button id="loginToggle" class="toggle-btn px-6 py-2 font-semibold border-b-2 border-blue-500 text-blue-500 transition relative z-10">Login</button>
      <button id="registerToggle" class="toggle-btn px-6 py-2 font-semibold text-gray-400 transition relative z-10">Register</button>
      <div class="absolute bottom-0 left-0 w-1/2 h-0.5 bg-blue-500 transition-all duration-300" id="slider"></div>
    </div>

    <form id="roleForm" class="space-y-4">
      <input type="text" id="username" placeholder="Username" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 transition"/>
      <input type="password" id="password" placeholder="Password" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 transition"/>
      <input type="email" id="email" placeholder="Email" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 transition hidden"/>

      <button type="submit" class="w-full bg-gradient-to-r from-blue-500 to-purple-500 text-white py-2 rounded-lg font-semibold shadow-lg hover:from-purple-500 hover:to-blue-500 transition transform hover:scale-105 flex items-center justify-center gap-2">
        <i data-feather="log-in"></i>
        <span id="submitText">Login as Admin</span>
      </button>
    </form>

    <p id="infoText" class="mt-4 text-center text-gray-500 italic">Currently: Login as Admin</p>
  </div>

  <script>
    feather.replace();

    const roleButtons = document.querySelectorAll(".role-btn");
    let selectedRole = "Admin";
    let isLogin = true;

    roleButtons.forEach(btn => {
      btn.addEventListener("click", () => {
        selectedRole = btn.innerText.trim();
        roleButtons.forEach(b => {
            b.classList.remove("bg-blue-100", "text-blue-600", "border-blue-500");
            b.classList.add("border-gray-300", "text-gray-700");
        });
        btn.classList.add("bg-blue-100", "text-blue-600", "border-blue-500");
        updateUI();
      });
    });

    const loginToggle = document.getElementById("loginToggle");
    const registerToggle = document.getElementById("registerToggle");
    const emailField = document.getElementById("email");
    const slider = document.getElementById("slider");

    loginToggle.addEventListener("click", () => {
      isLogin = true;
      loginToggle.classList.add("text-blue-500", "border-blue-500");
      registerToggle.classList.remove("text-blue-500", "border-blue-500");
      emailField.classList.add("hidden");
      emailField.required = false;
      slider.style.left = "0%";
      updateUI();
    });

    registerToggle.addEventListener("click", () => {
      isLogin = false;
      registerToggle.classList.add("text-blue-500", "border-blue-500");
      loginToggle.classList.remove("text-blue-500", "border-blue-500");
      emailField.classList.remove("hidden");
      emailField.required = true;
      slider.style.left = "50%";
      updateUI();
    });

    function updateUI() {
      document.getElementById("submitText").innerText = (isLogin ? "Login" : "Register") + " as " + selectedRole;
      document.getElementById("infoText").innerText = `Currently: ${(isLogin ? "Login" : "Register")} as ${selectedRole}`;
    }

   document.getElementById("roleForm").addEventListener("submit", (e) => {
  e.preventDefault();
  
  const formData = new URLSearchParams();
  formData.append("username", document.getElementById("username").value);
  formData.append("password", document.getElementById("password").value);
  formData.append("email", document.getElementById("email").value);
  formData.append("role", selectedRole.toLowerCase().trim());
  formData.append("action", isLogin ? "login" : "register");

  fetch('auth.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    console.log(data); // Debugging: See what the server sends back
    if (data.status) {
      // FORCE REDIRECT
      window.location.replace(data.redirect); 
    } else {
      alert(data.msg);
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert("Connection error. Check console.");
  });
});
  </script>
</body>
</html>