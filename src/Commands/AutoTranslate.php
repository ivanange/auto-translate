<?php

namespace Devaslanphp\AutoTranslate\Commands;

use Devaslanphp\AutoTranslate\Translator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Stichoza\GoogleTranslate\GoogleTranslate;

class AutoTranslate extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auto:translate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will search everywhere in your code for translations to automatically generate JSON files for you.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(Translator $coreTranslator) {
        // $this->info("translating...:" . $coreTranslator->getRaw('adminhub::notifications.account.updated', 'en', false));
        // return Command::SUCCESS;

        $locales = config('auto-translate.locales');
        foreach ($locales as $locale) {
            try {
                $filePath = lang_path($locale . '.json');
                $oldFilePath = lang_path($locale . '.old.json');
                if (File::exists($filePath)) File::move($filePath, $oldFilePath); // So it's not used by $coreTranslator.
                Artisan::call('translatable:export ' . $locale);

                if (File::exists($filePath)) {
                    $this->info('Translating ' . $locale . ', please wait...');
                    $results = [];
                    $localeFile = File::get($filePath);
                    File::delete($filePath); // So it's not used by $coreTranslator.
                    $localeFileContent = array_keys(json_decode($localeFile, true));

                    $translator = new GoogleTranslate($locale);
                    // $translator->setSource(config('app.fallback_locale'));
                    $this->withProgressBar($localeFileContent, function ($key) use ($coreTranslator, $locale, $translator, &$results) {
                        $raw = $coreTranslator->getRaw($key, $locale, true);
                        // $this->info("Raw string for {$key}: " . json_encode($raw ?? 'absent'));
                        $results[$key] = is_array($raw) ?
                            array_map(fn ($item) => $translator->translate($item), $raw)
                            : $translator->translate($raw);
                    });
                    $this->newLine();

                    File::put($filePath, json_encode($results, JSON_UNESCAPED_UNICODE));
                    File::delete($oldFilePath);
                }
            } catch (\Exception $e) {
                $this->error('Error: ' . $e->getMessage());
                if (File::exists($oldFilePath)) File::move($oldFilePath, $filePath);
            }
        }
        return Command::SUCCESS;
    }
}
