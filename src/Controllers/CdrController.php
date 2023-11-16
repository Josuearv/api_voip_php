<?php

namespace App\Controllers;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CdrController
{
    public function getByDate(Request $request, Response $response)
{
    try {
        $db = $this->getContainer($request)['db'];

        $startDate = $request->getQueryParams()['start_date'];
        $endDate = $request->getQueryParams()['end_date'];

        // Validar que se proporcionen ambas fechas
        if (empty($startDate) || empty($endDate)) {
            return $response->withJson(["error" => "Se requieren ambas fechas (start_date y end_date)"], 400);
        }

        // Lógica para obtener datos filtrados por fecha
        $query = "SELECT * FROM pkg_cdr WHERE starttime BETWEEN :start_date AND :end_date";
        $statement = $db->prepare($query);
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
}

private function getContainer(Request $request)
{
    return $request->getAttribute('container');
}
}
