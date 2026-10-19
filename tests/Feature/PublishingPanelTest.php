<?php

use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Models\Post;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('allows an administrator to create edit publish and unpublish through the panel', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));
    Livewire::test(CreatePost::class)->fillForm([
        'title' => 'Panel draft', 'slug' => 'panel-draft', 'type' => 'article',
        'excerpt' => '', 'content' => '<p>Draft content</p>', 'published_at' => null,
        'translations' => ['en' => ['title' => 'English draft', 'excerpt' => '', 'content' => '']],
    ])->call('create')->assertHasNoFormErrors();
    $post = Post::where('slug', 'panel-draft')->firstOrFail();
    expect($post->published_at)->toBeNull();
    Livewire::test(EditPost::class, ['record' => $post->id])->fillForm([
        'title' => 'Edited title', 'excerpt' => 'Ready to publish', 'content' => '<p>Ready content.</p>', 'published_at' => now(),
    ])->call('save')->assertHasNoFormErrors();
    expect($post->fresh()->published_at)->not->toBeNull();
    Livewire::test(EditPost::class, ['record' => $post->id])->callAction('unpublish')->assertHasNoActionErrors();
    expect($post->fresh()->published_at)->toBeNull();
});

it('rejects incomplete publication fields and image uploads outside the accepted mime types', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));
    Livewire::test(CreatePost::class)->fillForm([
        'title' => 'Incomplete', 'slug' => 'incomplete', 'type' => 'article', 'excerpt' => '', 'content' => '', 'published_at' => now(),
    ])->call('create')->assertHasFormErrors(['excerpt' => 'required', 'content']);
    Livewire::test(CreatePost::class)->fillForm([
        'title' => 'Unsafe image', 'slug' => 'unsafe-image', 'type' => 'article',
        'cover_image' => UploadedFile::fake()->create('payload.svg', 12, 'image/svg+xml'),
    ])->call('create')->assertHasFormErrors(['cover_image']);
});

it('does not allow a non administrator to mount the editor', function () {
    $this->actingAs(User::factory()->create());
    Livewire::test(CreatePost::class)->assertForbidden();
});

it('soft deletes and restores through the administrator editor', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));
    $post = Post::create(['title' => 'Draft', 'slug' => 'deletable-draft', 'content' => '']);
    Livewire::test(EditPost::class, ['record' => $post->id])->callAction('delete')->assertHasNoActionErrors();
    expect(Post::find($post->id))->toBeNull();
    Livewire::test(EditPost::class, ['record' => $post->id])->callAction('restore')->assertHasNoActionErrors();
    expect(Post::find($post->id))->not->toBeNull();
});
