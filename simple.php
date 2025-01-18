<?php
const NUMBER_SEMITONES = 12;
const MIN_NOTE_POSITION = [-3, 10];
const MAX_NOTE_POSITION = [5, 1];

$inputFile = $argv[1] ?? null;
$outputFile = 'out.json';

if (empty($inputFile)) {
    throw new Exception('The path to the input file is not specified');
}

if (!file_exists($inputFile) || !filesize($inputFile)) {
    throw new Exception('The file does not exist or is empty');
}

$offset = isset($argv[2]) ? (int)$argv[2] : null;
if (!$offset) {
    throw new Exception('No offset is provided');
}

try {
    $rawInputData = file_get_contents($inputFile);
    $notes = json_decode($rawInputData, true, 512, JSON_THROW_ON_ERROR);
} catch (Exception $e) {
    throw new Exception('Invalid JSON format in input file');
}

foreach ($notes as &$note) {
    if (
        !is_array($note) ||
        !is_int($note[0]) ||
        !is_int($note[1]) ||
        $note[1] < 1 ||
        $note[1] > NUMBER_SEMITONES
    ) {
        throw new Exception('Invalid note position');
    }

    $noteFullPosition = $note[0] * NUMBER_SEMITONES + $note[1];

    $newNoteFullPosition = $noteFullPosition + $offset;

    if ($newNoteFullPosition % NUMBER_SEMITONES !== 0) {
        $newOctave = floor($newNoteFullPosition / NUMBER_SEMITONES);
        $newPositionInOctave = abs($newOctave * NUMBER_SEMITONES - $newNoteFullPosition);
    } else {
        $newOctave = $newNoteFullPosition / NUMBER_SEMITONES - 1;
        $newPositionInOctave = NUMBER_SEMITONES;
    }

    $note = [$newOctave, $newPositionInOctave];

    if ($note < MIN_NOTE_POSITION || $note > MAX_NOTE_POSITION) {
        throw new Exception('The note position has reached the limit');
    }
}

$writingResult = file_put_contents($outputFile, json_encode($notes));
if ($writingResult === false) {
    throw new Exception('An error occurred while writing to a file');
}
echo "File processed successfully. The result is in the out.json file";
