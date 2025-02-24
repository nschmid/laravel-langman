<?php

class FindCommandTest extends TestCase
{
    public function testCommandErrorOnFilesNotFound()
    {
        array_map('unlink', glob(__DIR__.'/temp/*/*'));
        array_map('rmdir', glob(__DIR__.'/temp/*'));

        $this->createTempFiles();

        $this->artisan('langman:find', ['keyword' => 'ragnar']);

        $expected = "No language files were found!\n+-----+\n| key |\n+-----+\n";
        $this->assertEquals($expected, $this->consoleOutput());
    }

    public function testCommandOutputForFile()
    {
        $this->createTempFiles([
            'en' => ['user' => "<?php\n return ['not_found' => 'User NoT fOunD', 'age' => 'Age'];"],
            'nl' => ['user' => "<?php\n return ['not_found' => 'something'];"],
            'sp' => ['user' => "<?php\n return ['else' => 'else'];"],
        ]);

        $this->artisan('langman:find', ['keyword' => 'not found']);

        $this->assertMatchesRegularExpression('/key(?:.*)en(?:.*)nl/', $this->consoleOutput());
        $this->assertMatchesRegularExpression('/user\.not_found(?:.*)User NoT fOunD(?:.*)something/', $this->consoleOutput());
        $this->assertNotContains('age', [$this->consoleOutput()]);
        $this->assertNotContains('else', [$this->consoleOutput()]);
    }

    public function testCommandOutputForFileWithNestedKeys()
    {
        $this->createTempFiles([
            'en' => ['user' => "<?php\n return ['missing' => ['not_found' => 'user not found'], 'jarl_borg' => 'flying'];"],
            'sp' => ['user' => "<?php\n return ['missing' => ['not_found' => 'sp']];"],
        ]);

        $this->artisan('langman:find', ['keyword' => 'not found']);

        $this->assertMatchesRegularExpression('/key(?:.*)en(?:.*)sp/', $this->consoleOutput());
        $this->assertMatchesRegularExpression('/user\.missing\.not_found(?:.*)user not found(?:.*)sp/', $this->consoleOutput());
        $this->assertNotContains('jarl_borg', [$this->consoleOutput()]);
    }

    public function testCommandOutputForPackage()
    {
        $this->createTempFiles([
            'en' => ['user' => "<?php\n return ['weight' => 'weight'];", 'category' => ''],
            'nl' => ['user' => '', 'category' => ''],
            'vendor' => ['package' => ['en' => ['file' => "<?php\n return ['not_found' => 'file not found here'];"], 'sp' => ['file' => "<?php\n return ['not_found' => 'something'];"]]],
        ]);

        $this->artisan('langman:find', ['keyword' => 'not found', '--package' => 'package']);

        $this->assertMatchesRegularExpression('/key(?:.*)en(?:.*)sp/', $this->consoleOutput());
        $this->assertMatchesRegularExpression('/package::file\.not_found(?:.*)file not found here(?:.*)something/', $this->consoleOutput());
        $this->assertNotContains('weight', [$this->consoleOutput()]);
    }
}
