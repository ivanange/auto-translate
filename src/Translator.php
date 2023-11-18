<?php

namespace Devaslanphp\AutoTranslate;

use Illuminate\Support\Arr;
use Illuminate\Translation\Translator as IlluminateTranslator;

class Translator extends IlluminateTranslator {

    /**
     * Retrieve a language line out the loaded array.
     *
     * @param  string  $namespace
     * @param  string  $group
     * @param  string  $locale
     * @param  string  $item
     * @return string|array|null
     */
    public function getRawLine($namespace, $group, $locale, $item) {
        $this->load($namespace, $group, $locale);
        $line = Arr::get($this->loaded[$namespace][$group][$locale], $item);
        return $line;
    }

    /**
     * Get the raw translation for the given key.
     *
     * @param  string  $key
     * @param  string|null  $locale
     * @param  bool  $fallback
     * @return string|array
     */
    public function getRaw($key, $locale = null, $fallback = true) {
        $locale ??= $this->locale;

        // For JSON translations, there is only one file per locale, so we will simply load
        // that file and then we will be ready to check the array for the key. These are
        // only one level deep so we do not need to do any fancy searching through it.
        $this->load('*', '*', $locale);

        $line = $this->loaded['*']['*'][$locale][$key] ?? null;

        // If we can't find a translation for the JSON key, we will attempt to translate it
        // using the typical translation file. This way developers can always just use a
        // helper such as __ instead of having to pick between trans or __ with views.
        if (!isset($line)) {
            [$namespace, $group, $item] = $this->parseKey($key);

            // Here we will get the locale that should be used for the language line. If one
            // was not passed, we will use the default locales which was given to us when
            // the translator was instantiated. Then, we can load the lines and return.
            $locales = $fallback ? $this->localeArray($locale) : [$locale];

            $raw = $key;
            foreach ($locales as $locale) {
                if (!is_null($line = $this->getRawLine(
                    $namespace,
                    $group,
                    $locale,
                    $item,
                ))) {
                    if (is_array($line) && count($line) == 0) {
                        continue;
                    }
                    $raw = $line;
                    break;
                }
            }
            return $raw;
        } else {
            return $line ?? $key;
        }
    }
}
