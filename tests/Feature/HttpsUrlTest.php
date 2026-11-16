<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Com APP_URL em https, links, assets e formulários saem em https mesmo que o
 * PHP enxergue a requisição como http (nginx sem `fastcgi_param HTTPS`).
 */
class HttpsUrlTest extends TestCase
{
    use RefreshDatabase;

    private function bootWithAppUrl(string $url): void
    {
        config(['app.url' => $url]);
        (new AppServiceProvider($this->app))->boot();
    }

    public function test_https_app_url_forces_https_links_even_on_a_plain_http_request(): void
    {
        $this->bootWithAppUrl('https://kzion.exemplo');

        $html = $this->get('http://kzion.exemplo/login')->assertOk()->getContent();

        $this->assertStringContainsString('action="https://kzion.exemplo/login"', $html);
        $this->assertStringContainsString('https://kzion.exemplo/build/', $html);
        $this->assertStringNotContainsString('http://kzion.exemplo/build/', $html);
    }

    public function test_http_app_url_leaves_links_alone(): void
    {
        $this->bootWithAppUrl('http://localhost');

        $html = $this->get('http://localhost/login')->assertOk()->getContent();

        $this->assertStringContainsString('action="http://localhost/login"', $html);
    }
}
