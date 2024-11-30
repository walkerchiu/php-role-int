<?php

namespace WalkerChiu\Role;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use WalkerChiu\Role\Models\Entities\Role;
use WalkerChiu\Role\Models\Entities\RoleLang;
use WalkerChiu\Role\Models\Repositories\RoleRepository;

class RoleRepositoryTest extends \Orchestra\Testbench\TestCase
{
    use RefreshDatabase;

    protected $repository;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        //$this->loadLaravelMigrations(['--database' => 'mysql']);
        $this->loadMigrationsFrom(__DIR__ .'/../migrations');
        $this->withFactories(__DIR__ .'/../../src/database/factories');

        $this->repository = $this->app->make(RoleRepository::class);
    }

    /**
     * To load your package service provider, override the getPackageProviders.
     *
     * @param \Illuminate\Foundation\Application  $app
     * @return Array
     */
    protected function getPackageProviders($app)
    {
        return [\WalkerChiu\Core\CoreServiceProvider::class,
                \WalkerChiu\Role\RoleServiceProvider::class];
    }

    /**
     * Define environment setup.
     *
     * @param \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        /*
        // Setup default database to use mysql
        $app['config']->set('database.default', 'mysql');
        $app['config']->set('database.connections.mysql', [
            'driver'   => 'mysql',
              'host' => '220.135.74.159',
              'port' => '3307',
              'database' => 'test',
              'username' => 'myweb',
              'password' => 'myweb0122',
        ]);
        */
    }

    /**
     * A basic functional test on RoleRepository.
     *
     * For WalkerChiu\Core\Models\Repositories\Repository
     *
     * @return void
     */
    public function testRoleRepository()
    {
        // Config
        Config::set('wk-core.onoff.core-lang_core', 0);
        Config::set('wk-role.onoff.core-lang_core', 0);
        Config::set('wk-core.lang_log', 1);
        Config::set('wk-role.lang_log', 1);
        Config::set('wk-core.soft_delete', 1);
        Config::set('wk-role.soft_delete', 1);

        // Give
        $faker = \Faker\Factory::create();
        for ($i=1; $i<=3; $i++)
            $this->repository->save([
                'serial'     => $faker->isbn10,
                'identifier' => $faker->slug,
                'order'      => $faker->randomNumber
            ]);

        // Get and Count records after creation
            // When
            $records = $this->repository->get();
            $count   = $this->repository->count();
            // Then
            $this->assertCount(3, $records);
            $this->assertEquals(3, $count);

        // Find someone
            // When
            $record = $this->repository->find(1);
            // Then
            $this->assertNotNull($record);

            // When
            $record = $this->repository->find(4);
            // Then
            $this->assertNull($record);

        // Delete someone
            // When
            $this->repository->deleteByIds([1]);
            $count = $this->repository->count();
            // Then
            $this->assertEquals(2, $count);

            // When
            $this->repository->deleteByExceptIds([3]);
            $count = $this->repository->count();
            $record = $this->repository->find(3);
            // Then
            $this->assertEquals(1, $count);
            $this->assertNotNull($record);

            // When
            $count = $this->repository->where('id', '>', 0)->count();
            // Then
            $this->assertEquals(1, $count);

            // When
            $count = $this->repository->whereWithTrashed('id', '>', 0)->count();
            // Then
            $this->assertEquals(3, $count);

            // When
            $count = $this->repository->whereOnlyTrashed('id', '>', 0)->count();
            // Then
            $this->assertEquals(2, $count);

        // Force delete someone
            // When
            $this->repository->forcedeleteByIds([3]);
            $records = $this->repository->get();
            // Then
            $this->assertCount(0, $records);

        // Restore records
            // When
            $this->repository->restoreByIds([1, 2]);
            $count = $this->repository->count();
            // Then
            $this->assertEquals(2, $count);
    }

    /**
     * Unit test about Lang creation on RoleRepository.
     *
     * For WalkerChiu\Core\Models\Repositories\RepositoryTrait
     *     WalkerChiu\Role\Models\Repositories\RoleRepository
     * 
     * @return void
     */
    public function testcreateLangWithoutCheck()
    {
        // Config
        Config::set('wk-core.onoff.core-lang_core', 0);
        Config::set('wk-role.onoff.core-lang_core', 0);
        Config::set('wk-core.lang_log', 1);
        Config::set('wk-role.lang_log', 1);
        Config::set('wk-core.soft_delete', 1);
        Config::set('wk-role.soft_delete', 1);

        // Give
        factory(Role::class)->create();

        // Find record
            // When
            $record = $this->repository->find(1);
            // Then
            $this->assertNotNull($record);

        // Create Lang
            // When
            $lang = $this->repository->createLangWithoutCheck(['morph_type' => get_class($record), 'morph_id' => $record->id, 'code' => 'en_us', 'key' => 'name', 'value' => 'Hello']);
            // Then
            if (config('wk-core.onoff.core-lang_core')) {
                $this->assertInstanceOf(config('wk-core.class.core.langCore'), $lang);
            } else {
                $this->assertInstanceOf(RoleLang::class, $lang);
            }
    }

    /**
     * Unit test about Enable and Disable on RoleRepository.
     *
     * For WalkerChiu\Core\Models\Repositories\RepositoryTrait
     *     WalkerChiu\Role\Models\Repositories\RoleRepository
     *
     * @return void
     */
    public function testEnableAndDisable()
    {
        // Config
        Config::set('wk-core.onoff.core-lang_core', 0);
        Config::set('wk-role.onoff.core-lang_core', 0);
        Config::set('wk-core.lang_log', 1);
        Config::set('wk-role.lang_log', 1);
        Config::set('wk-core.soft_delete', 1);
        Config::set('wk-role.soft_delete', 1);

        // Give
        factory(Role::class)->create(['is_enabled' => 1]);
        factory(Role::class, 3)->create();

        // Count records
            // When
            $count = $this->repository->count();
            $count_enabled = $this->repository->ofEnabled(null, null)->count();
            $count_disabled = $this->repository->ofDisabled(null, null)->count();
            // Then
            $this->assertEquals(4, $count);
            $this->assertEquals(1, $count_enabled);
            $this->assertEquals(3, $count_disabled);

        // Enable records
            // When
            $this->repository->whereToEnable(null, null, 'id', '>', 3);
            $count_enabled = $this->repository->ofEnabled(null, null)->count();
            $count_disabled = $this->repository->ofDisabled(null, null)->count();
            // Then
            $this->assertEquals(2, $count_enabled);
            $this->assertEquals(2, $count_disabled);

        // Disable records
            // When
            $this->repository->whereToDisable(null, null, 'id', '>', 0);
            $count_enabled = $this->repository->ofEnabled(null, null)->count();
            $count_disabled = $this->repository->ofDisabled(null, null)->count();
            // Then
            $this->assertEquals(0, $count_enabled);
            $this->assertEquals(4, $count_disabled);
    }

    /**
     * Unit test about Query List on RoleRepository.
     *
     * For WalkerChiu\Core\Models\Repositories\RepositoryTrait
     *     WalkerChiu\Role\Models\Repositories\RoleRepository
     *
     * @return void
     */
    public function testQueryList()
    {
        // Config
        Config::set('wk-core.onoff.core-lang_core', 0);
        Config::set('wk-role.onoff.core-lang_core', 0);
        Config::set('wk-core.lang_log', 1);
        Config::set('wk-role.lang_log', 1);
        Config::set('wk-core.soft_delete', 1);
        Config::set('wk-role.soft_delete', 1);

        // Give
        factory(Role::class, 4)->create();

        // Get query
            // When
            sleep(1);
            $this->repository->find(3)->touch();
            $records = $this->repository->ofNormal(null, null)->get();
            // Then
            $this->assertCount(4, $records);

            // When
            $record = $records->first();
            // Then
            $this->assertArrayNotHasKey('deleted_at', $record->toArray());
            $this->assertEquals(3, $record->id);

        // Get query of trashed records
            // When
            $this->repository->deleteByIds([4]);
            $this->repository->deleteByIds([1]);
            $records = $this->repository->ofTrash(null, null)->get();
            // Then
            $this->assertCount(2, $records);

            // When
            $record = $records->first();
            // Then
            $this->assertArrayHasKey('deleted_at', $record);
            $this->assertEquals(1, $record->id);
    }

    /**
     * Unit test about FormTrait on RoleRepository.
     *
     * For WalkerChiu\Core\Models\Repositories\RepositoryTrait
     *     WalkerChiu\Role\Models\Repositories\RoleRepository
     *     WalkerChiu\Core\Models\Forms\FormTrait
     *
     * @return void
     */
    public function testFormTrait()
    {
        // Config
        Config::set('wk-core.onoff.core-lang_core', 0);
        Config::set('wk-role.onoff.core-lang_core', 0);
        Config::set('wk-core.lang_log', 1);
        Config::set('wk-role.lang_log', 1);
        Config::set('wk-core.soft_delete', 1);
        Config::set('wk-role.soft_delete', 1);

        // Name
            // Give
            factory(Role::class, 2)->create();
            $this->repository->createLangWithoutCheck(['morph_id' => 1, 'morph_type' => Role::class, 'code' => 'en_us', 'key' => 'name', 'value' => 'Hello']);
            $this->repository->createLangWithoutCheck(['morph_id' => 2, 'morph_type' => Role::class, 'code' => 'zh_tw', 'key' => 'name', 'value' => '您好']);
            // When
            $result_1 = $this->repository->checkExistName(null, null, 'en_us', null, 'Hello');
            $result_2 = $this->repository->checkExistName(null, null, 'en_us', null, 'Hi');
            $result_3 = $this->repository->checkExistName(null, null, 'en_us', 1, 'Hello');
            $result_4 = $this->repository->checkExistName(null, null, 'en_us', 1, '您好');
            $result_5 = $this->repository->checkExistName(null, null, 'zh_tw', 1, '您好');
            $result_6 = $this->repository->checkExistNameOfEnabled(null, null, 'en_us', null, 'Hello');
            // Then
            $this->assertTrue($result_1);
            $this->assertTrue(!$result_2);
            $this->assertTrue(!$result_3);
            $this->assertTrue(!$result_4);
            $this->assertTrue($result_5);
            $this->assertTrue(!$result_6);

        // Serial, Identifier
            // Give
            factory(Role::class)->create(['serial' => '123', 'identifier' => 'A123']);
            factory(Role::class)->create(['serial' => '124', 'identifier' => 'A124']);
            factory(Role::class)->create(['serial' => '125', 'identifier' => 'A125', 'is_enabled' => 1]);
            // When
            $result_1 = $this->repository->checkExistSerial(null, null, null, '123');
            $result_2 = $this->repository->checkExistSerial(null, null, 3, '123');
            $result_3 = $this->repository->checkExistSerial(null, null, 3, '124');
            $result_4 = $this->repository->checkExistSerialOfEnabled(null, null, 4, '124');
            $result_5 = $this->repository->checkExistSerialOfEnabled(null, null, 4, '125');
            // Then
            $this->assertTrue($result_1);
            $this->assertTrue(!$result_2);
            $this->assertTrue($result_3);
            $this->assertTrue(!$result_4);
            $this->assertTrue($result_5);
            // When
            $result_1 = $this->repository->checkExistIdentifier(null, null, null, 'A123');
            $result_2 = $this->repository->checkExistIdentifier(null, null, 3, 'A123');
            $result_3 = $this->repository->checkExistIdentifier(null, null, 3, 'A124');
            $result_4 = $this->repository->checkExistIdentifierOfEnabled(null, null, 4, 'A124');
            $result_5 = $this->repository->checkExistIdentifierOfEnabled(null, null, 4, 'A125');
            // Then
            $this->assertTrue($result_1);
            $this->assertTrue(!$result_2);
            $this->assertTrue($result_3);
            $this->assertTrue(!$result_4);
            $this->assertTrue($result_5);
    }

    /**
     * Unit test about Auto Complete on RoleRepository.
     *
     * For WalkerChiu\Core\Models\Repositories\RepositoryTrait
     *     WalkerChiu\Role\Models\Repositories\RoleRepository
     *
     * @return void
     */
    public function testAutoComplete()
    {
        // Config
        Config::set('wk-core.onoff.core-lang_core', 0);
        Config::set('wk-role.onoff.core-lang_core', 0);
        Config::set('wk-core.lang_log', 1);
        Config::set('wk-role.lang_log', 1);
        Config::set('wk-core.soft_delete', 1);
        Config::set('wk-role.soft_delete', 1);

        // Give
        factory(Role::class)->create(['serial' => 'A123', 'is_enabled' => 1]);
        factory(Role::class)->create(['serial' => 'A124', 'is_enabled' => 1]);
        $this->repository->createLangWithoutCheck(['morph_id' => 1, 'morph_type' => Role::class, 'code' => 'en_us', 'key' => 'name', 'value' => 'Hello']);
        $this->repository->createLangWithoutCheck(['morph_id' => 1, 'morph_type' => Role::class, 'code' => 'en_us', 'key' => 'description', 'value' => 'Good Morning!']);
        $this->repository->createLangWithoutCheck(['morph_id' => 1, 'morph_type' => Role::class, 'code' => 'en_us', 'key' => 'name', 'value' => 'Hello World']);
        $this->repository->createLangWithoutCheck(['morph_id' => 1, 'morph_type' => Role::class, 'code' => 'zh_tw', 'key' => 'name', 'value' => '您好']);
        $this->repository->createLangWithoutCheck(['morph_id' => 1, 'morph_type' => Role::class, 'code' => 'zh_tw', 'key' => 'name', 'value' => '早安']);
        $this->repository->createLangWithoutCheck(['morph_id' => 2, 'morph_type' => Role::class, 'code' => 'en_us', 'key' => 'name', 'value' => 'Bye']);

        // List array by name of enabled records
            // When
            $records = $this->repository->autoCompleteNameOfEnabled(null, null, 'en_us', 'H');
            // Then
            $this->assertCount(1, $records);

            // When
            $records = $this->repository->autoCompleteNameOfEnabled(null, null, 'zh_tw', 'H');
            // Then
            $this->assertCount(0, $records);

        // List array by serial of enabled records
            // When
            $records = $this->repository->autoCompleteSerialOfEnabled(null, null, 'en_us', 'A');
            // Then
            $this->assertCount(2, $records);
    }
}
