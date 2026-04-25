<?php

use Golded\Ftn\Hudson\HudsonReader;
use Golded\Ftn\ParsedMessage;

function hudsonFixtureBase(): string
{
    $path = sys_get_temp_dir().'/laravel-ftn-hudson-tests';

    if (! is_dir($path)) {
        mkdir($path, recursive: true);
    }

    $body = "I want this Hudson body preserved.\r\n";
    $recordCount = (int) ceil(strlen($body) / 128);

    file_put_contents($path.'/MSGIDX.BBS', pack('vC', 42, 7));
    file_put_contents($path.'/MSGHDR.BBS', hudsonHeader(
        msgno: 42,
        replyTo: 12,
        firstReply: 13,
        startRecord: 0,
        recordCount: $recordCount,
        board: 7,
        fromName: 'Odinn Sorensen',
        toName: 'Gregory ThroatWobbler',
        subject: 'Keep on the good work..',
        date: '01-01-24',
        time: '12:34',
    ));
    file_put_contents($path.'/MSGTXT.BBS', str_pad($body, $recordCount * 128, "\0"));

    return $path;
}

it('reads real Hudson messages and board metadata', function (): void {
    $messages = array_values(iterator_to_array(new HudsonReader()->read(hudsonFixtureBase())));
    $first = firstHudsonMessage($messages);

    expect($first->msgno)->toBe(42)
        ->and($first->fromName)->toBe('Odinn Sorensen')
        ->and($first->toName)->toBe('Gregory ThroatWobbler')
        ->and($first->subject)->toBe('Keep on the good work..')
        ->and($first->bodyText)->toContain('Hudson body preserved')
        ->and($first->postedAt)->not->toBeNull()
        ->and($first->replyToMsgno)->toBe(12)
        ->and($first->reply1stMsgno)->toBe(13)
        ->and($first->areaCode)->toBe('BOARD7')
        ->and($first->areaName)->toBe('BOARD7')
        ->and($first->areaSortOrder)->toBe(7)
        ->and($first->areaMetaKey)->toBe('hudson:7');
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

function hudsonHeader(
    int $msgno,
    int $replyTo,
    int $firstReply,
    int $startRecord,
    int $recordCount,
    int $board,
    string $fromName,
    string $toName,
    string $subject,
    string $date,
    string $time,
): string {
    return pack(
        'v11C5',
        $msgno,
        $replyTo,
        $firstReply,
        0,
        $startRecord,
        $recordCount,
        0,
        0,
        0,
        0,
        0,
        0,
        0,
        0,
        0,
        $board,
    )
        .str_pad("\0".$time, 6, "\0")
        .str_pad("\0".$date, 9, "\0")
        .str_pad("\0".$toName, 36, "\0")
        .str_pad("\0".$fromName, 36, "\0")
        .str_pad("\0".$subject, 73, "\0");
}
