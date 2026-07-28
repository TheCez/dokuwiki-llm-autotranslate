<?php

namespace dokuwiki\plugin\llmautotranslate;

use dokuwiki\HTTP\DokuHTTPClient;

class DokuHttpTransport implements HttpTransport {
    public function post(string $url, array $headers, string $body): array {
        $http = new DokuHTTPClient();
        $http->keep_alive = false;
        $http->headers = $headers;
        $http->sendRequest($url, $body, 'POST');
        $respBody = (string)$http->resp_body;
        if ($respBody === '' && !empty($http->error)) {
            $respBody = (string)$http->error;
        }
        return ['status' => (int)$http->status, 'body' => $respBody];
    }
}
