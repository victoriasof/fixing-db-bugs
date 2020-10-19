<?php

declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

//we are going to use session variables so we need to enable sessions
session_start();

function whatIsHappening()
{
    echo '<h2>$_GET</h2>';
    var_dump($_GET);
    echo '<h2>$_POST</h2>';
    var_dump($_POST);
    echo '<h2>$_COOKIE</h2>';
    var_dump($_COOKIE);
    echo '<h2>$_SESSION</h2>';
    var_dump($_SESSION);
}

whatIsHappening(); // call function

/*
function openConnection() : PDO {
    // Try to figure out what these should be for you
    $dbhost    =  "localhost"; //"DB_HOST";//probably localhost
    $dbuser    = "becode"; //"DB_USER";
    $dbpass    = "becode123"; //"DB_USER_PASSWORD";
    $db        = "fixbug"; //"DB_NAME";

    $driverOptions = [
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'",
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    // Try to understand what happens here
    $pdo = new PDO('mysql:host='. $dbhost .';dbname='. $db, $dbuser, $dbpass, $driverOptions);

    // Why we do this here
    return $pdo;

}

//The best way to select data from your database is with prepared statements
//(this prevents SQL injection attacks), like in the code example below:

$pdo = openConnection();
$handle = $pdo->prepare('SELECT some_field FROM some_table where id = :id');
$handle->bindValue(':id', 5);
$handle->execute();
$rows = $handle->fetchAll();
echo htmlspecialchars($rows[0]['some_field']);

//ERROR: Base table or view not found: 1146 Table 'fixbug.some_table'
// doesn't exist in /var/www/fixing-db-bugs/resources/index.php on line 54

*/

$sports = ['Football', 'Tennis', 'Ping pong', 'Volley ball', 'Rugby', 'Horse riding', 'Swimming', 'Judo', 'Karate'];

function openConnection(): PDO
{
    // No bugs in this function, just use the right credentials.
    $dbhost = "localhost";
    $dbuser = "becode";
    $dbpass = "becode123";
    $db = "fixbug";

    $driverOptions = [
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'",
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    return new PDO('mysql:host=' . $dbhost . ';dbname=' . $db, $dbuser, $dbpass, $driverOptions);
}

$pdo = openConnection();

if(!empty($_POST['firstname']) && !empty($_POST['lastname'])) {
    //@todo possible bug below?
    if(empty($_POST['id'])) {
        //removed exclamation mark, because there was a logic error: if not empty then don't use it, else if empty then use it.
        $handle = $pdo->prepare('INSERT INTO user (firstname, lastname, year) VALUES (:firstname, :lastname, :year)');
        $message = 'Your record has been added';
    } else { //if not empty then use it
        //@todo why does this not work?
        $handle = $pdo->prepare('UPDATE user SET (firstname = :firstname, lastname = :lastname, year = :year) WHERE id = :id');
        // changed VALUE to SET
        $handle->bindValue(':id', $_POST['id']);
        $message = 'Your record has been updated';
    }

    $handle->bindValue(':firstname', $_POST['firstname']);
    $handle->bindValue(':lastname', $_POST['lastname']);
    $handle->bindValue(':year', date('Y'));
    $handle->execute();

    if(!empty($_POST['id'])) {
        $handle = $pdo->prepare('DELETE FROM sport WHERE user_id = :id');
        $handle->bindValue(':id', $_POST['id']);
        $handle->execute();
        $userId = $_POST['id'];
    } else {
        //why did I leave this if empty? There must be no important reason for this. Move on.


        //@todo Why does this loop not work? If only I could see the bigger picture.
        foreach ($_POST['sports'] as $sport) {
            $userId = $pdo->lastInsertId();

            $handle = $pdo->prepare('INSERT INTO sport (user_id, sport) VALUES (:userId, :sport)');
            $handle->bindValue(':userId', $userId);
            $handle->bindValue(':sport', $sport);
            $handle->execute();
        }

    } //inserted foreach loop in the else statement

}
elseif(isset($_POST['delete'])) {
    //@todo BUG? Why does always delete all my users?
    $handle = $pdo->prepare('DELETE FROM user');
    //The line below just gave me an error, probably not important. Annoying line.
    //$handle->bindValue(':id', $_POST['id']);
    $handle->execute();

    $message = 'Your record has been deleted';
}

//@todo Invalid query?
$handle = $pdo->prepare('SELECT id, concat_ws(firstname, lastname, " ") AS name, sport FROM user LEFT JOIN sport ON id = sport.user_id where year = :year order by sport');
$handle->bindValue(':year', date('Y'));
$handle->execute();
$users = $handle->fetchAll();

$saveLabel = 'Save record';
if(!empty($_GET['id'])) {
    $saveLabel = 'Update record';

    $handle = $pdo->prepare('SELECT id, firstname, lastname FROM user where id = :id');
    $handle->bindValue(':id', $_GET['id']);
    $handle->execute();
    $selectedUser = $handle->fetch();

    //This segment checks all the current sports for an existing user when you update him. Currently that is not working however. :-(
    $selectedUser['sports'] = [];
    $handle = $pdo->prepare('SELECT sport FROM sport where user_id = :id');
    $handle->bindValue(':id', $_GET['id']);
    $handle->execute();
    foreach($handle->fetchAll() AS $sport) {
        $selectedUser['sports'][] = $sport;//@todo I just want an array of all sports of this, why is it not working?
    }
}

if(empty($selectedUser['id'])) {
    $selectedUser = [
        'id' => '',
        'firstname' => '',
        'lastname' => '',
        'sports' => []
    ];
}

require 'view.php';
// All bugs where written with Love for the learning Process. No actual bugs where harmed or eaten during the creation of this code.

