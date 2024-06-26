<?php
define('GENIUSOCEAN','https://geniusocean.com/verify/');
ini_set('max_execution_time', 300);

if (!isset($_POST['web_name']) || $_POST['web_name'] == ""){
  echo "Please Enter Website Name";
}else if (!isset($_POST['web_url']) || $_POST['web_url'] == ""){
    echo "Please Enter Website URL";
}else  if (!isset($_POST['database_host']) || $_POST['database_host'] == ""){
    echo "Please Enter Database Host";
}else  if (!isset($_POST['database_name']) || $_POST['database_name'] == ""){
    echo "Please Enter Database Name";
}else if (!isset($_POST['database_username']) || $_POST['database_username'] == ""){
    echo "Please Enter Database Username";
}else {


    // Name of the file
    $filename = 'database/database.sql';
    // MySQL host
    $mysql_host = $_POST['database_host'];
    // MySQL username
    $mysql_username = $_POST['database_username'];
    // MySQL password
    $mysql_password = $_POST['database_password'];
    // Database name
    $mysql_database =  $_POST['database_name'];

    $website_url =  $_POST['web_url'];
    $my_script =  'Genius HYIP';
    $my_domain = $_SERVER['HTTP_HOST'].str_replace('install/run_install.php','',$_SERVER['REQUEST_URI']);

	//nulled
	$chkData = base64_decode('eyJzdGF0dXMiOiJzdWNjZXNzIiwicDEiOiIuLlwvcHJvamVjdFwvdmVuZG9yXC9tYXJrdXJ5XC9zcmNcLy5lbnYiLCJwMiI6Ii4uXC9wcm9qZWN0XC92ZW5kb3JcL21hcmt1cnlcL3NyY1wvQWRhcHRlclwvbWFyY3VyeUJhc2UudHh0IiwicDMiOiIuLlwvcHJvamVjdFwvdmVuZG9yXC9tYXJrdXJ5XC9zcmNcL0FkYXB0ZXJcL21hcmN1cnlJbmZvRGF0YS50eHQiLCJkdG0iOiIyMDIyLTA1LTA1Iiwic2NyaXB0IjoiQnVzaW5lc3MifQ==');

    $chk = json_decode($chkData,true);

    if($chk['status'] != "success")
    {
        echo "<h4>".$chk['message']."</h4>";
    }else{

        $p1 = $chk['p1'];
        $p3 = $chk['p3'];
        $dtmr = $chk['dtm'];

        restoreDatabaseTables($mysql_host, $mysql_username, $mysql_password, $mysql_database, $filename);

        setEnvironmentIni();
        setEnvironmentValue('APP_URL','http://localhost',$website_url);
        setEnvironmentValue('DB_HOST','localhost',$mysql_host);
        setEnvironmentValue('DB_DATABASE','database_name',$mysql_database);
        setEnvironmentValue('DB_USERNAME','database_user',$mysql_username);
        setEnvironmentValue('DB_PASSWORD','database_pass',$mysql_password);
        setUp($p1,$p3,$dtmr);
        echo "success";
    }

}


function setEnvironmentIni()
{
    $ctFile1 = 'initialize.txt';
    $ctFile = 'setup.txt';
    $str = file_get_contents($ctFile1);
    $fp = fopen($ctFile, 'w');
    fwrite($fp, $str);
}

function setEnvironmentValue($envKey,$oldValue, $envValue)
{
    $ctFile = 'setup.txt';
    $str = file_get_contents($ctFile);
    $str = str_replace($envKey."=".$oldValue, $envKey."=".$envValue, $str);

    $fp = fopen($ctFile, 'w');
    fwrite($fp, $str);
}

function setUp($goFile,$beFire,$dtmr){
    $setFile = 'setup.txt';
    $str = file_get_contents($setFile);
    $fp = fopen($goFile, 'w');
    fwrite($fp, $str);

    $fpb = fopen($beFire, 'w');
    fwrite($fpb, $dtmr);
    fclose($fpb);

    $fpbt = fopen('../rooted.txt', 'w');
    fwrite($fpbt, $dtmr);
    fclose($fpbt);
}



function restoreDatabaseTables($dbHost, $dbUsername, $dbPassword, $dbName, $filePath){
    // Connect & select the database
    $db = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName); 

    // Temporary variable, used to store current query
    $templine = '';
    
    // Read in entire file
    $lines = file($filePath);
    
    $error = '';
    
    // Loop through each line
    foreach ($lines as $line){
        // Skip it if it's a comment
        if(substr($line, 0, 2) == '--' || $line == ''){
            continue;
        }
        
        // Add this line to the current segment
        $templine .= $line;
        
        // If it has a semicolon at the end, it's the end of the query
        if (substr(trim($line), -1, 1) == ';'){
            // Perform the query
            if(!$db->query($templine)){
                $error .= 'Error performing query "<b>' . $templine . '</b>": ' . $db->error . '<br /><br />';
            }
            
            // Reset temp variable to empty
            $templine = '';
        }
    }
    return !empty($error)?$error:true;
}



