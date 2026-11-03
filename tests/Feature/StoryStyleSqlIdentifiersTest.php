<?php

use App\Support\StoryStyle;

it('allows PostgreSQL lock identifiers without allowing banned promotional language', function () {
    $story = cleanStory(['type' => 'article', 'project_key' => null,
        'en' => ['content' => '<p>The session releases its lock.</p><pre><code class="language-sql">SELECT pg_advisory_unlock(1, 42);</code></pre>'],
        'tr' => ['content' => '<p>Oturum kilidi bırakır.</p>'],
    ]);
    expect(StoryStyle::violations($story))->not->toContain('en: banned word “unlock”');
    $story['en']['content'] .= '<p>Unlock new possibilities.</p>';
    expect(StoryStyle::violations($story))->toContain('en: banned word “unlock”');
});
