<?php
class FileHandler
{
    public static function readFile(string $filename): array
    {
        if (!file_exists($filename) || !filesize($filename)) {
            throw new Exception('The file does not exist or is empty');
        }

        try {
            $rawInputData = file_get_contents($filename);
            return json_decode($rawInputData, true, 512, JSON_THROW_ON_ERROR);
        } catch (Exception $error) {
            throw new Exception('Invalid JSON format in input file');
        }
    }

    public static function writeFile(string $filename, array $notes): void
    {
        $writingResult = file_put_contents($filename, json_encode($notes));
        if ($writingResult === false) {
            throw new Exception('An error occurred while writing to a file');
        }
    }
}

class NoteHandler
{
    private const NUMBER_SEMITONES = 12;
    private const MIN_NOTE_POSITION = [-3, 10];
    private const MAX_NOTE_POSITION = [5, 1];

    private array $notes;

    public function __construct(array $notes)
    {
        $this->notes = $notes;
    }

    private function validatePosition(array $note): bool
    {
        if (
            !is_array($note) ||
            !is_int($note[0]) ||
            !is_int($note[1]) ||
            $note[1] < 1 ||
            $note[1] > self::NUMBER_SEMITONES
        ) {
            throw new Exception('Invalid note position');
        }

        return true;
    }

    private function validateRange(array $note): void
    {
        if ($note < self::MIN_NOTE_POSITION || $note > self::MAX_NOTE_POSITION) {
            throw new Exception('The note position has reached the limit');
        }
    }

    public function transpose(int $offset): void
    {
        foreach ($this->notes as &$note) {
            $this->validatePosition($note);

            $noteFullPosition = $note[0] * self::NUMBER_SEMITONES + $note[1];

            $newNoteFullPosition = $noteFullPosition + $offset;

            if ($newNoteFullPosition % self::NUMBER_SEMITONES !== 0) {
                $newOctave = floor($newNoteFullPosition / self::NUMBER_SEMITONES);
                $newPositionInOctave = abs($newOctave * self::NUMBER_SEMITONES - $newNoteFullPosition);
            } else {
                $newOctave = $newNoteFullPosition / self::NUMBER_SEMITONES - 1;
                $newPositionInOctave = self::NUMBER_SEMITONES;
            }

            $note = [$newOctave, $newPositionInOctave];

            $this->validateRange($note);
        }
    }

    public function getNotes(): array
    {
        return $this->notes;
    }
}

class App
{
    private string $inputFile;
    private string $outputFile;
    private int $offset;

    public function __construct(string $inputFile, string $outputFile, int $offset)
    {
        $this->inputFile = $inputFile;
        $this->outputFile = $outputFile;
        $this->offset = $offset;
    }

    public function run(): void
    {
        $notes = FileHandler::readFile($this->inputFile);

        $noteHandler = new NoteHandler($notes);
        $noteHandler->transpose($this->offset);

        $transposedNotes = $noteHandler->getNotes();
        FileHandler::writeFile($this->outputFile, $transposedNotes);

        echo "File processed successfully. The result is in out.json";
    }
}

try {
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

    $app = new App($inputFile, $outputFile, $offset);
    $app->run();
} catch (Exception $error) {
    echo "Error: " . $error->getMessage();
}
