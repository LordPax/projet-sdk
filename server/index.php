<?php

function readDatabase($filename)
{
    return array_map(
        fn ($line) => json_decode($line, true),
        file($filename)
    );
}

function writeDatabase($filename, $data)
{
    file_put_contents(
        $filename,
        implode(
            "\n",
            array_map(
                fn ($line) => json_encode($line),
                $data
            )
        )
    );
}

function findBy($filename, $criteria)
{
    $data = readDatabase($filename);
    $result = array_values(array_filter(
        $data,
        fn ($line) => count(array_intersect_assoc($line, $criteria)) == count($criteria)
    ));

    return $result[0] ?? null;
}

function findAppBy($criteria)
{
    return findBy('./data/apps.db', $criteria);
}
function findCodeBy($criteria)
{
    return findBy('./data/codes.db', $criteria);
}
function findTokenBy($criteria)
{
    return findBy('./data/tokens.db', $criteria);
}
function findUserBy($criteria)
{
    return findBy('./data/users.db', $criteria);
}

function insertRow($filename, $row)
{
    $data = readDatabase($filename);
    $data[] = $row;
    writeDatabase($filename, $data);
}

function insertApp($app)
{
    insertRow('./data/apps.db', $app);
}
function insertCode($code)
{
    insertRow('./data/codes.db', $code);
}
function insertToken($token)
{
    insertRow('./data/tokens.db', $token);
}

function register()
{
    ['name' => $name, 'url'=> $url, 'redirect_success' => $redirect] = $_POST;
    if (findAppBy(['name' => $name])) {
        http_response_code(409);
        return;
    }
    $app = [
        'name' => $name,
        'url' => $url,
        'redirect_success' => $redirect,
        'client_id' => bin2hex(random_bytes(16)),
        'client_secret' => bin2hex(random_bytes(16)),
    ];
    insertApp($app);
    http_response_code(201);
    echo json_encode($app);
}

function auth()
{
    ['client_id' => $clientId, 'state' => $state, 'redirect_uri' => $redirect, 'scope' => $scope] = $_GET;
    $app = findAppBy(['client_id' => $clientId, 'redirect_success' => $redirect]);
    if (is_null($app)) {
        http_response_code(404);
        return;
    }
    if (findTokenBy(['client_id' => $clientId])) {
        return authSuccess();
    }
    echo "App: $app[name]<br>";
    echo "Url: $app[url]<br>";
    echo "Scope: $scope<br>";
    echo "<a href='/auth-success?scope=$scope&client_id=$clientId&state=$state'>Oui</a>&nbsp;";
    echo "<a href='/auth-failed'>Non</a>";
}

function authSuccess()
{
    ['client_id' => $clientId, 'state'=> $state, 'scope' => $scope] = $_GET;
    $app = findAppBy(['client_id' => $clientId]);
    $redirect = $app['redirect_success'];
    $code = [
        'code' => bin2hex(random_bytes(16)),
        'client_id' => $clientId,
        'expires_at' => time() + 3600,
        'scope' => $scope,
        'user_id' => 1,
    ];
    insertCode($code);
    header("Location: $redirect?code=$code[code]&state=$state");
}

function handleAuthCode($clientId)
{
    ['code' => $code] = $_GET;
    $code = findCodeBy(['code' => $code, 'client_id' => $clientId]);
    if (!$code) {
        http_response_code(404);
        throw new Exception();
    }
    if ($code['expires_at'] < time()) {
        http_response_code(400);
        throw new Exception();
    }
    return $code['user_id'];
}

function handlePassword()
{
    ['username' => $username, 'password' => $password] = $_GET;
    $user = findUserBy(['username' => $username, 'password' => $password]);
    if (!$user) {
        http_response_code(401);
        throw new Exception();
    }
    return $user['id'];
}

function token()
{
    ['grant_type'=> $grantType, 'redirect_uri' => $redirect, 'client_id' => $clientId, 'client_secret' => $clientSecret] = $_GET;
    $app = findAppBy(['client_id' => $clientId, 'client_secret' => $clientSecret, 'redirect_success' => $redirect]);
    
    if (!$app) {
        http_response_code(401);
        return;
    }
    try {
        $userId = match ($grantType) {
            'authorization_code' => handleAuthCode($clientId),
            'password' => handlePassword(),
            'client_credentials'=> null
        };

        $token = [
            'token' => bin2hex(random_bytes(16)),
            'client_id' => $clientId,
            'user_id' => $userId,
            'expires_at' => time() + 3600,
        ];
        insertToken($token);
        http_response_code(201);
        echo json_encode([
            'access_token' => $token['token'],
            'expires_in' => 3600,
        ]);
    } catch (\Exception $e) {
        error_log($e->getMessage());
    }
}

function checkToken()
{
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
    if (!$authHeader) {
        http_response_code(401);
        return;
    }
    [$type, $token] = explode(' ', $authHeader);
    if ($type != 'Bearer') {
        http_response_code(401);
        return;
    }
    $token = findTokenBy(['token' => $token]);
    if (!$token) {
        http_response_code(401);
        return;
    }
    if ($token['expires_at'] < time()) {
        http_response_code(401);
        return;
    }
    return $token;
}

function me()
{
    if (($token = checkToken()) === null) {
        return;
    }
    if (($user = findUserBy([ 'id' => $token['user_id'] ])) === null) {
        http_response_code(401);
        return;
    }
    echo json_encode($user);
}
function stats()
{
    if (!checkToken()) {
        return;
    }
    echo json_encode([
        "CA"=> 100000,
        "NbVisites"=> 100000,
        "NbClients"=> 100000,
        "NbProduits"=> 100000,
        "NbCommandes"=> 100000,
    ]);
}

$route = $_SERVER["REQUEST_URI"];
switch (strtok($route, "?")) {
    case '/register':
        register();
        break;
    case '/auth':
        auth();
        break;
    case '/auth-success':
        authSuccess();
        break;
    case '/token':
        token();
        break;
    case '/me':
        me();
        break;
    case '/stats':
        stats();
        break;
    default:
        http_response_code(404);
}
