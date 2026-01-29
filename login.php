<?php
session_start();
include 'includes/db.php';

// Redirect if already logged in
if(isset($_SESSION['role'])){
    $role = strtolower($_SESSION['role']);
    header("Location: dashboard/{$role}_dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Vaccination Dashboard Login/Register</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/feather-icons"></script>
</head>
<body class="bg-gradient-to-r from-blue-200 via-blue-100 to-white flex items-center justify-center min-h-screen font-sans relative overflow-hidden">

  <!-- Animated Background Circles -->
  <div class="absolute -top-20 -left-10 w-64 h-64 bg-blue-300 rounded-full opacity-30 animate-pulse-slow"></div>
  <div class="absolute -bottom-20 -right-10 w-64 h-64 bg-purple-300 rounded-full opacity-30 animate-pulse-slow"></div>

  <div class="w-full max-w-md bg-white rounded-3xl shadow-2xl p-10 relative z-10">
    
    <!-- Title -->
    <div class="text-center mb-6">
      <h1 class="text-3xl font-bold text-gray-800">Vaccination Portal</h1>
      <p class="text-gray-500 mt-2">Secure Access for Admins, Parents & Hospitals</p>
    </div>

    <!-- Role Selector with Icons -->
    <div class="flex justify-between mb-6">
      <button id="adminBtn" class="role-btn flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-300 text-gray-700 font-semibold bg-blue-100 transition transform hover:scale-105">
        <i data-feather="user-check"></i> Admin
      </button>
      <button id="parentBtn" class="role-btn flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-300 text-gray-700 font-semibold transition transform hover:scale-105">
        <i data-feather="user"></i> Parent
      </button>
      <button id="hospitalBtn" class="role-btn flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-300 text-gray-700 font-semibold transition transform hover:scale-105">
        <i data-feather="home"></i> Hospital
      </button>
    </div>

    <!-- Form Toggle -->
    <div class="flex justify-center mb-6 border-b border-gray-200 relative">
      <button id="loginToggle" class="toggle-btn px-6 py-2 font-semibold border-b-2 border-blue-500 text-blue-500 transition relative z-10">Login</button>
      <button id="registerToggle" class="toggle-btn px-6 py-2 font-semibold text-gray-400 transition relative z-10">Register</button>
      <div class="absolute bottom-0 left-0 w-1/2 h-0.5 bg-blue-500 transition-all duration-300" id="slider"></div>
    </div>

    <!-- Form -->
    <form id="roleForm" class="space-y-4">
      <input type="text" id="username" placeholder="Username" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 transition"/>
      <input type="password" id="password" placeholder="Password" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 transition"/>
      <input type="email" id="email" placeholder="Email" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 transition hidden"/>

      <button type="submit" class="w-full bg-gradient-to-r from-blue-500 to-purple-500 text-white py-2 rounded-lg font-semibold shadow-lg hover:from-purple-500 hover:to-blue-500 transition transform hover:scale-105 flex items-center justify-center gap-2">
        <i data-feather="log-in"></i>
        <span id="submitText">Login as Admin</span>
      </button>
    </form>

    <!-- Current Selected Info -->
    <p id="infoText" class="mt-4 text-center text-gray-500 italic">Currently: Login as Admin</p>
  </div>

  <script>
    feather.replace(); // load feather icons

    const adminBtn = document.getElementById("adminBtn");
    const parentBtn = document.getElementById("parentBtn");
    const hospitalBtn = document.getElementById("hospitalBtn");
    const roleButtons = [adminBtn, parentBtn, hospitalBtn];

    let selectedRole = "Admin";

    roleButtons.forEach(btn => {
      btn.addEventListener("click", () => {
        selectedRole = btn.innerText;
        roleButtons.forEach(b => b.classList.remove("bg-blue-100", "text-white"));
        btn.classList.add("bg-blue-100", "text-white");
        updateFormButton();
        updateInfoText();
      });
    });

    const loginToggle = document.getElementById("loginToggle");
    const registerToggle = document.getElementById("registerToggle");
    const emailField = document.getElementById("email");
    const submitText = document.getElementById("submitText");
    const submitBtn = document.querySelector("#roleForm button[type='submit']");
    const slider = document.getElementById("slider");
    let isLogin = true;

    loginToggle.addEventListener("click", () => {
      isLogin = true;
      loginToggle.classList.add("border-blue-500", "text-blue-500");
      loginToggle.classList.remove("text-gray-400");
      registerToggle.classList.remove("border-blue-500", "text-blue-500");
      registerToggle.classList.add("text-gray-400");
      emailField.classList.add("hidden");
      slider.style.left = "0%";
      updateFormButton();
      updateInfoText();
    });

    registerToggle.addEventListener("click", () => {
      isLogin = false;
      registerToggle.classList.add("border-blue-500", "text-blue-500");
      registerToggle.classList.remove("text-gray-400");
      loginToggle.classList.remove("border-blue-500", "text-blue-500");
      loginToggle.classList.add("text-gray-400");
      emailField.classList.remove("hidden");
      slider.style.left = "50%";
      updateFormButton();
      updateInfoText();
    });

    function updateFormButton() {
      submitText.innerText = (isLogin ? "Login" : "Register") + " as " + selectedRole;
    }

    function updateInfoText() {
      document.getElementById("infoText").innerText = `Currently: ${(isLogin ? "Login" : "Register")} as ${selectedRole}`;
    }

   document.getElementById("roleForm").addEventListener("submit", (e) => {
  e.preventDefault();

  const username = document.getElementById("username").value;
  const password = document.getElementById("password").value;
  const email = document.getElementById("email").value;
  const action = isLogin ? "login" : "register";

  fetch('auth.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: `username=${username}&password=${password}&email=${email}&role=${selectedRole}&action=${action}`
  })
  .then(res => res.json())
  .then(data => {
    if(data.status){
      alert(data.msg + "\nRedirecting to your dashboard...");
      // Redirect AFTER success
      window.location.href = data.redirect; // <-- this sends everyone to index.php
    } else {
      alert("Error: " + data.msg);
    }
  });
});

  </script>

  <style>
    @keyframes pulse-slow {
      0%, 100% { transform: scale(1); opacity: 0.3; }
      50% { transform: scale(1.2); opacity: 0.5; }
    }
    .animate-pulse-slow {
      animation: pulse-slow 6s infinite;
    }
  </style>
</body>
</html>
