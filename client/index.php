<?php

define("CLIENT_ID", '67dc2be521bec2ff862d3ab057de216b');
define("FB_CLIENT_ID", '449810320480230');
define("GH_CLIENT_ID", 'Iv1.567a14194dc478a3');
define("GOOGLE_CLIENT_ID", "967634149633-mqnfkpggjurpg9kst6iiatnesa6oh9ln.apps.googleusercontent.com");
define("CLIENT_SECRET", '04054cf433eeb3976252c81b6d657fda');
define("FB_CLIENT_SECRET", 'feae11b6be0b1a693152303cdedadffa');
define("GH_CLIENT_SECRET", 'cd72f250f96e1d49b4f610b4dac5ce9163863efb');
define("GOOGLE_CLIENT_SECRET", "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");


// Create a login page with a link to oauth
function login()
{
    $queryParams = http_build_query([
        "state"=>bin2hex(random_bytes(16)),
        "client_id"=> CLIENT_ID,
        "scope"=>"profile",
        "response_type"=>"code",
        "redirect_uri"=>"http://localhost:8081/oauth_success",
    ]);
    echo "
        <form method=\"POST\" action=\"/oauth_success\">
            <input type=\"text\" name=\"username\"/>
            <input type=\"password\" name=\"password\"/>
            <input type=\"submit\" value=\"Login\"/>
        </form>
    ";
    $fbQueryParams = http_build_query([
        "state"=>bin2hex(random_bytes(16)),
        "client_id"=> FB_CLIENT_ID,
        "scope"=>"public_profile,email",
        "redirect_uri"=>"https://localhost/fb_oauth_success",
    ]);
    $ghQueryParams = http_build_query([
        "state"=>bin2hex(random_bytes(16)),
        "client_id"=> GH_CLIENT_ID,
        "scope"=>"user,repo",
        "redirect_uri"=>"https://localhost/gh_oauth_success",
    ]);
    $googleQueryParams = http_build_query([
        "state"=>bin2hex(random_bytes(16)),
        "client_id"=> GOOGLE_CLIENT_ID,
        "scope"=>"profile",
        "redirect_uri"=>"https://localhost/google_oauth_success",
        "response_type" => "code"
    ]);

    echo "<a href=\"https://accounts.google.com/o/oauth2/auth?{$googleQueryParams}\">Login with Google</a><br>";
    echo "<a href=\"http://localhost:8080/auth?{$queryParams}\">Login with Oauth-Server</a><br>";
    echo "<a href=\"https://www.facebook.com/v13.0/dialog/oauth?{$fbQueryParams}\">Login with Facebook</a><br>";
    echo "<a href=\"https://github.com/login/oauth/authorize?{$ghQueryParams}\">Login with Github</a>";
}

// get token from code then get user info
function callback()
{
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        ["username"=> $username, "password" => $password] = $_POST;
        $specifParams = [
            "grant_type" => "password",
            "username" => $username,
            "password" => $password,
       ];
    } else {
        ["code"=> $code, "state" => $state] = $_GET;
        $specifParams = [
            "grant_type" => "authorization_code",
            "code" => $code
        ];
    }
    $queryParams = http_build_query(array_merge(
        $specifParams,
        [
            "redirect_uri" => "http://localhost:8081/oauth_success",
            "client_id" => CLIENT_ID,
            "client_secret" => CLIENT_SECRET,
        ]
    ));
    $response = file_get_contents("http://server:8080/token?{$queryParams}");
    if (!$response) {
        echo $http_response_header;
        return;
    }
    ["access_token" => $token] = json_decode($response, true);


    $context = stream_context_create([
        "http"=>[
            "header"=>"Authorization: Bearer {$token}"
        ]
    ]);
    $response = file_get_contents("http://server:8080/me", false, $context);
    if (!$response) {
        echo $http_response_header;
        return;
    }
    var_dump(json_decode($response, true));
}

// Facebook oauth: exchange code with token then get user info
function fbcallback()
{
    $token = getToken("https://graph.facebook.com/v13.0/oauth/access_token", FB_CLIENT_ID, FB_CLIENT_SECRET);
    $user = getFbUser($token);
    $unifiedUser = (fn () => [
        "id" => $user["id"],
        "name" => $user["name"],
        "email" => $user["email"],
        "firstName" => $user['first_name'],
        "lastName" => $user['last_name'],
    ])();
    var_dump($unifiedUser);
}
function ghcallback()
{
    $token = getTokenGh("https://github.com/login/oauth/access_token", GH_CLIENT_ID, GH_CLIENT_SECRET);
    $user = getGhUser($token);
    var_dump($user);
}
function googlecallback()
{
    $token = getTokenGoogle("https://oauth2.googleapis.com/token", GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET);
    $user = getGoogleUser($token);
    var_dump($user);
}
function getFbUser($token)
{
    $context = stream_context_create([
        "http"=>[
            "header"=>"Authorization: Bearer {$token}"
        ]
    ]);
    $response = file_get_contents("https://graph.facebook.com/v13.0/me?fields=last_name,first_name,email", false, $context);
    if (!$response) {
        echo $http_response_header;
        return;
    }
    return json_decode($response, true);
}
function getGhUser($token)
{
    $context = stream_context_create([
        "http"=>[
            "header"=>"Authorization: token {$token}\r\nUser-Agent: projet SDK"
        ]
    ]);
    $response = file_get_contents("https://api.github.com/user", false, $context);
    if (!$response) {
        echo $http_response_header;
        return;
    }
    return json_decode($response, true);
}
function getGoogleUser($token)
{
    $context = stream_context_create([
        "http"=>[
            "header"=>"Authorization: Bearer {$token}"
        ]
    ]);
    $response = file_get_contents("https://people.googleapis.com/v1/people/me?personFields=names,emailAddresses", false, $context);
    if (!$response) {
        echo $http_response_header;
        return;
    }
    return json_decode($response, true);
}
function getToken($baseUrl, $clientId, $clientSecret)
{
    ["code"=> $code, "state" => $state] = $_GET;
    $queryParams = http_build_query([
        "client_id"=> $clientId,
        "client_secret"=> $clientSecret,
        "redirect_uri"=>"https://localhost/fb_oauth_success",
        "code"=> $code,
        "grant_type"=>"authorization_code",
    ]);

    $url = $baseUrl . "?{$queryParams}";
    $response = file_get_contents($url);
    if (!$response) {
        echo $http_response_header;
        return;
    }
    ["access_token" => $token] = json_decode($response, true);

    return $token;
}

function getTokenGh($baseUrl, $clientId, $clientSecret)
{
    ["code"=> $code, "state" => $state] = $_GET;
    $queryParams = http_build_query([
        "client_id"=> $clientId,
        "client_secret"=> $clientSecret,
        "redirect_uri"=>"https://localhost/gh_oauth_success",
        "code"=> $code,
    ]);

    $context = stream_context_create([
        "http"=>[
            "header"=>"Accept: application/json"
        ]
    ]);

    $url = $baseUrl . "?{$queryParams}";
    $response = file_get_contents($url, false, $context);
    if (!$response) {
        echo $http_response_header;
        return;
    }
    ["access_token" => $token] = json_decode($response, true);

    return $token;
}
function getTokenGoogle($baseUrl, $clientId, $clientSecret)
{
    ["code"=> $code, "state" => $state] = $_GET;
    $bodyParams = http_build_query([
        "client_id"=> $clientId,
        "client_secret"=> $clientSecret,
        "redirect_uri"=>"https://localhost/google_oauth_success",
        "code"=> $code,
        "grant_type" => "authorization_code",
    ]);

    $context = stream_context_create([
        "http"=>[
            "method" => "POST",
            "header"=>"Content-type: application/x-www-form-urlencoded",
            "content" => $bodyParams
        ]
    ]);

    $response = file_get_contents($baseUrl, false, $context);
    if (!$response) {
        echo $http_response_header;
        return;
    }
    ["access_token" => $token] = json_decode($response, true);

    return $token;
}


$route = $_SERVER["REQUEST_URI"];
switch (strtok($route, "?")) {
    case '/login':
        login();
        break;
    case '/oauth_success':
        callback();
        break;
    case '/fb_oauth_success':
        fbcallback();
        break;
    case '/gh_oauth_success':
        ghcallback();
        break;
    case '/google_oauth_success':
        googlecallback();
        break;
    default:
        http_response_code(404);
}
