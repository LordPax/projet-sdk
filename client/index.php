<?php
include_once 'provider/OauthServerProvider.php';
include_once 'provider/FacebookProvider.php';
include_once 'provider/GithubProvider.php';
include_once 'provider/GoogleProvider.php';

define("CLIENT_ID", '67dc2be521bec2ff862d3ab057de216b');
define("FB_CLIENT_ID", '449810320480230');
define("GH_CLIENT_ID", 'Iv1.567a14194dc478a3');
define("GOOGLE_CLIENT_ID", "967634149633-mqnfkpggjurpg9kst6iiatnesa6oh9ln.apps.googleusercontent.com");
define("CLIENT_SECRET", '04054cf433eeb3976252c81b6d657fda');
define("FB_CLIENT_SECRET", 'feae11b6be0b1a693152303cdedadffa');
define("GH_CLIENT_SECRET", 'cd72f250f96e1d49b4f610b4dac5ce9163863efb');
define("GOOGLE_CLIENT_SECRET", "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");

$oauthProvider = new OauthServerProvider(CLIENT_ID, CLIENT_SECRET);
$fbProvider = new FacebookProvider(FB_CLIENT_ID, FB_CLIENT_SECRET);
$ghProvider = new GithubProvider(GH_CLIENT_ID, GH_CLIENT_SECRET);
$goProvider = new GoogleProvider(GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET);

// Create a login page with a link to oauth
function login() {
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

$route = $_SERVER["REQUEST_URI"];
switch (strtok($route, "?")) {
    case '/login':
        login();
        break;
    case '/oauth_success':
        $oauthProvider->callback();
        break;
    case '/fb_oauth_success':
        $fbProvider->callback();
        break;
    case '/gh_oauth_success':
        $ghProvider->callback();
        break;
    case '/google_oauth_success':
        $goProvider->callback();
        break;
    default:
        http_response_code(404);
}
