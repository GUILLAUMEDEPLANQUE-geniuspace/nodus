<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Article extends Model
{
    public $incrementing = false;
    public $timestamps = false;
    protected $keyType = 'string';
    protected $guarded = [];
    protected $casts = [
        'published_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function tocItems(): array
    {
        return array_values(array_filter(preg_split('/\n+/', $this->toc ?? '')));
    }

    public function tails(): array
    {
        $out = [];
        foreach (preg_split('/\n+/', $this->longtail ?? '') as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $p = array_map('trim', explode('|', $line, 2));
            $out[] = ['q' => $p[0], 'note' => $p[1] ?? ''];
        }
        return $out;
    }

    public function faqs(): array
    {
        $out = [];
        foreach (preg_split('/\n+/', $this->faq ?? '') as $line) {
            $p = array_map('trim', explode('||', $line, 2));
            if (count($p) === 2 && $p[0] !== '') {
                $out[] = ['q' => $p[0], 'a' => $p[1]];
            }
        }
        return $out;
    }

    public function urlSlug(): string
    {
        return $this->slug ?: Str::slug($this->title);
    }
}
