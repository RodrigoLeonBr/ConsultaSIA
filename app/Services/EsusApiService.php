<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Cliente HTTP para a API e-SUS (simpa). Faz login JWT e consome os
 * endpoints de produção SIGTAP. Servidor externo, sem CORS — só cURL/HTTP.
 */
class EsusApiService
{
    private ?string $token = null;

    public function __construct(
        private readonly string $baseUrl,
        private readonly ?string $username,
        private readonly ?string $password,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            rtrim((string) config('services.esus.base_url'), '/'),
            config('services.esus.username'),
            config('services.esus.password'),
        );
    }

    /**
     * @return array<int, string> Competências disponíveis (YYYY-MM), mais recentes primeiro.
     */
    public function getCompetencias(): array
    {
        $response = $this->authorized()->get('/api/cadastros/procedimentos-sigtap/competencias');

        if ($response->failed()) {
            throw new RuntimeException("Falha ao listar competências (HTTP {$response->status()}).");
        }

        return $response->json() ?? [];
    }

    /**
     * @return array<int, array<string, mixed>> Linhas de produção da competência.
     */
    public function getProducao(string $competencia): array
    {
        $response = $this->authorized()
            ->get('/api/cadastros/procedimentos-sigtap/producao', ['competencia' => $competencia]);

        if ($response->status() === 400) {
            throw new RuntimeException('Competência inválida — use o formato YYYY-MM.');
        }

        if ($response->failed()) {
            throw new RuntimeException("Falha ao buscar produção (HTTP {$response->status()}).");
        }

        return $response->json() ?? [];
    }

    private function login(): string
    {
        if (! $this->username || ! $this->password) {
            throw new RuntimeException('Credenciais da API e-SUS não configuradas (ESUS_API_USER/ESUS_API_PASSWORD).');
        }

        $response = Http::baseUrl($this->baseUrl)
            ->acceptJson()
            ->post('/auth/login', [
                'username' => $this->username,
                'senha' => $this->password,
            ]);

        if ($response->failed()) {
            throw new RuntimeException("Login na API e-SUS falhou (HTTP {$response->status()}).");
        }

        $token = $response->json('token');

        if (! $token) {
            throw new RuntimeException('Login na API e-SUS não retornou token.');
        }

        return $this->token = $token;
    }

    private function authorized(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->acceptJson()
            ->withToken($this->token ?? $this->login());
    }
}
