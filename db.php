<?php
try {
    $db = new PDO('sqlite:C:/shack/shackcom.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("DB Error: " . $e->getMessage());
}
?>
