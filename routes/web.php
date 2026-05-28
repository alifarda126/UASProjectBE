<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

Route::get('/', function () {
    return view('welcome');
});

// RUTE SEMENTARA UNTUK BACKUP DATA DARI MYSQL CLEVER CLOUD
Route::get('/backup-rahasia-moneflo', function () {
    // Mengambil semua nama tabel dari database lama
    $tables = DB::select('SHOW TABLES');
    $databaseName = DB::getDatabaseName();
    $key = "Tables_in_" . $databaseName;
    
    $sqlDump = "";
    
    foreach ($tables as $table) {
        $tableName = $table->$key;
        
        // Ambil struktur tabel (CREATE TABLE)
        $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`")[0]->{'Create Table'};
        $sqlDump .= "\n\n" . $createTable . ";\n\n";
        
        // Ambil data di dalam tabel
        $rows = DB::table($tableName)->get();
        foreach ($rows as $row) {
            $arrayRow = (array)$row;
            $columns = array_map(function($col) { return "`{$col}`"; }, array_keys($arrayRow));
            $values = array_map(function($val) { 
                if (is_null($val)) return "NULL";
                return "'" . addslashes($val) . "'"; 
            }, array_values($arrayRow));
            
            $sqlDump .= "INSERT INTO `{$tableName}` (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ");\n";
        }
    }
    
    return response($sqlDump)
        ->header('Content-Type', 'text/plain')
        ->header('Content-Disposition', 'attachment; filename="backup_moneflo.sql"');
});