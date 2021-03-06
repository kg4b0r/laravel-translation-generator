<?php declare(strict_types=1);

namespace ProcyonWeb\TranslationGenerator;

use Illuminate\Console\Command;

class GenerateCommand extends Command
{
    protected $signature = 'translation:generate {lang=en} {--upload}';

    protected $description = 'Generate missing translation strings in php and Vue files';

    /** @var PhraseClient */
    private $client;

    public function __construct(PhraseClient $client)
    {
        parent::__construct();
        $this->client = $client;
    }

    public function handle(): void
    {
        $translatables = (new SearchService())->getTranslatableStrings(config('translation.generator.patterns'));
        $this->client->downloadFiles();

        $lang = $this->argument('lang');
        $fileName = 'resources/lang/' . $lang . '.json';
        $jsonFile = file_get_contents($fileName);
        $translations = json_decode($jsonFile, true);

        foreach ($translatables as $translatable) {
            $translatable = str_replace('\\', '', $translatable);
            if (!array_key_exists($translatable, $translations)) {
                $translations[$translatable] = $translatable;
            }
        }

        $content = json_encode($translations, JSON_PRETTY_PRINT + JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES);
        file_put_contents($fileName, $content);

        $isUpload = $this->option('upload');
        if ($isUpload && $this->client->isReady()) {
            $this->client->uploadFile($content, $fileName, $lang);
        }
    }
}