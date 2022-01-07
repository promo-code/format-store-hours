# Store Hours

A universal way to handle store hours.

```php
use \PromoCode\StoreHours\Hours;

$hours = Hours::instance()
    // Default is Monday (1)
    // This is setting it to Sunday (7)
    ->setStartingDayOfWeek(7)
    // By default consecutive days that have the same open hours are combined
    // This option will turn that off so every day is separate
    ->setCombineConsecutiveDaysWithSameHours(false)
    // This sets the time format universally
    // You can also set start/end dates individually
    ->setTimeFormat('g:ia');

foreach ($this->hours as $h) {
    $hours->addHoursForDay(
        $h->opens,
        $h->closes,
        $h->day_of_week
    );
}

return $hours->getFormattedHours();
```
