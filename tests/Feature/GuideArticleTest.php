<?php

use App\Models\GuideArticle;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function adminUser(): User
{
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    return $admin;
}

it('never lets a non-admin manage guide articles', function () {
    $entrepreneur = User::factory()->entrepreneur()->create();
    $article = GuideArticle::factory()->create();

    $this->actingAs($entrepreneur)->get('/admin/guides')->assertForbidden();
    $this->actingAs($entrepreneur)
        ->post('/admin/guides', [
            'section' => 'glossary',
            'title' => 'Article pirate',
            'content' => '<p>Contenu</p>',
        ])
        ->assertForbidden();
    $this->actingAs($entrepreneur)
        ->delete("/admin/guides/{$article->id}")
        ->assertForbidden();

    expect(GuideArticle::where('title', 'Article pirate')->exists())->toBeFalse();
    expect(GuideArticle::find($article->id))->not->toBeNull();
});

it('lets an admin write, update and delete a guide article', function () {
    $admin = adminUser();

    $this->actingAs($admin)
        ->post('/admin/guides', [
            'section' => 'ohada-compliance',
            'title' => 'Créer une SARL en RDC',
            'description' => 'Les étapes clés.',
            'content' => '<p>Le <strong>RCCM</strong> est obligatoire.</p>',
            'order' => 2,
            'is_published' => true,
        ])
        ->assertSessionDoesntHaveErrors();

    $article = GuideArticle::where('title', 'Créer une SARL en RDC')->firstOrFail();

    expect($article->section)->toBe('ohada-compliance')
        ->and($article->content)->toContain('<strong>RCCM</strong>')
        ->and($article->is_published)->toBeTrue();

    $this->actingAs($admin)
        ->put("/admin/guides/{$article->id}", [
            'section' => 'ohada-compliance',
            'title' => 'Créer une SARL en RDC (mise à jour)',
            'description' => 'Les étapes clés.',
            'content' => '<p>Le RCCM reste obligatoire.</p>',
            'order' => 3,
            'is_published' => false,
        ])
        ->assertSessionDoesntHaveErrors();

    $article->refresh();

    expect($article->title)->toBe('Créer une SARL en RDC (mise à jour)')
        ->and($article->order)->toBe(3)
        ->and($article->is_published)->toBeFalse();

    $this->actingAs($admin)
        ->delete("/admin/guides/{$article->id}")
        ->assertSessionDoesntHaveErrors();

    expect(GuideArticle::find($article->id))->toBeNull();
});

it('validates the guide article fields', function () {
    $admin = adminUser();

    $this->actingAs($admin)
        ->post('/admin/guides', [
            'section' => 'not-a-real-section',
            'title' => '',
            'content' => '',
        ])
        ->assertSessionHasErrors(['section', 'title', 'content']);
});

it('only exposes published guide articles on the public guide', function () {
    GuideArticle::factory()->create([
        'section' => 'glossary',
        'title' => 'Article publié',
        'content' => '<p>Visible.</p>',
        'order' => 1,
    ]);
    GuideArticle::factory()->unpublished()->create([
        'section' => 'glossary',
        'title' => 'Article brouillon',
        'content' => '<p>Caché.</p>',
        'order' => 2,
    ]);

    $this->get('/guides')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Public/Guide')
            ->has('guideArticles', 1)
            ->where('guideArticles.0.title', 'Article publié')
            ->where('guideArticles.0.section', 'glossary')
        );
});
