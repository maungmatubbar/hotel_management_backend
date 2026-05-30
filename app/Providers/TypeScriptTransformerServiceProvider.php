<?php

namespace App\Providers;

use Illuminate\Support\Facades\File;
use Spatie\LaravelTypeScriptTransformer\TypeScriptTransformerApplicationServiceProvider as BaseTypeScriptTransformerServiceProvider;
use Spatie\TypeScriptTransformer\Formatters\PrettierFormatter;
use Spatie\TypeScriptTransformer\Transformers\AttributedClassTransformer;
use Spatie\TypeScriptTransformer\Transformers\EnumTransformer;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfigFactory;
use Spatie\TypeScriptTransformer\Writers\FlatModuleWriter;

class TypeScriptTransformerServiceProvider extends BaseTypeScriptTransformerServiceProvider
{
    protected function configure(TypeScriptTransformerConfigFactory $config): void
    {
        $outputDirectory = (string) config('typescript-transformer.output_directory', 'resources/js/generated');
        $outputFile = (string) config('typescript-transformer.output_file', 'generated.d.ts');

        if (! str_starts_with($outputDirectory, DIRECTORY_SEPARATOR)) {
            $outputDirectory = base_path($outputDirectory);
        }

        File::ensureDirectoryExists($outputDirectory);

        $config
            ->transformer(AttributedClassTransformer::class)
            ->transformer(EnumTransformer::class)
            ->transformDirectories(app_path())
            ->outputDirectory($outputDirectory)
            ->writer(new FlatModuleWriter($outputFile))
            ->formatter(PrettierFormatter::class);
    }
}
