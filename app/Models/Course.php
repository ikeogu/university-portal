<?php

namespace App\Models;

use App\Enums\Semester;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'title', 'credit_units', 'semester', 'level', 'is_active'])]
class Course extends Model
{
    use HasUlids;

    protected function casts(): array
    {
        return [
            'semester' => Semester::class,
            'is_active' => 'boolean',
        ];
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(CourseAllocation::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(Score::class);
    }
}
