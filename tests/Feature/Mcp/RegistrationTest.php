<?php

declare(strict_types=1);

namespace Ruvelo\Wiki\Tests\Feature\Mcp;

use Laravel\Mcp\Server\Registrar;
use Ruvelo\Wiki\Wiki;

class RegistrationTest extends McpTestCase
{
    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function rpc(string $method, array $params = []): array
    {
        return ['jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => $params];
    }

    public function test_the_http_endpoint_needs_the_configured_guard(): void
    {
        $this->postJson('/mcp/wiki', $this->rpc('tools/list'))->assertUnauthorized();
    }

    public function test_signed_in_agents_can_list_and_call_tools_over_http(): void
    {
        Wiki::create('Refunds', 'Within 30 days.');
        $this->actingAs($this->user());

        $tools = $this->postJson('/mcp/wiki', $this->rpc('tools/list'))->assertOk()->json('result.tools.*.name');
        $this->assertSame(['search_pages', 'read_page', 'list_pages', 'recent_changes'], $tools);

        $this->postJson('/mcp/wiki', $this->rpc('tools/call', ['name' => 'read_page', 'arguments' => ['page' => 'refunds']]))
            ->assertOk()
            ->assertJsonPath('result.isError', false)
            ->assertJsonPath('result.structuredContent.body', 'Within 30 days.');

        $this->postJson('/mcp/wiki', $this->rpc('resources/templates/list'))
            ->assertOk()
            ->assertJsonPath('result.resourceTemplates.0.uriTemplate', 'wiki://pages/{slug}');

        $this->postJson('/mcp/wiki', $this->rpc('resources/read', ['uri' => 'wiki://pages/refunds']))
            ->assertOk()
            ->assertJsonPath('result.contents.0.text', "# Refunds\n\nWithin 30 days.")
            ->assertJsonPath('result.contents.0.mimeType', 'text/markdown');
    }

    public function test_the_route_is_named_and_the_local_server_is_registered(): void
    {
        $this->assertSame(url('mcp/wiki'), route('wiki.mcp'));
        $this->assertNotNull($this->app->make(Registrar::class)->getLocalServer('wiki'));
    }
}
