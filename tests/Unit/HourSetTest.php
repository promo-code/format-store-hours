<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PromoCode\StoreHours\HourSet;

class HourSetTest extends TestCase
{
    public function test_add_sorts_hours_by_earliest_start_date()
    {
        $hours_set = new HourSet();

        $this->assertCount(0, $hours_set);

        $hours_set->add(
            $later_start = new \DateTime('+3 hours'),
            $later_end = new \DateTime('+11 hours')
        );

        $this->assertCount(1, $hours_set);

        $hours = $hours_set->current();

        $this->assertEquals(
            $later_start->format('g:ia'),
            $hours['start']->format('g:ia')
        );

        $hours_set->rewind();

        $hours_set->add(
            $earlier_start = new \DateTime('-3 hours'),
            $earlier_end = new \DateTime('+5 hours')
        );

        $this->assertCount(2, $hours_set);

        $hours = $hours_set->current();

        $this->assertEquals(
            $earlier_start->format('g:ia'),
            $hours['start']->format('g:ia')
        );
    }
}
