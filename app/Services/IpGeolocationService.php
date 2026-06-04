<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class IpGeolocationService
{
    private const CACHE_TTL = 604800; // 7 days

    private const FIELDS = 'status,message,country,countryCode,region,regionName,city,zip,lat,lon,timezone,isp,org,as,query,mobile,proxy,hosting';

    public function lookup(?string $ip): ?array
    {
        if (!$ip) {
            return null;
        }

        if ($this->isPrivateIp($ip)) {
            return $this->privateIpResult($ip);
        }

        return Cache::remember("ip_geo:{$ip}", self::CACHE_TTL, function () use ($ip) {
            $results = $this->fetchBatch([$ip]);
            return $results[$ip] ?? null;
        });
    }

    /**
     * @param  array<int, string>  $ips
     * @return array<string, array|null>
     */
    public function lookupMany(array $ips): array
    {
        $ips = array_values(array_unique(array_filter($ips)));
        $results = [];

        $toFetch = [];
        foreach ($ips as $ip) {
            if ($this->isPrivateIp($ip)) {
                $results[$ip] = $this->privateIpResult($ip);
                continue;
            }

            $cached = Cache::get("ip_geo:{$ip}");
            if ($cached !== null) {
                $results[$ip] = $cached;
            } else {
                $toFetch[] = $ip;
            }
        }

        foreach (array_chunk($toFetch, 100) as $chunk) {
            $fetched = $this->fetchBatch($chunk);
            foreach ($fetched as $ip => $data) {
                Cache::put("ip_geo:{$ip}", $data, self::CACHE_TTL);
                $results[$ip] = $data;
            }
        }

        return $results;
    }

    /**
     * @param  array<int, string>  $ips
     * @return array<string, array|null>
     */
    private function fetchBatch(array $ips): array
    {
        if (empty($ips)) {
            return [];
        }

        try {
            if (count($ips) === 1) {
                $ip = $ips[0];
                $response = Http::timeout(4)->get("http://ip-api.com/json/{$ip}", [
                    'fields' => self::FIELDS,
                ]);

                if ($response->successful() && $response->json('status') === 'success') {
                    return [$ip => $this->normalize($response->json())];
                }

                return [$ip => null];
            }

            $response = Http::timeout(8)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post('http://ip-api.com/batch?fields=' . self::FIELDS, $ips);

            if (!$response->successful()) {
                return array_fill_keys($ips, null);
            }

            $payload = $response->json();
            $mapped = [];

            foreach ($payload as $index => $item) {
                $ip = $ips[$index] ?? ($item['query'] ?? null);
                if (!$ip) {
                    continue;
                }
                $mapped[$ip] = ($item['status'] ?? '') === 'success'
                    ? $this->normalize($item)
                    : null;
            }

            return $mapped;
        } catch (\Throwable $e) {
            return array_fill_keys($ips, null);
        }
    }

    private function normalize(array $data): array
    {
        return [
            'ip' => $data['query'] ?? null,
            'country' => $data['country'] ?? null,
            'country_code' => $data['countryCode'] ?? null,
            'region' => $data['regionName'] ?? null,
            'city' => $data['city'] ?? null,
            'zip' => $data['zip'] ?? null,
            'latitude' => $data['lat'] ?? null,
            'longitude' => $data['lon'] ?? null,
            'timezone' => $data['timezone'] ?? null,
            'isp' => $data['isp'] ?? null,
            'org' => $data['org'] ?? null,
            'as' => $data['as'] ?? null,
            'is_mobile' => (bool) ($data['mobile'] ?? false),
            'is_proxy' => (bool) ($data['proxy'] ?? false),
            'is_hosting' => (bool) ($data['hosting'] ?? false),
            'location_label' => $this->buildLocationLabel($data),
        ];
    }

    private function buildLocationLabel(array $data): string
    {
        $parts = array_filter([
            $data['city'] ?? null,
            $data['regionName'] ?? null,
            $data['country'] ?? null,
        ]);

        return $parts ? implode('، ', $parts) : 'نامشخص';
    }

    private function isPrivateIp(string $ip): bool
    {
        if ($ip === '127.0.0.1' || $ip === '::1' || str_starts_with($ip, 'fe80:')) {
            return true;
        }

        return !filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }

    private function privateIpResult(?string $ip): array
    {
        return [
            'ip' => $ip,
            'country' => 'شبکه محلی',
            'country_code' => 'LOCAL',
            'region' => null,
            'city' => null,
            'zip' => null,
            'latitude' => null,
            'longitude' => null,
            'timezone' => null,
            'isp' => 'Local Network',
            'org' => null,
            'as' => null,
            'is_mobile' => false,
            'is_proxy' => false,
            'is_hosting' => false,
            'location_label' => 'شبکه محلی / localhost',
        ];
    }
}
