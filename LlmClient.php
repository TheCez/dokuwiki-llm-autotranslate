<?php

namespace dokuwiki\plugin\llmautotranslate;

class LlmClient {
    private HttpTransport $http;
    private string $apiUrl;
    private string $apiKey;
    private string $model;

    public function __construct(HttpTransport $http, string $apiUrl, string $apiKey, string $model) {
        $this->http = $http;
        $this->apiUrl = $apiUrl;
        $this->apiKey = $apiKey;
        $this->model = $model;
    }

    public function translate(string $systemPrompt, string $userText): string {
        $body = json_encode([
            'model' => $this->model,
            'temperature' => 0,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userText],
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->apiKey,
        ];

        $response = $this->http->post($this->apiUrl, $headers, $body);

        if ($response['status'] < 200 || $response['status'] >= 300) {
            throw new LlmException(
                'LLM request failed with status ' . $response['status'] . $this->detailSuffix($response['body']),
                $response['status']
            );
        }

        $decoded = json_decode($response['body'], true);

        if (!isset($decoded['choices'][0]['message']['content'])) {
            throw new LlmException(
                'LLM response is missing choices[0].message.content' . $this->detailSuffix($response['body']),
                502
            );
        }

        return $decoded['choices'][0]['message']['content'];
    }

    private function detailSuffix(string $body): string {
        $detail = trim($body);
        if ($detail === '') return '';
        $detail = preg_replace('/\s+/', ' ', $detail);
        if (mb_strlen($detail) > 300) $detail = mb_substr($detail, 0, 300) . '...';
        return ': ' . $detail;
    }
}
