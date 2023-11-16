<?php

// En el archivo src/index.php

require __DIR__ . '/../vendor/autoload.php';

use Slim\App;
use Controllers\AuthController;
use Middleware\AuthMiddleware;
use Slim\Http\Request;
use Slim\Http\Response;

$myfile = fopen("/root/passwordMysql.log", "r") or die("Unable to open file!");
$password = str_replace("\n","",fgets($myfile));
fclose($myfile);

error_log($password);

// Crear la aplicación Slim 
$app = new App([
    'settings' => [
        'displayErrorDetails' => false,
        'addContentLengthHeader' => false,
        'db' => [
            'host'   => '127.0.0.1',
            'user'   => 'root',
            'pass'   => $password,
            'dbname' => 'mbilling',
        ],
        'secretKey' => 'tu_clave_secreta_aqui',
    ],
]);

// Configurar el contenedor para la base de datos
$container = $app->getContainer();
$container['db'] = function ($c) {
    $db = $c['settings']['db'];
    $pdo = new PDO('mysql:host=' . $db['host'] . ';dbname=' . $db['dbname'],
        $db['user'], $db['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
};



$app->add(new \Tuupola\Middleware\JwtAuthentication([
    "path" => "/api", /* or ["/api", "/admin"] */
    "attribute" => "decoded_token_data",
    "secret" => "tu_clave_secreta_aqui",
    "algorithm" => ["HS256"],
    "error" => function ($response, $arguments) {
        $data["status"] = "error";
        $data["message"] = $arguments["message"];
        return $response
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }
]));

// Crear una instancia del controlador AuthController pasando el contenedor o la conexión a la base de datos
$authController = new AuthController($container['db']);



// Configurar las rutas
$app->post('/auth', [$authController, 'authenticate']);



$app->group('/api', function(\Slim\App $app) {
    $app->get('/user', function(Request $request, Response $response, array $args) {
        print_r($request->getAttribute('decoded_token_data'));
       
    });

    // Configurar la ruta /pkg_cdr/filterByDate (tu ruta existente)
$app->get('/pkg_cdr/filterByDate', function ($request, $response) {
    try {
        $dbSettings = $this->get('settings')['db'];
        $pdo = new PDO('mysql:host=' . $dbSettings['host'] . ';dbname=' . $dbSettings['dbname'],
            $dbSettings['user'], $dbSettings['pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $startDate = $request->getQueryParams()['start_date'];
        $endDate = $request->getQueryParams()['end_date'];
         // Concatenar horas al inicio y fin del día
         $startDate .= ' 00:00:00';
         $endDate .= ' 23:59:59';
        error_log($startDate);
        error_log($endDate);
        // Validar que se proporcionen ambas fechas
        if (empty($startDate) || empty($endDate)) {
            return $response->withJson(["error" => "Se requieren ambas fechas (start_date y end_date)"], 400);
        }

        // Lógica para obtener datos filtrados por fecha
        $query = "SELECT calledstation,sessiontime,starttime FROM pkg_cdr WHERE starttime BETWEEN :start_date AND :end_date";
        $statement = $pdo->prepare($query);
        $statement->bindParam(':start_date', $startDate);
        $statement->bindParam(':end_date', $endDate);
        $statement->execute();

        $results = $statement->fetchAll();

        if (empty($results)) {
            return $response->withJson(["message" => "No se encontraron datos para el rango de fechas proporcionado"]);
        }

        return $response->withJson($results);
    } catch (\Exception $e) {
        return $response->withJson(["error" => "Error en la consulta: " . $e->getMessage()], 500);
    }
});
});

// Iniciar la aplicación
$app->run();

