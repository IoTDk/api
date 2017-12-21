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
        $compliant = array();
        $compliant['data'] = new stdClass();
        $compliant['data']->type = "user";
        $compliant['data']->attributes = new stdClass();
        foreach ($user[0] as $key => $value) {
            switch ($key) {
                case "id":
                    $compliant['data']->$key = $value;
                    break;
                default:
                    $compliant['data']->attributes->$key = $value;
            }
        }
        return (json_encode($compliant));
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
    $password = htmlspecialchars($_POST['password']);
    $sql = 'SELECT * from users WHERE email = ?';

    try {
        $db = new Database();
        $query = $db->connect();
        $stmt = $query->prepare($sql);
        $stmt->execute(array($email));
        $curr_user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (password_verify($password, $curr_user['password'])) // tests if given password corresponds to password in db. no isset()??
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
$app->post('/update', function (Request $request, Response $response) {
    parse_str(file_get_contents("php://input"), $put_vars);
    $id = htmlspecialchars($put_vars['userid']);
    $fname = htmlspecialchars($put_vars['fname']);
    $lname = htmlspecialchars($put_vars['lname']);
    $cname = htmlspecialchars($put_vars['cname']);
    $email = htmlspecialchars($put_vars['email']);
    $password = htmlspecialchars($put_vars['password']);
    $apiLogin = htmlspecialchars($put_vars['apiLogin']);
    $apiPassword = htmlspecialchars($put_vars['apiPassword']);

    switch ($put_vars) {
        case isset($fname):
            $sql = "UPDATE users SET fname = :fname WHERE id = $id";
            $db = new Database();
            $query = $db->connect();
            $stmt = $query->prepare($sql);
            $stmt->bindParam(':fname', $fname);
            break;
        case isset($lname):
            $sql = "UPDATE users SET lname = :lname WHERE id = $id";
            $db = new Database();
            $query = $db->connect();
            $stmt = $query->prepare($sql);
            $stmt->bindParam(':lname', $lname);
            break;
        case isset($cname):
            $sql = "UPDATE users SET cname = :cname WHERE id = $id";
            $db = new Database();
            $query = $db->connect();
            $stmt = $query->prepare($sql);
            $stmt->bindParam(':cname', $cname);
            break;
        case isset($email):
            $sql = "UPDATE users SET email = :email WHERE id = $id";
            $db = new Database();
            $query = $db->connect();
            $stmt = $query->prepare($sql);
            $stmt->bindParam(':email', $email);
            break;
        case isset($password):
            $sql = "UPDATE users SET password = :password WHERE id = $id";
            $db = new Database();
            $query = $db->connect();
            $stmt = $query->prepare($sql);
            $stmt->bindParam(':password', $password);
            break;
        case isset($apiLogin):
            $sql = "UPDATE users SET apiLogin = :apiLogin WHERE id = $id";
            $db = new Database();
            $query = $db->connect();
            $stmt = $query->prepare($sql);
            $stmt->bindParam(':apiLogin', $apiLogin);
            break;
        case isset($apiPassword):
            $sql = "UPDATE users SET apiPassword = :apiPassword WHERE id = $id";
            $db = new Database();
            $query = $db->connect();
            $stmt = $query->prepare($sql);
            $stmt->bindParam(':apiPassword', $apiPassword);
            break;
    }

    try {
        $stmt->execute();
        return('{"notice": {"text": "User Updated"}}');
    } catch (PDOException $e) {
        return('{"Error": {"text": ' . $e->getMessage() . '}');
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