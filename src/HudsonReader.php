<?php

declare(strict_types=1);

namespace Golded\Ftn\Hudson;

use DateTimeImmutable;
use Golded\Ftn\Contracts\MessageBaseReader;
use Golded\Ftn\MessageProvenance;
use Golded\Ftn\ParsedMessage;
use Golded\Ftn\ReaderOptions;
use Golded\Ftn\Support\CharsetDetector;
use Golded\Ftn\Support\ControlLines;
use Golded\Ftn\Support\Text;

final class HudsonReader implements MessageBaseReader
{
    private const int HDR_SIZE = 187;
    private const int IDX_SIZE = 3;
    private const int TXT_RECORD = 128;
    private const int DELETED_MSGNO = 0xFFFF;

    /**
     * @return iterable<ParsedMessage>
     */
    public function read(string $path, ?ReaderOptions $options = null): iterable
    {
        $options ??= new ReaderOptions();
        $indexPath = $this->findFile($path, 'MSGIDX.BBS');
        $headerPath = $this->findFile($path, 'MSGHDR.BBS');
        $textPath = $this->findFile($path, 'MSGTXT.BBS');

        if ($indexPath === null || $headerPath === null || $textPath === null) {
            return;
        }

        $indexHandle = fopen($indexPath, 'rb');
        $headerHandle = fopen($headerPath, 'rb');
        $textHandle = fopen($textPath, 'rb');

        if ($indexHandle === false || $headerHandle === false || $textHandle === false) {
            return;
        }

        try {
            yield from $this->readMessages($indexHandle, $headerHandle, $textHandle, $textPath, $options);
        } finally {
            fclose($indexHandle);
            fclose($headerHandle);
            fclose($textHandle);
        }
    }

    /**
     * @param resource $indexHandle
     * @param resource $headerHandle
     * @param resource $textHandle
     *
     * @return iterable<ParsedMessage>
     */
    private function readMessages($indexHandle, $headerHandle, $textHandle, string $sourcePath, ReaderOptions $options): iterable
    {
        $position = 0;

        while (! feof($indexHandle)) {
            $indexRaw = fread($indexHandle, self::IDX_SIZE);

            if ($indexRaw === false || strlen($indexRaw) < self::IDX_SIZE) {
                break;
            }

            $index = $this->unpackIndex($indexRaw);

            if ($index === null) {
                break;
            }

            if ($index['msgno'] === self::DELETED_MSGNO) {
                $position++;

                continue;
            }

            fseek($headerHandle, $position * self::HDR_SIZE);
            $headerRaw = fread($headerHandle, self::HDR_SIZE);

            if ($headerRaw === false || strlen($headerRaw) < self::HDR_SIZE) {
                $position++;

                continue;
            }

            $header = $this->unpackHeader($headerRaw);

            if ($header === null) {
                $position++;

                continue;
            }

            fseek($textHandle, $header['startrec'] * self::TXT_RECORD);
            $textRaw = $header['numrecs'] > 0 ? fread($textHandle, $header['numrecs'] * self::TXT_RECORD) : '';
            $textRaw = $textRaw === false ? '' : $textRaw;
            $charset = CharsetDetector::detect($textRaw, $options->fallbackCharset);
            $body = $this->parseBody($textRaw);
            $board = $index['board'];
            $areaCode = 'BOARD'.$board;

            yield new ParsedMessage(
                msgno: $header['msgno'],
                fromName: Text::toUtf8(substr($header['by'], 1), $charset),
                toName: Text::toUtf8(substr($header['to'], 1), $charset),
                subject: Text::toUtf8(substr($header['re'], 1), $charset),
                bodyText: Text::toUtf8($body, $charset),
                attributesRaw: $header['msgattr'] | ($header['netattr'] << 8),
                postedAt: $this->parseDate($header['date'], $header['time']),
                replyToMsgno: $header['replyto'] ?: null,
                reply1stMsgno: $header['reply1st'] ?: null,
                areaCode: $areaCode,
                areaName: $areaCode,
                areaSortOrder: $board,
                areaMetaKey: 'hudson:'.$board,
                controlLines: ControlLines::parseMessage($body),
                provenance: new MessageProvenance(
                    sourceType: 'hudson',
                    sourcePath: $sourcePath,
                    sourceId: (string) $header['msgno'],
                    sourceOffset: $header['startrec'] * self::TXT_RECORD,
                ),
            );
            $position++;
        }
    }

    private function parseBody(string $raw): string
    {
        $raw = ltrim($raw, "\xFF");

        return Text::parseBody($raw);
    }

    private function parseDate(string $date, string $time): ?DateTimeImmutable
    {
        $date = substr($date, 1);
        $time = substr($time, 1);

        if ($date === '' || $time === '') {
            return null;
        }

        return DateTimeImmutable::createFromFormat('m-d-y H:i', trim($date).' '.trim($time)) ?: null;
    }

    private function findFile(string $directory, string $filename): ?string
    {
        foreach ([$filename, strtolower($filename)] as $candidate) {
            $path = rtrim($directory, '/').'/'.$candidate;

            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * @return array{msgno: int, board: int}|null
     */
    private function unpackIndex(string $raw): ?array
    {
        $index = unpack('vmsgno/Cboard', $raw);

        if ($index === false) {
            return null;
        }

        return [
            'msgno' => $this->integer($index, 'msgno'),
            'board' => $this->integer($index, 'board'),
        ];
    }

    /**
     * @return array{msgno: int, replyto: int, reply1st: int, startrec: int, numrecs: int, msgattr: int, netattr: int, time: string, date: string, to: string, by: string, re: string}|null
     */
    private function unpackHeader(string $raw): ?array
    {
        $header = unpack(
            'vmsgno/vreplyto/vreply1st/vtimesread/vstartrec/vnumrecs/vdestnet/vdestnode/vorignet/vorignode/Cdestzone/Corigzone/vcost/Cmsgattr/Cnetattr/Cboard/a6time/a9date/a36to/a36by/a73re',
            $raw,
        );

        if ($header === false) {
            return null;
        }

        return [
            'msgno' => $this->integer($header, 'msgno'),
            'replyto' => $this->integer($header, 'replyto'),
            'reply1st' => $this->integer($header, 'reply1st'),
            'startrec' => $this->integer($header, 'startrec'),
            'numrecs' => $this->integer($header, 'numrecs'),
            'msgattr' => $this->integer($header, 'msgattr'),
            'netattr' => $this->integer($header, 'netattr'),
            'time' => $this->string($header, 'time'),
            'date' => $this->string($header, 'date'),
            'to' => $this->string($header, 'to'),
            'by' => $this->string($header, 'by'),
            're' => $this->string($header, 're'),
        ];
    }

    /**
     * @param array<mixed> $values
     */
    private function integer(array $values, string $key): int
    {
        $value = $values[$key] ?? 0;

        return is_int($value) ? $value : 0;
    }

    /**
     * @param array<mixed> $values
     */
    private function string(array $values, string $key): string
    {
        $value = $values[$key] ?? '';

        return is_string($value) ? $value : '';
    }
}
