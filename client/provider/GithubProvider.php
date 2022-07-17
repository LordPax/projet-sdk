<?php
include_once 'Provider.php';

class GithubProvider extends Provider {
    /**
     * @param string $id
     * @param string $secret
     */
    public function __construct(string $id, string $secret) {
        parent::__construct($id, $secret);
    }

    public function callback() {
        $token = $this->getToken("https://github.com/login/oauth/access_token", $this->getId(), $this->getSecret());
        $user = $this->getUser("https://api.github.com/user", $token);
        var_dump($user);
    }

    /**
     * get access token
     * @return string
     */
    public function getToken($baseUrl): string {
        ["code"=> $code, "state" => $state] = $_GET;
        $queryParams = http_build_query([
            "client_id"=> $this->getId(),
            "client_secret"=> $this->getSecret(),
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
                "header"=>"Authorization: token {$token}\r\nUser-Agent: projet SDK"
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
