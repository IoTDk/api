<?php
/**
 * Created by PhpStorm.
 * User: David
 * Date: 14/11/2017
 * Time: 10:33
 */

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require '../vendor/autoload.php';
require '../src/config/database.php';
require '../src/routes/users.php';
require '../src/routes/devices.php';

$app->run();