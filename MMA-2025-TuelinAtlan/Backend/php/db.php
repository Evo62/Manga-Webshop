<?php
    function OpenCon()
    {
    $dbhost = "localhost";
    $dbuser = "root";
    $dbpass = "";
    $conn = new mysqli($dbhost, $dbuser, $dbpass) or die("Connect failed: %s\n". $conn -> error);
    return $conn;
    }
    function OpenConDB()
     {
     $dbhost = "localhost";
     $dbuser = "root";
     $dbpass = "";
     $db = "webshop";
     $conn = new mysqli($dbhost, $dbuser, $dbpass,$db) or die("Connect failed: %s\n". $conn -> error);
     //$conn = new mysqli($dbhost, $dbuser, $dbpass) or die("Connect failed: %s\n". $conn -> error);
     return $conn;
     }

    function CloseCon($conn)
     {
     $conn -> close();
     }

    ?>
