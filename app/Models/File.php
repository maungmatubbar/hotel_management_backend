<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

#[Fillable(['fileable_id', 'fileable_type', 'category', 'tenant_id', 'disk', 'path', 'original_name', 'mime_type', 'size'])]
class File extends Model
{
    use CentralConnection, SoftDeletes;

    public function fileable(): MorphTo
    {
        return $this->morphTo();
    }
}
