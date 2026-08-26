<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Models\CollectionField;
use App\Models\Content;
use App\Models\Form;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Regression tests for P0-2: public form spam protection.
 *
 * - Honeypot: a non-empty hidden "website" field marks the submission as
 *   bot traffic and it is silently dropped.
 * - Turnstile: when a secret key is configured, submissions must carry a
 *   valid Cloudflare Turnstile token; network failures fail open.
 */
class FormSpamProtectionTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private Form $form;

    protected function setUp(): void
    {
        parent::setUp();

        // The challenge is opt-in: disabled by default.
        config()->set('services.turnstile.site_key', null);
        config()->set('services.turnstile.secret_key', null);

        $this->project = Project::create([
            'name' => 'Demo',
            'slug' => 'demo',
            'status' => 1,
            'public_api' => 1,
        ]);

        $collection = Collection::create([
            'name' => 'Leads',
            'slug' => 'leads',
            'project_id' => $this->project->id,
        ]);

        CollectionField::create([
            'type' => 'text',
            'label' => 'Title',
            'name' => 'title',
            'options' => '{}',
            'validations' => '{}',
            'project_id' => $this->project->id,
            'collection_id' => $collection->id,
            'order' => 1,
        ]);

        $this->form = Form::create([
            'name' => 'Contact',
            'project_id' => $this->project->id,
            'collection_id' => $collection->id,
        ]);

        $this->form->forceFill([
            'fields' => json_encode([
                [
                    'type' => 'text',
                    'name' => 'title',
                    'options' => ['repeatable' => false],
                    'validations' => [
                        'required' => ['status' => false, 'message' => null],
                        'charcount' => ['status' => false, 'type' => 'Between', 'min' => null, 'max' => null],
                        'unique' => ['status' => false, 'message' => null],
                    ],
                ],
            ]),
        ])->save();
    }

    public function test_honeypot_submission_is_silently_dropped(): void
    {
        $response = $this->post('/forms/submit/'.$this->form->uuid, [
            'data' => ['title' => 'Spam title'],
            'website' => 'http://spam.example',
        ]);

        $response->assertOk();
        $this->assertSame(0, Content::count());
    }

    public function test_legitimate_submission_is_stored(): void
    {
        $response = $this->post('/forms/submit/'.$this->form->uuid, [
            'data' => ['title' => 'Real lead'],
            'website' => '',
        ]);

        $response->assertOk();
        $this->assertSame(1, Content::count());
    }

    public function test_turnstile_challenge_is_enforced_when_configured(): void
    {
        config()->set('services.turnstile.secret_key', 'test-secret');
        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response(['success' => true], 200),
        ]);

        $response = $this->post('/forms/submit/'.$this->form->uuid, [
            'data' => ['title' => 'Real lead'],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('turnstile');
        $this->assertSame(0, Content::count());
    }

    public function test_turnstile_challenge_accepts_valid_token(): void
    {
        config()->set('services.turnstile.secret_key', 'test-secret');
        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response(['success' => true], 200),
        ]);

        $response = $this->post('/forms/submit/'.$this->form->uuid, [
            'data' => ['title' => 'Real lead'],
            'cf-turnstile-response' => 'valid-token',
        ]);

        $response->assertOk();
        $this->assertSame(1, Content::count());
    }

    public function test_turnstile_fails_open_on_network_error(): void
    {
        config()->set('services.turnstile.secret_key', 'test-secret');
        Http::fake(fn () => throw new ConnectionException('Network down'));

        $response = $this->post('/forms/submit/'.$this->form->uuid, [
            'data' => ['title' => 'Real lead'],
            'cf-turnstile-response' => 'some-token',
        ]);

        $response->assertOk();
        $this->assertSame(1, Content::count());
    }

    public function test_embedded_form_exposes_turnstile_site_key_when_configured(): void
    {
        config()->set('services.turnstile.site_key', 'test-site-key');

        $response = $this->post('/forms/'.$this->form->uuid);

        $response->assertOk();
        $response->assertJsonPath('turnstile_site_key', 'test-site-key');
    }
}
