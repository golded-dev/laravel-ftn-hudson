<?php

use Golded\Ftn\Hudson\HudsonReader;
use Golded\Ftn\ParsedMessage;

function hudsonFixtureBase(): string
{
    return __DIR__.'/../../../archive/messages/HUDSON';
}

it('reads real Hudson messages and board metadata', function (): void {
    $messages = array_values(iterator_to_array(new HudsonReader()->read(hudsonFixtureBase())));
    $first = firstHudsonMessage($messages);

    expect($first->fromName)->toBe('Dirk A. Mueller')
        ->and($first->toName)->not->toBeEmpty()
        ->and($first->subject)->not->toBeEmpty()
        ->and($first->postedAt)->not->toBeNull()
        ->and($first->areaCode)->toStartWith('BOARD');
});

/**
 * @param list<ParsedMessage> $messages
 */
function firstHudsonMessage(array $messages): ParsedMessage
{
    if ($messages === []) {
        throw new RuntimeException('Expected at least one parsed Hudson message.');
    }

    return $messages[0];
}
