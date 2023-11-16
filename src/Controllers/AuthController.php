<?php 

// api/src/Controllers/AuthController.php
namespace Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;

class AuthController
{
    private $db;
    private $secretKey = 'tu_clave_secreta_aqui';

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function authenticate(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $username = $data['username'];
        $password = $data['password'];

        // Verificar las credenciales en la base de datos
        if ($this->validateUserCredentials($username, $password)) {
            $token = $this->generateToken(['username' => $username]);
            return $response->withJson(['token' => $token]);
        } else {
            return $response->withJson(['error' => 'Credenciales no válidas'], 401);
        }
    }

    private function validateUserCredentials($username, $password)
    {
        // Buscar usuario en la base de datos
        $query = "SELECT * FROM pkg_user WHERE username = :username";
        $statement = $this->db->prepare($query);
        $statement->bindParam(':username', $username);
        $statement->execute();

        $user = $statement->fetch();

        // Verificar si se encontró el usuario y la contraseña coincide usando SHA1
        return ($user && sha1($password) === $user['password']);
    }

    private function generateToken($data)
    {
        $issuedAt = time();
        $expirationTime = $issuedAt + 3600; // Token expira en 1 hora

        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'data' => $data,
        ];

        return JWT::encode($payload, $this->secretKey, 'HS256');
    }
}