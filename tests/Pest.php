<?php

use ClassAtlas\USms\DataObjects\MessageData;
use ClassAtlas\USms\Tests\Support\RecordingMode;
use ClassAtlas\USms\Tests\Support\ScrubbedFixture;
use ClassAtlas\USms\Tests\TestCase;

RecordingMode::bootstrap();

uses(TestCase::class)->in('Unit', 'Requests', 'Channels', 'Http', 'Recording');

function scrubbedFixture(string $name): ScrubbedFixture
{
    return new ScrubbedFixture($name);
}

function testMessage(string $text = 'ClassAtlas test', mixed ...$options): MessageData
{
    return new MessageData(
        RecordingMode::brandId(),
        [RecordingMode::number()],
        $text,
        ...$options,
    );
}
