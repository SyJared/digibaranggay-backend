<?php
$conn = new mysqli("localhost", "root", "", "digidb");

if ($conn->connect_error) {
    die("DB connection failed");
}
