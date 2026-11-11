<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $fillable = ['key', 'name', 'subject', 'body', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function render(array $data = []): array
    {
        $replace = [];
        foreach ($data as $key => $value) {
            $replace['{{'.$key.'}}'] = (string) $value;
        }

        return [
            'subject' => strtr($this->subject, $replace),
            'body' => strtr($this->body, $replace),
        ];
    }
}
