<?php
// Function to select all records from a table
function selectAll($table) {
    global $mycon;
    try {
        $query = "SELECT * FROM $table";
        $result = mysqli_query($mycon, $query);
        if (!$result) {
            throw new Exception(mysqli_error($mycon));
        }
        return $result;
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
        return false;
    }
}

// Function to select with conditions
function selectWhere($table, $conditions = array()) {
    global $mycon;
    try {
        // First, check if columns exist
        $tableInfo = mysqli_query($mycon, "DESCRIBE $table");
        $validColumns = array();
        while ($row = mysqli_fetch_assoc($tableInfo)) {
            $validColumns[] = $row['Field'];
        }

        $sql = "SELECT * FROM $table";
        
        if (!empty($conditions)) {
            $validConditions = array();
            foreach($conditions as $key => $value) {
                if (in_array($key, $validColumns)) {
                    $validConditions[$key] = $value;
                }
            }
            
            if (!empty($validConditions)) {
                $sql .= " WHERE ";
                $i = 0;
                foreach($validConditions as $key => $value) {
                    if ($i > 0) {
                        $sql .= " AND ";
                    }
                    $sql .= "$key = '" . mysqli_real_escape_string($mycon, $value) . "'";
                    $i++;
                }
            }
        }
        
        $result = mysqli_query($mycon, $sql);
        if (!$result) {
            throw new Exception(mysqli_error($mycon));
        }
        return $result;
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
        return false;
    }
}

// Function to select specific columns
function selectColumns($table, $columns = array(), $conditions = array()) {
    global $mycon;
    $cols = implode(", ", $columns);
    $sql = "SELECT $cols FROM $table";
    
    if (!empty($conditions)) {
        $sql .= " WHERE ";
        $i = 0;
        foreach($conditions as $key => $value) {
            if ($i > 0) {
                $sql .= " AND ";
            }
            $sql .= "$key = '$value'";
            $i++;
        }
    }
    
    $result = mysqli_query($mycon, $sql);
    return $result;
}
