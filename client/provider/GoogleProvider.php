<?php
include_once 'Provider.php';

class GoogleProvider extends Provider {
    /**
     * @param string $id
     * @param string $secret
     */
    public function __construct(string $id, string $secret) {
        parent::__construct($id, $secret);
    }

    public function callback() {
        $token = $this->getToken("https://oauth2.googleapis.com/token", $this->getId(), $this->getSecret());
        $user = $this->getUser("https://people.googleapis.com/v1/people/me?personFields=names,emailAddresses", $token);
        var_dump($user);
    }

    /**
     * get access token
     * @return string
     */
    public function getToken($baseUrl): string {
        ["code"=> $code, "state" => $state] = $_GET;
        $bodyParams = http_build_query([
            "client_id"=> $this->getId(),
            "client_secret"=> $this->getSecret(),
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
