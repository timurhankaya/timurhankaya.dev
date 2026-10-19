<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A message sent through the contact form, read in the admin panel. */
class ContactRequest extends Model
{
    public const TOPICS = ['project', 'partnership', 'job', 'other'];

    public const SERVICES = ['saas', 'web', 'ai', 'mobile', 'erp', 'partnership'];

    public const TIMELINES = ['asap', '1-3-months', 'later', 'flexible'];

    protected $fillable = ['topic', 'name', 'email', 'company', 'services', 'budget', 'timeline', 'message', 'locale', 'ip_hash'];

    protected function casts(): array
    {
        return ['services' => 'array', 'read_at' => 'datetime'];
    }
}
