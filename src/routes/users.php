<?php
/**
 * Created by PhpStorm.
 * User: David
 * Date: 14/11/2017
 * Time: 10:54
 */

// this file handles requests that are relative to users -> mySQL database.

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app = new \Slim\App;

header("Content-Type: application/vnd.api+json");
header("Access-Control-Allow-Headers: content-type");
header("Access-Control-Allow-Origin: *");

//GET ALL USERS
$app->get('/users', function (Request $request, Response $response) {
    $sql = 'SELECT * FROM users';

    try {
        //Get the database object
        $db = new Database();
        //connect
        $query = $db->connect();

        $stmt = $query->query($sql);
        $users = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = NULL;
        echo json_encode($users);
    } catch (PDOException $e) {
        echo '{"Error": {"text": ' . $e->getMessage() . '} }';
    }
});

//GET SINGLE USER
$app->get('/users/{id}', function (Request $request, Response $response) {
    $id = htmlspecialchars($request->getAttribute('id'));

    $sql = "SELECT * FROM users WHERE id = $id";
    try {
        $db = new Database();
        $query = $db->connect();
        $stmt = $query->query($sql);
        $user = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = NULL;
        echo json_encode($user);
    } catch (PDOException $e) {
        echo '{"Error": {"text": ' . $e->getMessage() . '} }';
    }
});


//ADD A USER
$app->post('/users', function (Request $request, Response $response) {
    $res = json_decode(file_get_contents("php://input"));
    $fname = $res->data->attributes->fname; //retrieve value from php input (POST form)
    $lname = $res->data->attributes->lname;
    $cname = $res->data->attributes->cname;
    $email = $res->data->attributes->email;
    $password = $res->data->attributes->password;
    $apiLogin = $res->data->attributes->apilogin;
    $apiPassword = $res->data->attributes->apipassword;
    $sql = "INSERT INTO users (fname, lname, cname, email, password, apiLogin, apiPassword) VALUES (:fname, :lname, :cname, :email, :password, :apiLogin, :apiPassword)";

    $hash_options = ["cost" => 12];
    $hashed = password_hash($password, PASSWORD_BCRYPT, $hash_options); // hashes password before sending to db.

    try {
        $db = new Database();
        $query = $db->connect();
        $stmt = $query->prepare($sql);
        $stmt->bindParam(':fname', $fname); // set values in sql.
        $stmt->bindParam(':lname', $lname);
        $stmt->bindParam(':cname', $cname);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashed);
        $stmt->bindParam(':apiLogin', $apiLogin);
        $stmt->bindParam(':apiPassword', $apiPassword);

        $stmt->execute();

        echo '{"notice": {"text": "User Added"}}';
    } catch (PDOException $e) { // if error
        echo '{"Error": {"text": ' . $e->getMessage() . '}';
    }
});


//Auth USER
$app->post('/auth', function (Request $request, Response $response) {
    $email = htmlspecialchars($_POST['email']);
    $sql = 'SELECT * from users WHERE email = ?';

    try {
        $db = new Database();
        $query = $db->connect();
        $stmt = $query->prepare($sql);
        $stmt->execute(array($email));
        $curr_user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (password_verify($_POST['password'], $curr_user['password'])) // tests if given password corresponds to password in db.
        {
            print_r(json_encode($curr_user));
        } else {
            echo '{"Error": {"text": "Wrong email/password combination"} }'; //if password error
        }
    } catch (PDOException $e) {
        echo '{"Error": {"text": ' . $e->getMessage() . '} }'; // if query error
    }
});

//UPDATE A USER
$app->put('/users/update/{id}', function (Request $request, Response $response) {
    $id = htmlspecialchars($request->getAttribute('id'));
    parse_str(file_get_contents("php://input"), $put_vars);
    $fname = htmlspecialchars($put_vars['fname']);
    $lname = htmlspecialchars($put_vars['lname']);
    $cname = htmlspecialchars($put_vars['cname']);
    $email = htmlspecialchars($put_vars['email']);
    $password = htmlspecialchars($put_vars['password']);
    $apiLogin = htmlspecialchars($put_vars['apiLogin']);
    $apiPassword = htmlspecialchars($put_vars['apiPassword']);


    $sql = "UPDATE users SET
            fname = :fname,
            lname = :lname,
            cname = :cname,
            email = :email,
            password = :password,
            apiLogin = :apiLogin,
            apiPassword = :apiPassword
            WHERE id = $id";

    try {
        $db = new Database();
        $query = $db->connect();
        $stmt = $query->prepare($sql);
        $stmt->bindParam(':fname', $fname);
        $stmt->bindParam(':lname', $lname);
        $stmt->bindParam(':cname', $cname);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $password);
        $stmt->bindParam(':apiLogin', $apiLogin);
        $stmt->bindParam(':apiPassword', $apiPassword);

        $stmt->execute();

        echo '{"notice": {"text": "User Updated"}';
    } catch (PDOException $e) {
        echo '{"Error": {"text": ' . $e->getMessage() . '}';
    }
});


//DELETE SINGLE USER
$app->delete('/users/delete/{id}', function (Request $request, Response $response) {
    $id = htmlspecialchars($request->getAttribute('id'));

    $sql = "DELETE FROM users WHERE id = $id";
    try {
        $db = new Database();
        $query = $db->connect();
        $stmt = $query->prepare($sql);
        $stmt->execute();
        $db = NULL;
        echo '{"notice": {"text": "User Deleted"}';
    } catch (PDOException $e) {
        echo '{"Error": {"text": ' . $e->getMessage() . '} }';
    }
});