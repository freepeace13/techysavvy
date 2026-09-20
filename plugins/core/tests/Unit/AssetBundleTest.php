<?php

namespace Techysavvy\Core\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Techysavvy\Core\Assets\AssetBundle;

class AssetBundleTest extends TestCase
{
    public function test_it_exposes_declared_scripts_and_styles(): void
    {
        $bundle = new AssetBundle('/tmp/dist', ['a.js', 'b.js' => ['module' => true]], ['a.css']);

        $this->assertSame(['a.js' => ['module' => false], 'b.js' => ['module' => true]], $bundle->scripts());
        $this->assertSame(['a.css'], $bundle->styles());
        $this->assertSame(['a.js', 'b.js', 'a.css'], $bundle->files());
        $this->assertTrue($bundle->has('a.css'));
        $this->assertFalse($bundle->has('other.js'));
        $this->assertSame('/tmp/dist/a.js', $bundle->path('a.js'));
    }

    #[DataProvider('badFileNames')]
    public function test_it_rejects_unsafe_or_wrongly_typed_file_names(string $file): void
    {
        $this->expectException(InvalidArgumentException::class);

        new AssetBundle('/tmp/dist', [$file]);
    }

    /** @return array<string, array{string}> */
    public static function badFileNames(): array
    {
        return [
            'parent traversal' => ['../secret.js'],
            'forward slash' => ['sub/a.js'],
            'backslash' => ['sub\\a.js'],
            'double dot' => ['a..js'],
            'leading dot' => ['.hidden.js'],
            'wrong extension' => ['a.css'],
            'no extension' => ['a'],
        ];
    }

    public function test_it_rejects_a_script_as_a_style(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new AssetBundle('/tmp/dist', [], ['a.js']);
    }

    public function test_it_rejects_unknown_or_non_bool_script_options(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AssetBundle('/tmp/dist', ['a.js' => ['defer' => true]]);
    }

    public function test_it_rejects_a_non_bool_module_option(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AssetBundle('/tmp/dist', ['a.js' => ['module' => 'yes']]);
    }

    public function test_path_refuses_undeclared_files(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new AssetBundle('/tmp/dist', ['a.js']))->path('b.js');
    }
}
