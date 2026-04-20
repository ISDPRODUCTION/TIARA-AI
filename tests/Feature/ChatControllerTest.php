<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatControllerTest extends TestCase
{
    use RefreshDatabase;

    // ????????? Homepage Tests ?????????????????????????????????????????????????????????????????????????????????????????????????????????

    public function test_homepage_returns_200(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_homepage_contains_nexus_ai_title(): void
    {
        $response = $this->get('/');

        $response->assertSee('Tiara AI');
    }

    public function test_homepage_has_csrf_meta_tag(): void
    {
        $response = $this->get('/');

        $response->assertSee('csrf-token', false);
    }

    public function test_homepage_has_chat_input(): void
    {
        $response = $this->get('/');

        $response->assertSee('id="chat-input"', false);
    }

    public function test_homepage_has_send_button(): void
    {
        $response = $this->get('/');

        $response->assertSee('id="send-btn"', false);
    }

    public function test_homepage_has_suggestion_cards(): void
    {
        $response = $this->get('/');

        $response->assertSee('suggestion-card', false);
    }

    public function test_homepage_displays_ai_provider_status(): void
    {
        config(['ai.provider' => 'mistral']);

        $response = $this->get('/');

        $response->assertSee('Mistral AI');
    }

    public function test_homepage_displays_gemini_provider_when_configured(): void
    {
        config(['ai.provider' => 'gemini']);

        $response = $this->get('/');

        $response->assertSee('Gemini AI');
    }

    public function test_homepage_has_noscript_fallback(): void
    {
        $response = $this->get('/');

        $response->assertSee('<noscript>', false);
    }

    // ????????? Chat API Validation Tests ????????????????????????????????????????????????????????????????????????

    public function test_chat_requires_message(): void
    {
        $response = $this->postJson('/api/chat', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['message']);
    }

    public function test_chat_rejects_empty_message(): void
    {
        $response = $this->postJson('/api/chat', [
            'message' => '',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['message']);
    }

    public function test_chat_rejects_message_exceeding_max_length(): void
    {
        $response = $this->postJson('/api/chat', [
            'message' => str_repeat('a', 4001),
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['message']);
    }

    public function test_chat_accepts_valid_message_within_limit(): void
    {
        // We'll mock the HTTP call to avoid needing real API keys
        Http::fake([
            'api.mistral.ai/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Hello from Mistral!']]
                ]
            ], 200),
        ]);

        config(['ai.provider' => 'mistral', 'ai.mistral.api_key' => 'test-key']);

        $response = $this->postJson('/api/chat', [
            'message' => str_repeat('a', 4000),
        ]);

        $response->assertStatus(200);
    }

    public function test_chat_validates_history_entries(): void
    {
        $response = $this->postJson('/api/chat', [
            'message' => 'Hello',
            'history' => [
                [
                    'role' => 'invalid_role',
                    'parts' => [['text' => 'test']],
                ]
            ],
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['history.0.role']);
    }

    public function test_chat_validates_history_parts_required(): void
    {
        $response = $this->postJson('/api/chat', [
            'message' => 'Hello',
            'history' => [
                [
                    'role' => 'user',
                    // missing parts
                ]
            ],
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['history.0.parts']);
    }

    public function test_chat_rejects_history_exceeding_max_entries(): void
    {
        $history = [];
        for ($i = 0; $i < 41; $i++) {
            $history[] = [
                'role' => 'user',
                'parts' => [['text' => 'test']],
            ];
        }

        $response = $this->postJson('/api/chat', [
            'message' => 'Hello',
            'history' => $history,
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['history']);
    }

    // ????????? Mistral Provider Tests ?????????????????????????????????????????????????????????????????????????????????

    public function test_mistral_returns_successful_response(): void
    {
        Http::fake([
            'api.mistral.ai/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Halo! Saya Tiara AI ????']]
                ]
            ], 200),
        ]);

        config(['ai.provider' => 'mistral', 'ai.mistral.api_key' => 'test-key']);

        $response = $this->postJson('/api/chat', [
            'message' => 'Halo!',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['reply'])
                 ->assertJson(['reply' => 'Halo! Saya Tiara AI ????']);
    }

    public function test_mistral_sends_conversation_history(): void
    {
        Http::fake([
            'api.mistral.ai/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Response with context']]
                ]
            ], 200),
        ]);

        config(['ai.provider' => 'mistral', 'ai.mistral.api_key' => 'test-key']);

        $response = $this->postJson('/api/chat', [
            'message' => 'Follow up question',
            'history' => [
                [
                    'role' => 'user',
                    'parts' => [['text' => 'Previous question']],
                ],
                [
                    'role' => 'model',
                    'parts' => [['text' => 'Previous answer']],
                ],
            ],
        ]);

        $response->assertStatus(200)
                 ->assertJson(['reply' => 'Response with context']);

        // Verify the request was made with correct format
        Http::assertSent(function ($request) {
            $body = $request->data();
            // Should have system + 2 history + 1 current = 4 messages
            return count($body['messages']) === 4
                && $body['messages'][0]['role'] === 'system'
                && $body['messages'][1]['role'] === 'user'
                && $body['messages'][2]['role'] === 'assistant' // model ??? assistant
                && $body['messages'][3]['role'] === 'user';
        });
    }

    public function test_mistral_returns_error_without_api_key(): void
    {
        config(['ai.provider' => 'mistral', 'ai.mistral.api_key' => '']);

        $response = $this->postJson('/api/chat', [
            'message' => 'Hello',
        ]);

        $response->assertStatus(500)
                 ->assertJsonStructure(['error']);
    }

    public function test_mistral_handles_api_failure(): void
    {
        Http::fake([
            'api.mistral.ai/*' => Http::response([
                'error' => ['message' => 'Internal Server Error']
            ], 500),
        ]);

        config(['ai.provider' => 'mistral', 'ai.mistral.api_key' => 'test-key']);

        $response = $this->postJson('/api/chat', [
            'message' => 'Hello',
        ]);

        $response->assertStatus(500)
                 ->assertJsonStructure(['error']);
    }

    public function test_mistral_handles_empty_choices(): void
    {
        Http::fake([
            'api.mistral.ai/*' => Http::response([
                'choices' => []
            ], 200),
        ]);

        config(['ai.provider' => 'mistral', 'ai.mistral.api_key' => 'test-key']);

        $response = $this->postJson('/api/chat', [
            'message' => 'Hello',
        ]);

        $response->assertStatus(422)
                 ->assertJsonStructure(['error']);
    }

    // ????????? Gemini Provider Tests ????????????????????????????????????????????????????????????????????????????????????

    public function test_gemini_returns_successful_response(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => 'Hello from Gemini!']]]]
                ]
            ], 200),
        ]);

        config(['ai.provider' => 'gemini', 'ai.gemini.api_key' => 'test-gemini-key']);

        $response = $this->postJson('/api/chat', [
            'message' => 'Hello',
        ]);

        $response->assertStatus(200)
                 ->assertJson(['reply' => 'Hello from Gemini!']);
    }

    public function test_gemini_returns_error_without_api_key(): void
    {
        config(['ai.provider' => 'gemini', 'ai.gemini.api_key' => '']);

        $response = $this->postJson('/api/chat', [
            'message' => 'Hello',
        ]);

        $response->assertStatus(500)
                 ->assertJsonStructure(['error']);
    }

    public function test_gemini_handles_api_failure(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'error' => ['message' => 'Forbidden']
            ], 403),
        ]);

        config(['ai.provider' => 'gemini', 'ai.gemini.api_key' => 'test-gemini-key']);

        $response = $this->postJson('/api/chat', [
            'message' => 'Hello',
        ]);

        $response->assertStatus(403)
                 ->assertJsonStructure(['error']);
    }

    public function test_gemini_handles_empty_candidates(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => []
            ], 200),
        ]);

        config(['ai.provider' => 'gemini', 'ai.gemini.api_key' => 'test-gemini-key']);

        $response = $this->postJson('/api/chat', [
            'message' => 'Hello',
        ]);

        $response->assertStatus(422)
                 ->assertJsonStructure(['error']);
    }

    // ????????? Connection Error Tests ?????????????????????????????????????????????????????????????????????????????????

    public function test_handles_connection_exception_gracefully(): void
    {
        Http::fake([
            'api.mistral.ai/*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection refused');
            },
        ]);

        config(['ai.provider' => 'mistral', 'ai.mistral.api_key' => 'test-key']);

        $response = $this->postJson('/api/chat', [
            'message' => 'Hello',
        ]);

        $response->assertStatus(503)
                 ->assertJsonStructure(['error']);
    }

    // ????????? Route Tests ??????????????????????????????????????????????????????????????????????????????????????????????????????????????????

    public function test_chat_endpoint_only_accepts_post(): void
    {
        $response = $this->getJson('/api/chat');

        $response->assertStatus(405); // Method Not Allowed
    }

    public function test_chat_endpoint_is_named(): void
    {
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('chat.send'));
    }

    // ????????? Config Tests ???????????????????????????????????????????????????????????????????????????????????????????????????????????????

    public function test_ai_config_has_required_keys(): void
    {
        $this->assertNotNull(config('ai.provider'));
        $this->assertNotNull(config('ai.system_prompt'));
        $this->assertNotNull(config('ai.temperature'));
        $this->assertNotNull(config('ai.max_tokens'));
        $this->assertNotNull(config('ai.mistral'));
        $this->assertNotNull(config('ai.gemini'));
    }

    public function test_ai_config_default_provider_is_mistral(): void
    {
        $this->assertEquals('mistral', config('ai.provider'));
    }

    public function test_ai_config_has_rate_limit(): void
    {
        $this->assertNotNull(config('ai.rate_limit'));
        $this->assertGreaterThan(0, config('ai.rate_limit'));
    }
}
