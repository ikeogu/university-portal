<?php

namespace Tests\Feature\Public;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_only_exposes_a_bio_data_link_when_the_setting_is_open(): void
    {
        $this->get(route('landing'))
            ->assertInertia(fn ($page) => $page
                ->component('Public/Landing')
                ->where('bioDataHref', null)
            );

        Setting::set('bioUpdateOpen', true);

        $this->get(route('landing'))
            ->assertInertia(fn ($page) => $page
                ->component('Public/Landing')
                ->where('bioDataHref', route('public.bio.edit'))
            );
    }
}
