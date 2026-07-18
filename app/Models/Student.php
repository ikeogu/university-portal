<?php

namespace App\Models;

use App\Enums\MaritalStatus;
use App\Enums\ModeOfStudy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'mat_no', 'entry_year', 'last_name', 'first_name', 'middle_name', 'dob',
    'state_of_origin', 'marital_status', 'mode_of_study', 'photo_path', 'access_pin', 'is_active',
])]
#[Hidden(['access_pin_hash'])]
class Student extends Model
{
    use HasUlids;

    /**
     * The plaintext PIN, set only in-memory right after generation (auto on
     * create, or via regeneratePin()) so the caller can show it exactly
     * once. Never persisted, never touches $fillable/casts.
     */
    public ?string $plainAccessPin = null;

    protected static function booted(): void
    {
        static::creating(function (Student $student) {
            if (empty($student->access_pin_hash)) {
                $student->setAccessPin(self::generatePin());
            }
        });
    }

    protected function casts(): array
    {
        return [
            'dob' => 'date',
            'marital_status' => MaritalStatus::class,
            'mode_of_study' => ModeOfStudy::class,
            'is_active' => 'boolean',
        ];
    }

    protected function fullName(): Attribute
    {
        return Attribute::get(fn () => trim("{$this->last_name}, {$this->first_name} {$this->middle_name}"));
    }

    protected function photoUrl(): Attribute
    {
        return Attribute::get(fn () => $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null);
    }

    /**
     * Virtual write-only attribute: `Student::create(['access_pin' => '123456', ...])`
     * hashes straight into access_pin_hash. Never readable back — there is
     * no getter, by design.
     */
    protected function accessPin(): Attribute
    {
        return Attribute::set(fn (string $value) => ['access_pin_hash' => Hash::make($value)]);
    }

    public function verifyPin(string $pin): bool
    {
        return Hash::check($pin, $this->access_pin_hash);
    }

    /**
     * Issue a fresh PIN, invalidating the old one, and return it in plain
     * text this one time — the caller (an admin action) must show it to
     * the student immediately; it cannot be recovered afterward.
     */
    public function regeneratePin(): string
    {
        $this->setAccessPin($plain = self::generatePin());
        $this->save();

        return $plain;
    }

    private function setAccessPin(string $plain): void
    {
        $this->access_pin_hash = Hash::make($plain);
        $this->plainAccessPin = $plain;
    }

    private static function generatePin(): string
    {
        return (string) random_int(100000, 999999);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(Score::class);
    }
}
