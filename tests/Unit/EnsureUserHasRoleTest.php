<?php

namespace Tests\Unit;

use App\Enums\StaffRole;
use App\Http\Middleware\EnsureUserHasRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class EnsureUserHasRoleTest extends TestCase
{
    use RefreshDatabase;

    private function callMiddleware(User $user, string ...$roles): mixed
    {
        $request = Request::create('/admin');
        $request->setUserResolver(fn () => $user);

        return (new EnsureUserHasRole)->handle($request, fn () => response('next-called'), ...$roles);
    }

    public function test_it_allows_a_user_with_the_required_role(): void
    {
        $hod = User::factory()->hod()->create();

        $this->assertSame('next-called', $this->callMiddleware($hod, StaffRole::Hod->value, StaffRole::ExamOfficer->value)->getContent());
    }

    public function test_it_blocks_a_user_without_the_required_role(): void
    {
        $lecturer = User::factory()->lecturer()->create();

        $this->expectException(HttpException::class);

        $this->callMiddleware($lecturer, StaffRole::Hod->value, StaffRole::ExamOfficer->value);
    }
}
