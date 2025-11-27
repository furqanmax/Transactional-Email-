<?php

namespace Furqanmax\TransactionalEmail;

/**
 * TransactionalEmailClient
 *
 * Lightweight HTTP client to interact with a transactional email API.
 * Works in Laravel or plain PHP via cURL.
 */
class TransactionalEmailClient
{
    protected string $baseUrl;

    /** @var array{login?:string,template?:string,direct?:string} */
    protected array $endpoints;

    /** @var array{timeout?:int,verify_ssl?:bool} */
    protected array $httpConfig;

    /** @var array{email?:string,password?:string}|null */
    protected ?array $credentials;

    /** @var string|null Application UUID (APP_ID) used as default for template sends */
    protected ?string $appId;

    protected ?string $token;

    /**
     * @param string $baseUrl Base API URL, e.g., http://127.0.0.1:8000/api
     * @param array{login?:string,template?:string,direct?:string} $endpoints
     * @param array{timeout?:int,verify_ssl?:bool} $httpConfig
     * @param array{email?:string,password?:string}|null $credentials
     * @param string|null $appId Application UUID to use as default for template sends
     */
    public function __construct(
        string $baseUrl,
        array $endpoints = [],
        array $httpConfig = [],
        ?array $credentials = null,
        ?string $appId = null,
        ?string $token = null,
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->endpoints = array_merge([
            'login' => '/login',
            'template' => '/gettransactionalApi',
            'direct' => '/makeTransactionalApi',
        ], $endpoints);
        $this->httpConfig = array_merge([
            'timeout' => 10,
            'verify_ssl' => false, // default for local dev
        ], $httpConfig);
        $this->credentials = $credentials;
        $this->appId = $appId;
        $this->token = $token;
    }

    public function setToken(?string $token): self
    {
        $this->token = $token;
        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    /**
     * Resolve a token to use for the request. If no explicit token is
     * provided and no stored token exists, attempt auto-login when credentials
     * are configured.
     */
    protected function resolveToken(?string $overrideToken): ?string
    {
        if ($overrideToken) {
            return $overrideToken;
        }
        if ($this->token) {
            return $this->token;
        }
        if ($this->credentials && ($this->credentials['email'] ?? null) && ($this->credentials['password'] ?? null)) {
            return $this->login();
        }
        return null;
    }

    /**
     * Login and store the token.
     *
     * @return string token
     */
    public function login(?string $email = null, ?string $password = null): string
    {
        $email = $email ?? ($this->credentials['email'] ?? null);
        $password = $password ?? ($this->credentials['password'] ?? null);

        if (!$email || !$password) {
            throw new \InvalidArgumentException('Login requires email and password.');
        }

        $payload = [
            'email' => $email,
            'password' => $password,
        ];

        $resp = $this->post($this->endpoints['login'], $payload, null);
        if (!isset($resp['token'])) {
            throw new \RuntimeException('Login failed: token not found in response.');
        }
        $this->token = (string)$resp['token'];
        return $this->token;
    }

    /**
     * Send using a template.
     * Mirrors the provided example payload structure.
     *
     * @param array|string $templateVariables Array will be json_encoded; string will be sent as-is
     */
    public function sendTemplateEmail(
        string $from,
        string $to,
        string $templateKey,
        array|string $templateVariables,
        ?string $subject = null,
        ?string $preheaderText = null,
        ?string $uuid = null,
        ?string $token = null
    ): array {
        // Use explicit uuid or fall back to configured APP_ID
        $uuid = $uuid ?? $this->appId;
        if (!$uuid) {
            throw new \InvalidArgumentException('Template sends require a UUID. Configure APP_ID in .env or pass $uuid explicitly.');
        }

        $payload = [
            'from' => $from,
            'to' => $to,
            'template_key' => $templateKey,
            'template_variables' => is_array($templateVariables)
                ? json_encode($templateVariables)
                : $templateVariables,
            'uuid' => $uuid,
        ];

        if ($preheaderText !== null) {
            $payload['preheader_text'] = $preheaderText;
        }
        if ($subject !== null) {
            $payload['subject'] = $subject;
        }

        $token = $this->resolveToken($token);
        return $this->post($this->endpoints['template'], $payload, $token);
    }

    /**
     * Send a direct email without a pre-defined template.
     */
    public function sendDirectEmail(
        string $from,
        string $to,
        string $subject,
        ?string $preheader = null,
        string $body = '',
        ?string $htmlBody = null,
        ?string $token = null
    ): array {
        $payload = [
            'from' => $from,
            'to' => $to,
            'subject' => $subject,
            'body' => $body,
        ];
        if ($preheader !== null) {
            $payload['preheader'] = $preheader;
        }
        if ($htmlBody !== null) {
            $payload['html_body'] = $htmlBody;
        }

        $token = $this->resolveToken($token);
        return $this->post($this->endpoints['direct'], $payload, $token);
    }

    /**
     * Low-level POST helper.
     * @param string $pathOrUrl Either full URL or relative path starting with '/'
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    protected function post(string $pathOrUrl, array $payload, ?string $token = null): array
    {
        $url = $this->buildUrl($pathOrUrl);

        $headers = [
            'Content-Type: application/json',
        ];
        if ($token) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        $ch = curl_init();
        if ($ch === false) {
            throw new \RuntimeException('Failed to initialize cURL');
        }

        $timeout = (int)($this->httpConfig['timeout'] ?? 10);
        $verify = (bool)($this->httpConfig['verify_ssl'] ?? false);

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => $verify,
        ]);

        $raw = curl_exec($ch);
        if ($raw === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('API Error: ' . $err);
        }

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE) ?: 0;
        curl_close($ch);

        $decoded = json_decode($raw, true);
        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON response (HTTP ' . $status . '): ' . $raw);
        }

        if ($status >= 400) {
            $message = is_array($decoded) && isset($decoded['message']) ? $decoded['message'] : 'HTTP ' . $status;
            throw new \RuntimeException('API request failed: ' . $message);
        }

        return is_array($decoded) ? $decoded : ['raw' => $raw, 'status' => $status];
    }

    protected function buildUrl(string $pathOrUrl): string
    {
        if (str_starts_with($pathOrUrl, 'http://') || str_starts_with($pathOrUrl, 'https://')) {
            return $pathOrUrl;
        }
        if (!str_starts_with($pathOrUrl, '/')) {
            $pathOrUrl = '/' . $pathOrUrl;
        }
        return $this->baseUrl . $pathOrUrl;
    }
}
