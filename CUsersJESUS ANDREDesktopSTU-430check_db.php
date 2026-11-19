<?php require '/var/www/html/vendor/autoload.php'; $db = \Config\Database::connect(); $rows = $db->table('firmas')->get()->getResultArray(); print_r($rows);
