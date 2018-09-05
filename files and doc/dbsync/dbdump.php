<?php
	error_reporting( E_ALL );
	ini_set('display_errors', 1);
    $link = mysqli_connect('localhost', 'root', 'root');
    if (!$link) {
        die('Could not connect: ' . mysql_error());
    }

    $sql = 'DROP DATABASE bakeway_prod_sync';
    if (mysqli_query($link, $sql)) {
        echo "Database bakeway_prod_sync was successfully dropped\n";
    } else {
        echo 'Error dropping database: ' . mysqli_error($link) . "\n";
    }

    $sql = 'CREATE DATABASE bakeway_prod_sync';
    if (mysqli_query($link, $sql)) {
        echo "<li>Database bakeway_prod_sync was successfully created\n";
    } else {
        echo '<li> Error creating database: ' . mysqli_error($link) . "\n";
    }
?>