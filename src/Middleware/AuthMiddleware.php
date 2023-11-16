<?php

// AuthMiddleware.php

namespace Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use Firebase\JWT\JWT;

class AuthMiddleware
{
    private $secretKey = 'tu_clave_secreta_aqui';

    public function __invoke(Request $request, Response $response, callable $next)
    {
        $authorizationHeader = $request->getHeaderLine('Authorization');

        if (empty($authorizationHeader)) {
            return $response->withJson(['error' => 'Token de autenticación no proporcionado'], 401);
        }

        $token = str_replace('Bearer ', '', $authorizationHeader);

        try {
            // Verificar y decodificar el token
            $decoded = $this->verifyToken($token);

            // Agrega los datos decodificados del token como un atributo de la solicitud
            $request = $request->withAttribute('tokenData', $decoded);

            // Continuar con la ejecución del siguiente middleware o el manejador de solicitudes
            return $next($request, $response);
        } catch (\Exception $e) {
            return $response->withJson(['error' => 'Token de autenticación no válido'], 401);
        }
    }

    private function verifyToken($token)
    {
        // Verificar el token usando Firebase JWT
        return JWT::decode($token, $this->secretKey, ['HS256']);
    }
}
