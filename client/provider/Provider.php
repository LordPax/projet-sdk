<?php
class Provider {
    private $id;
    private $secret;

    /**
     * @param string $id
     * @param string $secret
     */
    public function __construct(string $id, string $secret) {
        $this->id = $id;
        $this->secret = $secret;
    }

    /**
     * get id
     * @return string
     */
    public function getId(): string {
        return $this->id;
    }

    /**
     * get secret
     * @return string
     */
    public function getSecret(): string {
        return $this->secret;
    }
}
