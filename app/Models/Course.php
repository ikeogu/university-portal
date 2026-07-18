<?php

namespace App\Models;

use App\Enums\CourseCategory;
use App\Enums\Semester;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code', 'title', 'credit_units', 'semester', 'level',
    'category', 'elective_group', 'choose_count', 'is_active',
])]
class Course extends Model
{
    use HasUlids;

    protected $attributes = [
        'category' => 'core',
    ];

    protected function casts(): array
    {
        return [
            'semester' => Semester::class,
            'category' => CourseCategory::class,
            'is_active' => 'boolean',
        ];
    }

    public function isElective(): bool
    {
        return $this->category->isElective();
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(CourseAllocation::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(Score::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(CourseRegistration::class);
    }
}
