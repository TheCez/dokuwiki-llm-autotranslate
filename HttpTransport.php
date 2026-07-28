<?php

namespace dokuwiki\plugin\llmautotranslate;

interface HttpTransport {
    /** @return array{status:int, body:string} */
    public function post(string $url, array $headers, string $body): array;
}
