<?php
/**
 * Created by PhpStorm.
 * User: David
 * Date: 14/11/2017
 * Time: 10:54
 */

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app = new \Slim\App;

header("Content-Type: application/json");
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

//Get devices
$app->post('/devices', function(Request $request, Response $response){
    $apiLogin = $_POST['login'];
    $apiPassword = $_POST['password']; //it arrives to PHP and the credentials are OK.

    //GET DEVICE TYPE IDS
    $furl = 'https://backend.sigfox.com/api/devicetypes';
    $post = array('login' => $apiLogin, 'password' => $apiPassword);
    function curl_post($url, $post)
    {
        $defaults = array(
            CURLOPT_POST => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_URL => $url,
            CURLOPT_FRESH_CONNECT => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPHEADER => array("Authorization:Basic NTllMGJmNDU5ZTkzYTE0ZWRiYWY1NDhmOmVhNDc0YzM5MTRmMWFiYTc5NGJlNmZlYzQwOTY5NDEy"),
            CURLOPT_FORBID_REUSE => 1,
            CURLOPT_TIMEOUT => 4,
            CURLOPT_POSTFIELDS => http_build_query($post)
        );
        $ch = curl_init();
        curl_setopt_array($ch, $defaults);
        $result = curl_exec($ch);
        if (!$result) {
            trigger_error(curl_error($ch));
        }
        curl_close($ch);
        return ($result);
    }

    $result = curl_post($furl, $post);
    $res = json_decode($result, true);

    $i = 0;
    $curr_devices = array();
    foreach($res as $line) {
        $curr_devices[$i] = array();
        foreach ($line as $obj) {
            $curr_devices[$i]['devicetypeid'] = $obj['id'];
            $curr_devices[$i]['devicetypename'] = $obj['name'];
            $curr_devices[$i]['devicetypegroup'] = $obj['group'];
            $curr_devices[$i]['devicetypecontract'] = $obj['contract'];
            $i++;
        }
    }

    //GET ALL DEVICES FROM DEVICE TYPE
//    $curr_devices[$i]['devices'] = array();
    $i = 0; $j = 0;
    while ($curr_devices[$i]) {
        $surl = 'https://backend.sigfox.com/api/devicetypes/' . $curr_devices[$i]['devicetypeid'] . '/devices'; // -> fills url with appropriate device type id
        $device_raw = curl_post($surl, $post); //does the request and returns result. ----> Do smthing with this.
        $device_json = json_decode($device_raw);
        foreach ($device_json as $lines) {
            //$curr_devices[$i]['devices'] = array();
            $j = 0;
            foreach ($lines as $device) {
                $curr_devices[$i]['devices'][$j] = $device; //HAVE ALL DEVICE-TYPES AND DEVICES ----> NEED MESSAGES and might be here
                /*$turl = 'https://backend.sigfox.com/api/devices/' . $curr_devices[$i]['devices'][$j]->id . '/messages?limit=10';
                $messages_raw = curl_post($turl, $post);
                $messages_json = json_decode($messages_raw);
                $curr_devices[$i]['devices'][$j]->messages = $messages_json;*/
                $j++;
            }
        }
        $i++;
    }
    return (json_encode($curr_devices));
});

//GET SINGLE USER
$app->get('/users/{id}', function (Request $request, Response $response) {
    $id = $request->getAttribute('id');

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
    $fname = $res->data->attributes->fname;
    $lname = $res->data->attributes->lname;
    $cname = $res->data->attributes->cname;
    $email = $res->data->attributes->email;
    $password = $res->data->attributes->password;
    $apiLogin = $res->data->attributes->apilogin;
    $apiPassword = $res->data->attributes->apipassword;
    $sql = "INSERT INTO users (fname, lname, cname, email, password, apiLogin, apiPassword) VALUES (:fname, :lname, :cname, :email, :password, :apiLogin, :apiPassword)";

    $hash_options = ["cost" => 12];
    $hashed = password_hash($password, PASSWORD_BCRYPT, $hash_options);

    try {
        $db = new Database();
        $query = $db->connect();
        $stmt = $query->prepare($sql);
        $stmt->bindParam(':fname', $fname);
        $stmt->bindParam(':lname', $lname);
        $stmt->bindParam(':cname', $cname);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashed);
        $stmt->bindParam(':apiLogin', $apiLogin);
        $stmt->bindParam(':apiPassword', $apiPassword);

        $stmt->execute();

        echo '{"notice": {"text": "User Added"}}';
    } catch (PDOException $e) {
        echo '{"Error": {"text": ' . $e->getMessage() . '}';
    }
});


//Auth USER
$app->post('/auth', function (Request $request, Response $response) {
    $email = $_POST['email'];
    $sql = 'SELECT * from users WHERE email = ?';

    try {
        $db = new Database();
        $query = $db->connect();
        $stmt = $query->prepare($sql);
        $stmt->execute(array($email));
        $curr_user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (password_verify($_POST['password'], $curr_user['password']))
        {
            print_r(json_encode($curr_user));
        } else {
            echo '{"Error": {"text": "Wrong email/password combination"} }';
        }
    } catch (PDOException $e) {
        echo '{"Error": {"text": ' . $e->getMessage() . '} }';
    }
});

//UPDATE A USER
$app->put('/users/update/{id}', function (Request $request, Response $response) {
    $id = $request->getAttribute('id');
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
    $id = $request->getAttribute('id');

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