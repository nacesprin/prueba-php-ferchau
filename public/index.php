<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$bd = new PDO("sqlite:" . __DIR__ . "/../ferchau.db");
$bd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);

$app = AppFactory::create();

// Parse json, form data and xml
$app->addBodyParsingMiddleware(); //Necesario para parsear contenido de los request-headers en JSON

/**
 * Ruta inicial
 */
$app->get('/', function (Request $request, Response $response, $args) {
    $response->getBody()->write("FERCHAU API");
    return $response;
});

/**
 * [GET] /estudio 
 * Devuelve listado de estudios disponibles
 */
$app->get('/estudio', function (Request $request, Response $response, $args) use ($bd) {
   $res = $bd->query('SELECT * FROM estudio')->fetchAll(PDO::FETCH_ASSOC);
   $payload = json_encode($res);
   $response->getBody()->write($payload);
   return $response;
});

/**
 * [GET] /estudio/1
 * Devuelve información sobre el estudio ID=1
 */
$app->get('/estudio/{id}', function (Request $request, Response $response, $args) use($bd) {
   $stm = $bd->prepare("SELECT * FROM estudio WHERE id = :id LIMIT 1;");
   $stm->bindParam(":id", $args["id"]);
   $stm->execute();
   $res = $stm->fetch(PDO::FETCH_ASSOC);
   if($res !== false)
   {
      $payload = json_encode(['success' => true, 'data' => $res ]);
   }
   else
   {
      $payload = json_encode(['success' => false ]);
   }
   $response->getBody()->write($payload);
   return $response;
});

/**
 * [POST] /estudio
 * Crea el registro en la tabla estudio enviando el parámetro POST 'nombre'
 */
$app->post('/estudio', function (Request $request, Response $response, $args) use ($bd) {
   //El request-header debe ser tipo x-www-form-urlencoded, application/json, application/xml
   $params = (array)$request->getParsedBody();
   $stm = $bd->prepare("INSERT INTO estudio(nombre) VALUES(:nombre);");
   $stm->bindParam(":nombre", $params['nombre']);
   $res = $stm->execute();
   if ($res === true) {
      $params['id'] = $bd->lastInsertId();
      $payload = json_encode(['success' => true, 'data' => $params]);
   } else {
      $payload = json_encode(['success' => false]);
   }
   $response->getBody()->write($payload);
   return $response;
});

/**
 * [PUT] /estudio/1
 * Actualizar el registro ID=1 en tabla estudio
 */
$app->put('/estudio/{id}', function (Request $request, Response $response, $args) use($bd) {
   //El request-header debe ser tipo x-www-form-urlencoded, application/json, application/xml
   $params = (array)$request->getParsedBody(); 
   $stm = $bd->prepare("UPDATE estudio SET nombre = :nombre WHERE id = :id");
   $stm->bindParam(":id", $args["id"]);
   $stm->bindParam(":nombre", $params["nombre"]);
   $res = $stm->execute();
   if($res === true)
   {
      $payload = json_encode(['success' => true ]);
   }
   else
   {
      $payload = json_encode(['success' => false ]);
   }
   $response->getBody()->write($payload);
   return $response;
});

/**
 * [DELETE] /estudio/1
 * Elimina el registro en tabla estudio cuyo ID=1
 */
$app->delete('/estudio/{id}', function (Request $request, Response $response, $args) use ($bd) {
   //El request-header debe ser tipo x-www-form-urlencoded, application/json, application/xml
   $stm = $bd->prepare("DELETE FROM estudio WHERE id = :id");
   $stm->bindParam(":id", $args["id"]);
   $res = $stm->execute();
   if ($res === true) {
      $payload = json_encode(['success' => true]);
   } else {
      $payload = json_encode(['success' => false]);
   }
   $response->getBody()->write($payload);
   return $response;
});

$responseJsonMiddleware = function ($request, $handler): Response {
   $response = $handler->handle($request);
   return $response
      ->withHeader('Content-Type', 'application/json');
};

$loggedInMiddleware = function ($request, $handler): Response {
   if (!$request->hasHeader('Auth-Token') || $request->getHeaderLine('Auth-Token') != 'ClaveToken') {
      header('Content-Type: application/json; charset=utf-8');
      http_response_code(403);
      echo json_encode(['success' => false, 'data' => 'Auth Error']);
      die;
   }
   $response = $handler->handle($request);
   return $response;
};

$app->add($loggedInMiddleware);
$app->add($responseJsonMiddleware);

$app->run();