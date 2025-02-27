<?php

class RenameCommandTest extends TestCase
{
    public function testRenameAKeyValue()
    {
        $this->createTempFiles([
            'en' => ['user' => "<?php\n return['mobile' => 'Mobile'];"],
        ]);

        $this->artisan('langman:rename', ['oldKey' => 'user.mobile', 'newKey' => 'contact']);

        $newValue = (array) include $this->app['config']['langman.path'].'/en/user.php';

        $this->assertEquals(['contact' => 'Mobile'], $newValue);
    }

    public function testRenameAKeyValueForAllLanguages()
    {
        $this->createTempFiles([
            'en' => ['user' => "<?php\n return['mobile' => 'Mobile'];"],
            'es' => ['user' => "<?php\n return['mobile' => 'Movil'];"],
        ]);
        $expectedValueEN = ['contact' => 'Mobile'];
        $expectedValueES = ['contact' => 'Movil'];

        $this->artisan('langman:rename', ['oldKey' => 'user.mobile', 'newKey' => 'contact']);

        $newValueEN = (array) include $this->app['config']['langman.path'].'/en/user.php';
        $newValueES = (array) include $this->app['config']['langman.path'].'/es/user.php';

        $this->assertEquals($expectedValueEN, $newValueEN);
        $this->assertEquals($expectedValueES, $newValueES);
    }

    public function testRenameANestedKeyValueForAllLanguages()
    {
        $this->createTempFiles([
            'en' => ['user' => "<?php\n return['contact' => ['cellphone' => 'Mobile']];"],
            'es' => ['user' => "<?php\n return['contact' => ['cellphone' => 'Movil']];"],
        ]);
        $expectedValueEN = ['contact' => ['mobile' => 'Mobile']];
        $expectedValueES = ['contact' => ['mobile' => 'Movil']];

        $this->artisan('langman:rename', ['oldKey' => 'user.contact.cellphone', 'newKey' => 'mobile']);

        $newValueEN = (array) include $this->app['config']['langman.path'].'/en/user.php';

        $newValueES = (array) include $this->app['config']['langman.path'].'/es/user.php';
        $this->assertEquals($expectedValueEN, $newValueEN);
        $this->assertEquals($expectedValueES, $newValueES);
    }

    public function testRenameOfANestedKeyValueForAllLanguagesInAnyDepth()
    {
        $this->createTempFiles([
            'en' => ['user' => "<?php\n return['contact' => ['mobile' => 'Mobile', 'others' => ['msn' => 'E-mail']]];"],
            'es' => ['user' => "<?php\n return['contact' => ['mobile' => 'Movil', 'others' => ['msn' => 'Correo electronico']]];"],
        ]);

        $expectedValueEN = ['contact' => ['mobile' => 'Mobile', 'others' => ['mail' => 'E-mail']]];
        $expectedValueES = ['contact' => ['mobile' => 'Movil', 'others' => ['mail' => 'Correo electronico']]];

        $this->artisan('langman:rename', ['oldKey' => 'user.contact.others.msn', 'newKey' => 'mail']);

        $newValueEN = (array) include $this->app['config']['langman.path'].'/en/user.php';
        $newValueES = (array) include $this->app['config']['langman.path'].'/es/user.php';
        $this->assertEquals($expectedValueEN, $newValueEN);
        $this->assertEquals($expectedValueES, $newValueES);
    }

    public function testRenameCommandShowViewFilesAffectedForTheChange()
    {
        $manager = $this->app[\Themsaid\Langman\Manager::class];

        $this->createTempFiles([
            'en' => ['users' => "<?php\n return['name' => 'Name'];"],
        ]);

        array_map('unlink', glob(__DIR__.'/views_temp/users/index.blade.php'));
        array_map('rmdir', glob(__DIR__.'/views_temp/users'));
        array_map('unlink', glob(__DIR__.'/views_temp/users.blade.php'));

        file_put_contents(__DIR__.'/views_temp/users.blade.php', "{{ trans('users.name') }} {{ trans('users.age') }}");
        mkdir(__DIR__.'/views_temp/users');
        file_put_contents(__DIR__.'/views_temp/users/index.blade.php', "{{ trans('users.name') }} {{ trans('users.city') }} {{ trans('users.name') }}");

        $this->artisan('langman:rename', ['oldKey' => 'users.name', 'newKey' => 'username']);

        array_map('unlink', glob(__DIR__.'/views_temp/users/index.blade.php'));
        array_map('rmdir', glob(__DIR__.'/views_temp/users'));
        array_map('unlink', glob(__DIR__.'/views_temp/users.blade.php'));
        $expected = <<<EXPECTED
Renamed key was found in 2 file(s).
+------------+-----------------------+
| Encounters | File                  |
+------------+-----------------------+
| 1          | users.blade.php       |
| 2          | users/index.blade.php |
+------------+-----------------------+
The key at users.name was renamed to username successfully!\n
EXPECTED;

        $this->assertEquals($expected, $this->consoleOutput());
        $this->assertMatchesRegularExpression('/Encounters(?:.*)File/', $this->consoleOutput());
        $this->assertMatchesRegularExpression('/1(?:.*)users\.blade\.php/', $this->consoleOutput());
        $this->assertMatchesRegularExpression('/2(?:.*)users(\\\|\/)index\.blade\.php/', $this->consoleOutput());
    }
}
