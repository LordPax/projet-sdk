<?php
include_once 'Provider.php';

class FacebookProvider extends Provider {
    /**
     * @param string $id
     * @param string $secret
     */
    public function __construct(string $id, string $secret) {
        parent::__construct($id, $secret);
    }

    public function callback() {
        $token = $this->getToken("https://graph.facebook.com/v13.0/oauth/access_token");
        $user = $this->getUser("https://graph.facebook.com/v13.0/me?fields=last_name,first_name,email", $token);

        $unifiedUser = (fn () => [
            "id" => $user["id"],
            "name" => $user["name"],
            "email" => $user["email"],
            "firstName" => $user['first_name'],
            "lastName" => $user['last_name'],
        ])();

        var_dump($unifiedUser);
    }

    /**
     * get access token
     * @return string
     */
    public function getToken(string $baseUrl): string {
        ["code"=> $code, "state" => $state] = $_GET;
        $queryParams = http_build_query([
            "client_id"=> $this->getId(),
            "client_secret"=> $this->getSecret(),
            "redirect_uri"=>"https://localhost/fb_oauth_success",
            "code"=> $code,
            "grant_type"=>"authorization_code",
        ]);

        $url = $baseUrl . "?{$queryParams}";
        $response = file_get_contents($url);

        if (!$response) {
            echo $http_response_header;
            return "";
        }

        ["access_token" => $token] = json_decode($response, true);

        return $token;
    }

    /**
     * get information user
     * @return void
     */
    public function getUser(string $url, string $token) {
        $context = stream_context_create([
            "http"=>[
                "header"=>"Authorization: Bearer {$token}"
            ]
        ]);

        $response = file_get_contents($url, false, $context);
        if (!$response) {
            echo $http_response_header;
            return;
        }

        return json_decode($response, true);
    }
}
