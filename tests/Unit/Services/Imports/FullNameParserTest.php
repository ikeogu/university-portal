<?php

namespace Tests\Unit\Services\Imports;

use App\Services\Imports\FullNameParser;
use PHPUnit\Framework\TestCase;

class FullNameParserTest extends TestCase
{
    public function test_parses_surname_first_names_and_middle_names(): void
    {
        $this->assertSame(
            ['last_name' => 'OKORO', 'first_name' => 'Chidera', 'middle_name' => 'Faith'],
            FullNameParser::parse('OKORO, Chidera Faith'),
        );
    }

    public function test_parses_surname_and_single_first_name_without_a_middle_name(): void
    {
        $this->assertSame(
            ['last_name' => 'MUSA', 'first_name' => 'Ibrahim', 'middle_name' => null],
            FullNameParser::parse('MUSA, Ibrahim'),
        );
    }

    public function test_falls_back_to_splitting_on_whitespace_when_there_is_no_comma(): void
    {
        $this->assertSame(
            ['last_name' => 'OKORO', 'first_name' => 'Chidera', 'middle_name' => 'Faith'],
            FullNameParser::parse('OKORO Chidera Faith'),
        );
    }

    public function test_a_single_word_name_is_used_as_both_last_and_first_name(): void
    {
        $this->assertSame(
            ['last_name' => 'Madonna', 'first_name' => 'Madonna', 'middle_name' => null],
            FullNameParser::parse('Madonna'),
        );
    }

    public function test_trims_surrounding_whitespace(): void
    {
        $this->assertSame(
            ['last_name' => 'OKORO', 'first_name' => 'Chidera', 'middle_name' => null],
            FullNameParser::parse('  OKORO ,  Chidera  '),
        );
    }
}
