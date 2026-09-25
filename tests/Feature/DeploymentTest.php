<?php

namespace Tests\Feature;

use Tests\TestCase;

class DeploymentTest extends TestCase
{
    public function test_browser_api_urls_remain_same_origin_without_proxy_configuration(): void
    {
        config(['trustedproxy.proxies' => []]);

        $this->get('/')
            ->assertOk()
            ->assertSee('window.KITA_DATA_URL = "/api/kita-data"', false)
            ->assertSee('loginUrl: "/login"', false)
            ->assertSee('transactionUrl: "/api/transactions"', false)
            ->assertSee('paymongoUrl: "/api/payments/paymongo/checkout"', false)
            ->assertSee('logoutUrl: "/logout"', false);
    }

    public function test_health_endpoint_does_not_require_a_database(): void
    {
        config(['database.default' => 'unavailable']);

        $this->get('/up')->assertOk();
    }

    public function test_render_forwarded_https_is_used_for_asset_urls(): void
    {
        config(['trustedproxy.proxies' => '*']);

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])
            ->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->get('/')
            ->assertOk()
            ->assertSee('https://localhost/styles.css', false);
    }

    public function test_local_requests_do_not_trust_forwarded_https_by_default(): void
    {
        config(['trustedproxy.proxies' => []]);

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])
            ->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->get('/')
            ->assertOk()
            ->assertSee('http://localhost/styles.css', false);
    }
}
