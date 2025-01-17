<?php

/**
 * Copyright 2014 SURFnet bv
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace Surfnet\YubikeyApiClient\Crypto;

use Surfnet\YubikeyApiClient\Exception\InvalidArgumentException;

class Signer
{
    /**
     * @var array Valid parameters in the response message
     */
    private static $validResponseParams = [
        'nonce',
        'otp',
        'sessioncounter',
        'sessionuse',
        'sl',
        'status',
        't',
        'timeout',
        'timestamp'
    ];

    /**
     * @var string The base64-decoded client secret
     */
    private $clientSecret;

    /**
     * @param string $clientSecret The base64-encoded client secret
     */
    public function __construct(string $clientSecret)
    {
        $this->clientSecret = base64_decode($clientSecret);

        if (!is_string($this->clientSecret) || base64_encode($this->clientSecret) !== $clientSecret) {
            throw new InvalidArgumentException('Given client secret is not a base64-decodable string.');
        }
    }

    /**
     * Signs an array by calculating a signature and setting it on the 'h' key.
     *
     * @param array $data
     * @return array
     */
    public function sign(array $data): array
    {
        ksort($data);

        $queryString = $this->buildQueryString($data);
        $data['h'] = base64_encode(hash_hmac('sha1', $queryString, $this->clientSecret, true));

        return $data;
    }

    /**
     * Verifies that the signature in the 'h' key matches the expected signature.
     *
     * @param array $data
     * @return bool
     */
    public function verifySignature(array $data): bool
    {
        $signedData = array_intersect_key($data, array_flip(self::$validResponseParams));
        ksort($signedData);

        $queryString = $this->buildQueryString($signedData);
        $signature = base64_encode(hash_hmac('sha1', $queryString, $this->clientSecret, true));

        return hash_equals($signature, $data['h']);
    }

    /**
     * @param array $query
     * @return string
     */
    private function buildQueryString(array $query): string
    {
        $queryString = '';

        foreach ($query as $key => $value) {
            $queryString .= '&' . sprintf('%s=%s', $key, $value);
        }

        return substr($queryString, 1);
    }
}
