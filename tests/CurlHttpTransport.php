<?php

namespace dokuwiki\plugin\llmautotranslate;

class CurlHttpTransport implements HttpTransport {
    public function post(string $url, array $headers, string $body): array {
        $curlHeaders = [];
        foreach ($headers as $key => $value) {
            $curlHeaders[] = "$key: $value";
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $resp = curl_exec($ch);

        if ($resp === false) {
            return ['status' => 0, 'body' => curl_error($ch)];
        }

        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        return ['status' => $status, 'body' => (string) $resp];
    }
}
