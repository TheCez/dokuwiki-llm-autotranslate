<?php

namespace dokuwiki\plugin\llmautotranslate;

class FakeHttpTransport implements HttpTransport {
    public string $lastUrl = '';
    public array $lastHeaders = [];
    public string $lastBody = '';

    private array $response;

    public function __construct(array $response) {
        $this->response = $response;
    }

    public function post(string $url, array $headers, string $body): array {
        $this->lastUrl = $url;
        $this->lastHeaders = $headers;
        $this->lastBody = $body;

        return $this->response;
    }
}
