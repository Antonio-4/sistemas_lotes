<?php 
session_start();

if(!isset($_SESSION['user'])){
    header("Location: ../index.php");
    exit();

}

function esAdmin(){
    return isset($_SESSION['rol']) && $_SESSION['rol'] ==='admin';
}
?>