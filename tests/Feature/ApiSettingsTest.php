<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\Serp\SerpProviderFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ApiSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    private function values(array $overrides = []): array
    {
        return array_replace(['provider' => 'serper', 'api_key' => 'secret-test-key',
            'country' => 'us', 'language' => 'hi', 'results' => 20], $overrides);
    }

    public function test_only_admin_can_view_save_or_test_settings(): void
    {
        $before = Setting::orderBy('key')->pluck('value', 'key')->all();
        Http::fake();
        foreach (['manager', 'viewer'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role, 'is_active' => true]));
            $this->get(route('settings.api.index'))->assertForbidden();
            $this->put(route('settings.api.update'), $this->values())->assertForbidden();
            $this->postJson(route('settings.api.test'), $this->values())->assertForbidden();
        }
        Http::assertNothingSent();
        $this->assertSame($before, Setting::orderBy('key')->pluck('value', 'key')->all());
    }

    public function test_admin_can_save_all_five_settings_and_keep_existing_key(): void
    {
        $this->actingAs($this->admin())->put(route('settings.api.update'), $this->values())
            ->assertSessionHasNoErrors()->assertSessionHas('success');
        foreach ($this->values() as $field => $value) {
            $this->assertSame((string) $value, Setting::get('serp_'.$field));
        }
        $this->put(route('settings.api.update'), $this->values(['api_key' => '']))->assertSessionHasNoErrors();
        $this->assertSame('secret-test-key', Setting::get('serp_api_key'));
        $before = Setting::orderBy('key')->pluck('value', 'key')->all();
        Http::fake();
        $this->get(route('settings.api.index'))->assertOk()->assertSee('SERP_LANGUAGE')
            ->assertSee('SERP_RESULTS')->assertDontSee('secret-test-key');
        Http::assertNothingSent();
    }

    public function test_testing_uses_unsaved_values_and_returns_api_error(): void
    {
        $before = Setting::orderBy('key')->pluck('value', 'key')->all();
        Http::fake(['google.serper.dev/search' => Http::response(['message' => 'Invalid API key'], 401)]);
        $this->actingAs($this->admin())->postJson(route('settings.api.test'), $this->values())
            ->assertOk()->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Serper.dev (HTTP 401): Invalid API key');
        Http::assertSent(fn ($request) => $request->hasHeader('X-API-KEY', 'secret-test-key')
            && $request['gl'] === 'us' && $request['hl'] === 'hi' && $request['num'] === 20);
        $this->assertSame($before, Setting::orderBy('key')->pluck('value', 'key')->all());
    }

    public function test_success_and_connection_failures_have_clear_messages(): void
    {
        $this->actingAs($this->admin());
        Http::fake(['google.serper.dev/search' => Http::response(['organic' => []])]);
        $this->postJson(route('settings.api.test'), $this->values())->assertJsonPath('success', true);
        Http::fake(['google.serper.dev/search' => Http::failedConnection()]);
        $this->postJson(route('settings.api.test'), $this->values())
            ->assertJsonPath('success', false)->assertJsonPath('message', 'Unable to reach the API. The connection failed or timed out. Please try again.');
    }

    public function test_invalid_settings_are_rejected_without_saving_or_calling_api(): void
    {
        $before = Setting::orderBy('key')->pluck('value', 'key')->all();
        Http::fake();
        $this->actingAs($this->admin())->postJson(route('settings.api.test'),
            $this->values(['provider' => 'unknown', 'country' => 'invalid', 'language' => '!', 'results' => 101]))
            ->assertUnprocessable()->assertJsonValidationErrors(['provider', 'country', 'language', 'results']);
        Http::assertNothingSent();
        $this->assertSame($before, Setting::orderBy('key')->pluck('value', 'key')->all());
    }

    public function test_dataforseo_application_errors_are_detected_even_with_http_200(): void
    {
        Http::fake(['api.dataforseo.com/*' => Http::response(['status_code' => 40100, 'status_message' => 'Authentication failed.'])]);
        $this->actingAs($this->admin())->postJson(route('settings.api.test'),
            $this->values(['provider' => 'dataforseo', 'serp_login' => 'admin@example.com']))
            ->assertJsonPath('success', false)->assertJsonPath('message', 'DataForSEO (HTTP 200): Authentication failed.');
    }

    public function test_rank_checks_use_saved_language_and_result_limit(): void
    {
        foreach ($this->values(['results' => 1]) as $field => $value) Setting::set('serp_'.$field, (string) $value);
        Http::fake(['google.serper.dev/search' => Http::response(['organic' => [
            ['link' => 'https://other.test'], ['link' => 'https://example.com'],
        ]])]);
        $this->assertNull(SerpProviderFactory::make()->getRank('test', 'https://example.com', 'us'));
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request['hl'] === 'hi');
    }
}
