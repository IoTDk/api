<?php
/**
 * Created by PhpStorm.
 * User: David
 * Date: 15/12/2017
 * Time: 09:48
 */

// this file handle requests that are relative to devices -> Sigfox API

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

//Get devices
$app->get('/devices', function(Request $request, Response $response){
    if (isset($_GET['login']) && isset($_GET['password'])) {
        $apiLogin = htmlspecialchars($_GET['login']);
        $apiPassword = htmlspecialchars($_GET['password']); //it arrives to PHP and the credentials are OK.
    } else {
        return ('{"Error": "You have to specify login AND password"');
    }
    // Each Group in Sigfox have to have a different HTTP Header which depends on Sigfox Api Login and API Password -- Got the headers using Postman.

    //GET DEVICE TYPE IDS
    $furl = 'https://backend.sigfox.com/api/devicetypes/';
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
    };

    function curl_post_sec($url, $post) {
        $defaults = array(
            CURLOPT_POST => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_URL => $url,
            CURLOPT_FRESH_CONNECT => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPHEADER => array("Authorization:Basic NWEyNjU3OTA1MDA1NzQ3YzBmZmI5ZTdlOjc2ODdkNzQ3ZTVkOGVjZjJhYjkzZTczMGNlNjJmMGZh"),
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
    };

    $bhg = 0;
    if ($apiLogin === '59e0bf459e93a14edbaf548f') {
        $result = curl_post($furl, $post);
        $bhg = 1;
    } else if ($apiLogin === '5a2657905005747c0ffb9e7e') {
        $bhg = 2;
        $result = curl_post_sec($furl, $post);
    } else {
        $result = null;
    }

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

    $my_devices = array();
    $i = 0;
    $a = 0;
    while ($curr_devices[$i]) {
        $surl = 'https://backend.sigfox.com/api/devicetypes/' . $curr_devices[$i]['devicetypeid'] . '/devices'; // -> fills url with appropriate device type id
        $device_raw = ($bhg == 1 ? curl_post($surl, $post) : curl_post_sec($surl, $post)); //does the request and returns result. ----> Do smthing with this.
        $device_json = json_decode($device_raw);
        foreach ($device_json as $lines) {
            $j = 0;
            foreach ($lines as $device) {
                $object = new stdClass();
                $object->type = "device";
                $object->attributes = new stdClass();
                $object->attributes->devicetypeid = $curr_devices[$i]['devicetypeid'];
                $object->attributes->devicetypename = $curr_devices[$i]['devicetypename'];
                $object->attributes->devicetypegroup = $curr_devices[$i]['devicetypegroup'];
                foreach ($device as $key => $value) {
                    switch ($key) {
                        case "id":
                            $key = "deviceid";
                            $object->id = $value;
                            $object->attributes->$key = $value;
                            break;
                        case "type":
                            $key = "typeofdevice";
                            $object->attributes->$key = $value;
                            break;
                        case "name":
                            $object->attributes->$key = $value;
                            break;
                        case "last":
                            $object->attributes->$key = $value;
                            break;
                        case "state":
                            $object->attributes->$key = $value;
                            break;
                        case "lat":
                            $object->attributes->$key = $value;
                            break;
                        case "lng":
                            $object->attributes->$key = $value;
                            break;
                        default:
                            break;

                    }
                }
                $my_devices['data'][$a] = $object;
                $j++;
                $a++;
            }
        }
        $i++;
    }
    return(json_encode($my_devices));
});

// GET messages from device with device id.
$app->get('/messages', function (Request $request, Response $response) {
    $apiLogin = htmlspecialchars($_GET['login']);
    $apiPassword = htmlspecialchars($_GET['password']);
    $deviceid = htmlspecialchars($_GET['id']);

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

    function second_post($url, $post) {
        $defaults = array(
            CURLOPT_POST => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_URL => $url,
            CURLOPT_FRESH_CONNECT => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPHEADER => array("Authorization:Basic NWEyNjU3OTA1MDA1NzQ3YzBmZmI5ZTdlOjc2ODdkNzQ3ZTVkOGVjZjJhYjkzZTczMGNlNjJmMGZh"),
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

    $url = 'https://backend.sigfox.com/api/devices/' . $deviceid . '/messages?limit=30';
    $post = array('login' => $apiLogin, 'password' => $apiPassword);

    $a = 0;
    if ($apiLogin === '59e0bf459e93a14edbaf548f') {
        $result = curl_post($url, $post);
        $a = 1;
    } else if ($apiLogin === '5a2657905005747c0ffb9e7') {
        $a = 2;
        $result = curl_post_sec($url, $post);
    } else {
        $result = null;
    }

    $result = ($a == 1 ? curl_post($url, $post) : second_post($url, $post));
    $res = json_decode($result);
    $true = $res->data;

    static $z = 0;
    $my_messages = array();
    foreach ($true as $message) {
        $clean_message = new stdClass();
        $clean_message->type = "message";
        $clean_message->attributes = new stdClass();
        foreach ($message as $key => $value) {
            switch ($key) {
                case "device":
                    $key = "id";
                    $clean_message->id = $value . $z;
                    $clean_message->relationships = new stdClass();
                    $clean_message->relationships->device = new stdClass();
                    $clean_message->relationships->device->data = new stdClass();
                    $clean_message->relationships->device->data->$key = $value;
                    $clean_message->relationships->device->data->type = "device";
                    break;
                case "time":
                    $clean_message->attributes->$key = $value;
                    break;
                case "data":
                    $key = "transmitted";
                    $clean_message->attributes->$key = $value;
                    break;
                default:
                    continue;
            }
        }
        $my_messages['data'][$z] = $clean_message;
        $z++;
    }
    if (empty($my_messages)) {$my_messages = new stdClass(); $my_messages->data = array();}
    return (json_encode($my_messages));
});