<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
boot_session();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

function backupDatabase($pdo) {
    $tables = [];
    $result = $pdo->query("SHOW TABLES");
    
    while($row = $result->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }
    
    $return = '';
    
    foreach($tables as $table) {
        $result = $pdo->query("SELECT * FROM $table");
        $numColumns = $result->columnCount();
        
        $return .= "DROP TABLE IF EXISTS $table;\n\n";
        
        $result2 = $pdo->query("SHOW CREATE TABLE $table");
        $row2 = $result2->fetch(PDO::FETCH_NUM);
        
        $return .= $row2[1] . ";\n\n";
        
        for($i = 0; $i < $numColumns; $i++) {
            while($row = $result->fetch(PDO::FETCH_NUM)) {
                $return .= "INSERT INTO $table VALUES(";
                for($j = 0; $j < $numColumns; $j++) {
                    $row[$j] = addslashes($row[$j]);
                    $row[$j] = str_replace("\n", "\\n", $row[$j]);
                    if(isset($row[$j])) {
                        $return .= '"' . $row[$j] . '"';
                    } else {
                        $return .= '""';
                    }
                    if($j < ($numColumns - 1)) {
                        $return .= ',';
                    }
                }
                $return .= ");\n";
            }
        }
        $return .= "\n\n";
    }
    
    
    $filename = 'backup/db_backup_' . date('Y-m-d_H-i-s') . '.sql';
    
    if (!file_exists('backup')) {
        mkdir('backup', 0755, true);
    }
    
    file_put_contents($filename, $return);
    
    return $filename;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['backup'])) {
    $filename = backupDatabase($pdo);
    
    if (file_exists($filename)) {
        $_SESSION['flash_message'] = [
            'type' => 'success', 
            'message' => "Database backup created: " . basename($filename)
        ];
    } else {
        $_SESSION['flash_message'] = [
            'type' => 'danger', 
            'message' => "Backup failed"
        ];
    }
    
    header("Location: index.php");
    exit;
}
?>
