<?php
include_once 'Provider.php';

class OauthServerProvider extends Provider {
    /**
     * @param string $id
     * @param string $secret
     */
    public function __construct(string $id, string $secret) {
        parent::__construct($id, $secret);
    }

    public function callback() {
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

        $token = $this->getToken("http://server:8080/token", $specifParams);
        $user = $this->getUser("http://server:8080/me", $token);
        var_dump($user);
    }

    /**
     * get access token
     * @return string
     */
    public function getToken($baseUrl, $specifParams): string {
        $queryParams = http_build_query(array_merge(
            $specifParams,
            [
                "redirect_uri" => "http://localhost:8081/oauth_success",
                "client_id" => $this->getId(),
                "client_secret" => $this->getSecret(),
            ]
        ));

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
    public function getUser($url, $token) {
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
