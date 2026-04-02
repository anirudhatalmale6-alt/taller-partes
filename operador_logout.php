<?php
require 'config.php';
unset($_SESSION['operador_id'], $_SESSION['operador_nombre']);
session_destroy();
redirect('index.php');
