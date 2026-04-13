<?php
require_once 'config/database.php';
include 'views/layout/sidebar.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>JumeauNum Dashboard</title>
    <link rel="stylesheet" href="/projeeeeet/jumeaunum/public/css/style.css?v=1">

<body>

<div class="container">

    <!-- SIDEBAR -->
    

    <!-- MAIN -->
    <div class="main">

        <!-- TOP BAR -->
        <div class="topbar">
            <h1>Dashboard</h1>
            <div class="user">Admin</div>
        </div>

        <!-- CARDS -->
        <div class="cards">

            <div class="card green">
                <h3>Dossiers</h3>
                <p>--</p>
            </div>

            <div class="card red">
                <h3>Consultations</h3>
                <p>--</p>
            </div>

            <div class="card orange">
                <h3>Admissions</h3>
                <p>--</p>
            </div>

        </div>

    </div>

</div>

</body>
</html>