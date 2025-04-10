<?php
/**
 * Copyright 2016 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Google\Cloud\Spanner\Tests\Unit\Connection;

use Google\ApiCore\Call;
use Google\ApiCore\CredentialsWrapper;
use Google\ApiCore\OperationResponse;
use Google\ApiCore\Serializer;
use Google\ApiCore\Testing\MockResponse;
use Google\ApiCore\Transport\TransportInterface;
use Google\Cloud\Core\GrpcRequestWrapper;
use Google\Cloud\Core\GrpcTrait;
use Google\Cloud\Core\Testing\GrpcTestTrait;
use Google\Cloud\Spanner\Admin\Database\V1\Backup;
use Google\Cloud\Spanner\Admin\Database\V1\CreateBackupEncryptionConfig;
use Google\Cloud\Spanner\Admin\Database\V1\EncryptionConfig;
use Google\Cloud\Spanner\Admin\Database\V1\RestoreDatabaseEncryptionConfig;
use Google\Cloud\Spanner\Admin\Instance\V1\Instance;
use Google\Cloud\Spanner\Admin\Instance\V1\Instance\State;
use Google\Cloud\Spanner\Admin\Instance\V1\InstanceConfig;
use Google\Cloud\Spanner\Connection\Grpc;
use Google\Cloud\Spanner\V1\BatchWriteRequest\MutationGroup as MutationGroupProto;
use Google\Cloud\Spanner\V1\DeleteSessionRequest;
use Google\Cloud\Spanner\V1\ExecuteBatchDmlRequest\Statement;
use Google\Cloud\Spanner\V1\ExecuteSqlRequest\QueryOptions;
use Google\Cloud\Spanner\V1\KeySet;
use Google\Cloud\Spanner\V1\Mutation;
use Google\Cloud\Spanner\V1\Mutation\Delete;
use Google\Cloud\Spanner\V1\Mutation\Write;
use Google\Cloud\Spanner\V1\PartialResultSet;
use Google\Cloud\Spanner\V1\PartitionOptions;
use Google\Cloud\Spanner\V1\RequestOptions;
use Google\Cloud\Spanner\V1\Session;
use Google\Cloud\Spanner\V1\SpannerClient;
use Google\Cloud\Spanner\V1\TransactionOptions;
use Google\Cloud\Spanner\V1\TransactionOptions\PartitionedDml;
use Google\Cloud\Spanner\V1\TransactionOptions\PBReadOnly;
use Google\Cloud\Spanner\V1\TransactionOptions\ReadWrite;
use Google\Cloud\Spanner\V1\TransactionSelector;
use Google\Cloud\Spanner\V1\Type;
use Google\Cloud\Spanner\ValueMapper;
use Google\Cloud\Spanner\MutationGroup;
use Google\Protobuf\FieldMask;
use Google\Protobuf\ListValue;
use Google\Protobuf\NullValue;
use Google\Protobuf\Struct;
use Google\Protobuf\Timestamp;
use Google\Protobuf\Value;
use GuzzleHttp\Promise\PromiseInterface;
use http\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @group spanner
 * @group spanner-grpc
 */
class GrpcTest extends TestCase
{
    use GrpcTestTrait;
    use GrpcTrait;
    use ProphecyTrait;

    const CONFIG = 'projects/my-project/instanceConfigs/config-1';
    const DATABASE = 'projects/my-project/instances/instance-1/databases/database-1';
    const INSTANCE = 'projects/my-project/instances/instance-1';
    const PROJECT = 'projects/my-project';
    const SESSION = 'projects/my-project/instances/instance-1/databases/database-1/sessions/session-1';
    const TABLE = 'table-1';
    const TRANSACTION = 'transaction-1';

    private $requestWrapper;
    private $serializer;
    private $successMessage;
    private $lro;

    public function setUp(): void
    {
        $this->checkAndSkipGrpcTests();

        $this->requestWrapper = $this->prophesize(GrpcRequestWrapper::class);
        $this->serializer = new Serializer;
        $this->successMessage = 'success';
        $this->lro = $this->prophesize(OperationResponse::class)->reveal();
    }

    public function testApiEndpoint()
    {
        $expected = 'foobar.com';

        $grpc = new GrpcStub(['apiEndpoint' => $expected]);

        $this->assertEquals($expected, $grpc->config['apiEndpoint']);
    }

    /**
     * @dataProvider clientUniverseDomainConfigProvider
     */
    public function testUniverseDomain($config, $expectedUniverseDomain, ?string $envUniverse = null)
    {
        if ($envUniverse) {
            putenv('GOOGLE_CLOUD_UNIVERSE_DOMAIN=' . $envUniverse);
        }

        $grpc = new GrpcStub($config);

        if ($envUniverse) {
            // We have to do this instead of using "@runInSeparateProcess" because in the case of
            // an error, PHPUnit throws a "Serialization of 'ReflectionClass' is not allowed" error.
            // @TODO: Remove this once we've updated to PHPUnit 10.
            putenv('GOOGLE_CLOUD_UNIVERSE_DOMAIN');
        }

        $this->assertEquals($expectedUniverseDomain, $grpc->config['universeDomain']);
    }

    public function clientUniverseDomainConfigProvider()
    {
        return [
            [[], 'googleapis.com'],
            [['universeDomain' => 'googleapis.com'], 'googleapis.com'],
            [['universeDomain' => 'abc.def.ghi'], 'abc.def.ghi'],
            [[], 'abc.def.ghi', 'abc.def.ghi'],
            [['universeDomain' => 'googleapis.com'], 'googleapis.com', 'abc.def.ghi'],
        ];
    }

    public function testListInstanceConfigs()
    {
        $this->assertCallCorrect('listInstanceConfigs', [
            'projectName' => self::PROJECT
        ], $this->expectResourceHeader(self::PROJECT, [
            self::PROJECT
        ]));
    }

    public function testGetInstanceConfig()
    {
        $this->assertCallCorrect('getInstanceConfig', [
            'name' => self::CONFIG,
            'projectName' => self::PROJECT
        ], $this->expectResourceHeader(self::PROJECT, [
            self::CONFIG
        ]));
    }

    public function testCreateInstanceConfig()
    {
        list ($args, $config) = $this->instanceConfig();

        $this->assertCallCorrect(
            'createInstanceConfig',
            [
                'projectName' => self::PROJECT,
                'instanceConfigId' => self::CONFIG
            ] + $args,
            $this->expectResourceHeader(self::CONFIG, [
                self::PROJECT,
                self::CONFIG,
                $config
            ]),
            $this->lro,
            null
        );
    }

    public function testUpdateInstanceConfig()
    {
        list ($args, $config, $fieldMask) = $this->instanceConfig(false);
        $this->assertCallCorrect('updateInstanceConfig', $args, $this->expectResourceHeader(self::CONFIG, [
            $config, $fieldMask
        ]), $this->lro, null);
    }

    public function testDeleteInstanceConfig()
    {
        $this->assertCallCorrect('deleteInstanceConfig', [
            'name' => self::CONFIG
        ], $this->expectResourceHeader(self::CONFIG, [
            self::CONFIG
        ]));
    }

    public function testListInstances()
    {
        $this->assertCallCorrect('listInstances', [
            'projectName' => self::PROJECT
        ], $this->expectResourceHeader(self::PROJECT, [
            self::PROJECT
        ]));
    }

    public function testGetInstance()
    {
        $this->assertCallCorrect('getInstance', [
            'name' => self::INSTANCE,
            'projectName' => self::PROJECT
        ], $this->expectResourceHeader(self::PROJECT, [
            self::INSTANCE
        ]));
    }

    public function testGetInstanceWithFieldMaskArray()
    {
        $fieldNames = ['name', 'displayName', 'nodeCount'];

        $mask = [];
        foreach (array_values($fieldNames) as $key) {
            $mask[] = Serializer::toSnakeCase($key);
        }

        $fieldMask = $this->serializer->decodeMessage(new FieldMask, ['paths' => $mask]);
        $this->assertCallCorrect('getInstance', [
            'name' => self::INSTANCE,
            'projectName' => self::PROJECT,
            'fieldMask' => $fieldNames
        ], $this->expectResourceHeader(self::PROJECT, [
            self::INSTANCE,
            ['fieldMask' => $fieldMask]
        ]));
    }

    public function testGetInstanceWithFieldMaskString()
    {
        $fieldNames = 'nodeCount';
        $mask[] = Serializer::toSnakeCase($fieldNames);

        $fieldMask = $this->serializer->decodeMessage(new FieldMask, ['paths' => $mask]);
        $this->assertCallCorrect('getInstance', [
            'name' => self::INSTANCE,
            'projectName' => self::PROJECT,
            'fieldMask' => $fieldNames
        ], $this->expectResourceHeader(self::PROJECT, [
            self::INSTANCE,
            ['fieldMask' => $fieldMask]
        ]));
    }

    public function testCreateInstance()
    {
        list ($args, $instance) = $this->instance();

        $this->assertCallCorrect('createInstance', [
            'projectName' => self::PROJECT,
            'instanceId' => self::INSTANCE
        ] + $args, $this->expectResourceHeader(self::INSTANCE, [
            self::PROJECT,
            self::INSTANCE,
            $instance
        ]), $this->lro, null);
    }

    public function testCreateInstanceWithProcessingNodes()
    {
        list ($args, $instance) = $this->instance(true, false);

        $this->assertCallCorrect('createInstance', [
            'projectName' => self::PROJECT,
            'instanceId' => self::INSTANCE,
            'processingUnits' => 1000
        ] + $args, $this->expectResourceHeader(self::INSTANCE, [
            self::PROJECT,
            self::INSTANCE,
            $instance
        ]), $this->lro, null);
    }

    public function testUpdateInstance()
    {
        list ($args, $instance, $fieldMask) = $this->instance(false);

        $this->assertCallCorrect('updateInstance', $args, $this->expectResourceHeader(self::INSTANCE, [
            $instance, $fieldMask
        ]), $this->lro, null);
    }

    public function testDeleteInstance()
    {
        $this->assertCallCorrect('deleteInstance', [
            'name' => self::INSTANCE
        ], $this->expectResourceHeader(self::INSTANCE, [
            self::INSTANCE
        ]));
    }

    public function testSetInstanceIamPolicy()
    {
        $policy = ['foo' => 'bar'];

        $this->assertCallCorrect('setInstanceIamPolicy', [
            'resource' => self::INSTANCE,
            'policy' => $policy
        ], $this->expectResourceHeader(self::INSTANCE, [
            self::INSTANCE,
            $policy
        ], false));
    }

    public function testGetInstanceIamPolicy()
    {
        $this->assertCallCorrect('getInstanceIamPolicy', [
            'resource' => self::INSTANCE
        ], $this->expectResourceHeader(self::INSTANCE, [
            self::INSTANCE
        ]));
    }

    public function testTestInstanceIamPermissions()
    {
        $permissions = ['permission1', 'permission2'];
        $this->assertCallCorrect('testInstanceIamPermissions', [
            'resource' => self::INSTANCE,
            'permissions' => $permissions
        ], $this->expectResourceHeader(self::INSTANCE, [
            self::INSTANCE,
            $permissions
        ], false));
    }

    public function testListDatabases()
    {
        $this->assertCallCorrect('listDatabases', [
            'instance' => self::INSTANCE
        ], $this->expectResourceHeader(self::INSTANCE, [
            self::INSTANCE
        ]));
    }

    public function testCreateDatabase()
    {
        $createStmt = 'CREATE Foo';
        $extraStmts = [
            'CREATE TABLE Bar'
        ];
        $encryptionConfig = ['kmsKeyName' => 'kmsKeyName'];
        $expectedEncryptionConfig = $this->serializer->decodeMessage(new EncryptionConfig, $encryptionConfig);

        $this->assertCallCorrect('createDatabase', [
            'instance' => self::INSTANCE,
            'createStatement' => $createStmt,
            'extraStatements' => $extraStmts,
            'encryptionConfig' => $encryptionConfig
        ], $this->expectResourceHeader(self::INSTANCE, [
            self::INSTANCE,
            $createStmt,
            [
                'extraStatements' => $extraStmts,
                'encryptionConfig' => $expectedEncryptionConfig
            ]
        ]), $this->lro, null);
    }

    public function testCreateBackup()
    {
        $backupId = "backup-id";
        $expireTime = new \DateTime("+ 7 hours");
        $backup = [
            'database' => self::DATABASE,
            'expireTime' => $expireTime->format('Y-m-d\TH:i:s.u\Z')
        ];
        $expectedBackup = $this->serializer->decodeMessage(new Backup(), [
            'expireTime' => $this->formatTimestampForApi($backup['expireTime'])
        ] + $backup);

        $encryptionConfig = [
            'kmsKeyName' => 'kmsKeyName',
            'encryptionType' => CreateBackupEncryptionConfig\EncryptionType::CUSTOMER_MANAGED_ENCRYPTION
        ];
        $expectedEncryptionConfig = $this->serializer->decodeMessage(
            new CreateBackupEncryptionConfig,
            $encryptionConfig
        );

        $this->assertCallCorrect('createBackup', [
            'instance' => self::INSTANCE,
            'backupId' => $backupId,
            'backup' => $backup,
            'encryptionConfig' => $encryptionConfig
        ], $this->expectResourceHeader(self::INSTANCE, [
            self::INSTANCE,
            $backupId,
            $expectedBackup,
            [
                'encryptionConfig' => $expectedEncryptionConfig
            ]
        ]), $this->lro, null);
    }

    public function testRestoreDatabase()
    {
        $databaseId = 'test-database';
        $encryptionConfig = [
            'kmsKeyName' => 'kmsKeyName',
            'encryptionType' => RestoreDatabaseEncryptionConfig\EncryptionType::CUSTOMER_MANAGED_ENCRYPTION
        ];
        $expectedEncryptionConfig = $this->serializer->decodeMessage(
            new RestoreDatabaseEncryptionConfig,
            $encryptionConfig
        );

        $this->assertCallCorrect('restoreDatabase', [
            'instance' => self::INSTANCE,
            'databaseId' => $databaseId,
            'encryptionConfig' => $encryptionConfig
        ], $this->expectResourceHeader(self::INSTANCE, [
            self::INSTANCE,
            $databaseId,
            [
                'encryptionConfig' => $expectedEncryptionConfig
            ]
        ]), $this->lro, null);
    }

    public function testUpdateDatabaseDdl()
    {
        $statements = [
            'CREATE TABLE Bar'
        ];

        $this->assertCallCorrect('updateDatabaseDdl', [
            'name' => self::DATABASE,
            'statements' => $statements
        ], $this->expectResourceHeader(self::DATABASE, [
            self::DATABASE,
            $statements
        ], false), $this->lro, null);
    }

    public function testDropDatabase()
    {
        $this->assertCallCorrect('dropDatabase', [
            'name' => self::DATABASE
        ], $this->expectResourceHeader(self::DATABASE, [
            self::DATABASE
        ]));
    }

    public function testGetDatabase()
    {
        $this->assertCallCorrect('getDatabase', [
            'name' => self::DATABASE
        ], $this->expectResourceHeader(self::DATABASE, [
            self::DATABASE
        ]));
    }

    public function testGetDatabaseDdl()
    {
        $this->assertCallCorrect('getDatabaseDdl', [
            'name' => self::DATABASE
        ], $this->expectResourceHeader(self::DATABASE, [
            self::DATABASE
        ]));
    }

    public function testSetDatabaseIamPolicy()
    {
        $policy = ['foo' => 'bar'];

        $this->assertCallCorrect('setDatabaseIamPolicy', [
            'resource' => self::DATABASE,
            'policy' => $policy
        ], $this->expectResourceHeader(self::DATABASE, [
            self::DATABASE,
            $policy
        ], false));
    }

    public function testGetDatabaseIamPolicy()
    {
        $this->assertCallCorrect('getDatabaseIamPolicy', [
            'resource' => self::DATABASE
        ], $this->expectResourceHeader(self::DATABASE, [
            self::DATABASE
        ]));
    }

    public function testTestDatabaseIamPermissions()
    {
        $permissions = ['permission1', 'permission2'];
        $this->assertCallCorrect('testDatabaseIamPermissions', [
            'resource' => self::DATABASE,
            'permissions' => $permissions
        ], $this->expectResourceHeader(self::DATABASE, [
            self::DATABASE,
            $permissions
        ], false));
    }

    /**
     * @dataProvider larOptions
     */
    public function testCreateSession($larEnabled, $grpcConfig)
    {
        $labels = ['foo' => 'bar'];

        $this->assertCallCorrect('createSession', [
            'database' => self::DATABASE,
            'session' => [
                'labels' => $labels
            ]
        ], $this->expectResourceHeader(self::DATABASE, [
            self::DATABASE,
            [
                'session' => (new Session)->setLabels($labels)
            ]
        ], true, $larEnabled), null, '', $grpcConfig);
    }

    public function testCreateSessionAsync()
    {
        $promise = $this->prophesize(PromiseInterface::class)->reveal();
        $client = $this->prophesize(SpannerClient::class);
        $transport = $this->prophesize(TransportInterface::class);
        $transport->startUnaryCall(
            Argument::type(Call::class),
            Argument::withEntry('headers', [
                'x-goog-spanner-route-to-leader' => ['true'],
                'google-cloud-resource-prefix' => ['database1']
            ])
        )->willReturn($promise);

        $client->getTransport()->willReturn($transport->reveal());

        $grpc = new Grpc(['gapicSpannerClient' => $client->reveal()]);

        $promise = $grpc->createSessionAsync([
            'database' => 'database1',
            'session' => [
                'labels' => [ 'foo' => 'bar' ]
            ]
        ]);

        $this->assertInstanceOf(PromiseInterface::class, $promise);
    }

    /**
     * @dataProvider larOptions
     */
    public function testBatchCreateSessions($larEnabled, $grpcConfig)
    {
        $count = 10;
        $template = [
            'labels' => [
                'foo' => 'bar'
            ]
        ];

        $this->assertCallCorrect('batchCreateSessions', [
            'database' => self::DATABASE,
            'sessionCount' => $count,
            'sessionTemplate' => $template
        ], $this->expectResourceHeader(self::DATABASE, [
            self::DATABASE, $count, [
                'sessionTemplate' => $this->serializer->decodeMessage(new Session, $template)
            ]
        ], true, $larEnabled), null, '', $grpcConfig);
    }

    public function testBatchWrite()
    {
        $mutationGroups = [
            (new MutationGroup(false))
                ->insertOrUpdate(
                    "Singers",
                    ['SingerId' => 16, 'FirstName' => 'Scarlet', 'LastName' => 'Terry']
                )->toArray(),
            (new MutationGroup(false))
                ->insertOrUpdate(
                    "Singers",
                    ['SingerId' => 17, 'FirstName' => 'Marc', 'LastName' => 'Kristen']
                )->insertOrUpdate(
                    "Albums",
                    ['AlbumId' => 1, 'SingerId' => 17, 'AlbumTitle' => 'Total Junk']
                )->toArray()
        ];

        $expectedMutationGroups = [
            new MutationGroupProto(['mutations' => [
                new Mutation(['insert_or_update' => new Write([
                    'table' => 'Singers',
                    'columns' => ['SingerId', 'FirstName', 'LastName'],
                    'values' => [new ListValue(['values' => [
                        new Value(['string_value' => '16']),
                        new Value(['string_value' => 'Scarlet']),
                        new Value(['string_value' => 'Terry'])
                    ]])]
                ])])
            ]]),
            new MutationGroupProto(['mutations' => [
                new Mutation(['insert_or_update' => new Write([
                    'table' => 'Singers',
                    'columns' => ['SingerId', 'FirstName', 'LastName'],
                    'values' => [new ListValue(['values' => [
                        new Value(['string_value' => '17']),
                        new Value(['string_value' => 'Marc']),
                        new Value(['string_value' => 'Kristen'])
                    ]])]
                ])]),
                new Mutation(['insert_or_update' => new Write([
                    'table' => 'Albums',
                    'columns' => ['AlbumId', 'SingerId', 'AlbumTitle'],
                    'values' => [new ListValue(['values' => [
                        new Value(['string_value' => '1']),
                        new Value(['string_value' => '17']),
                        new Value(['string_value' => 'Total Junk'])
                    ]])]
                ])]),
            ]]),
        ];

        $this->assertCallCorrect(
            'batchWrite',
            [
                'database' => self::DATABASE,
                'session'  => self::SESSION,
                'mutationGroups' => $mutationGroups,
            ],
            $this->expectResourceHeader(self::DATABASE, [
                self::SESSION,
                $expectedMutationGroups,
                []
            ]),
        );
    }

    /**
     * @dataProvider larOptions
     */
    public function testGetSession($larEnabled, $grpcConfig)
    {
        $this->assertCallCorrect('getSession', [
            'database' => self::DATABASE,
            'name' => self::SESSION
        ], $this->expectResourceHeader(self::DATABASE, [
            self::SESSION
        ], true, $larEnabled), null, '', $grpcConfig);
    }

    public function testDeleteSession()
    {
        $this->assertCallCorrect('deleteSession', [
            'database' => self::DATABASE,
            'name' => self::SESSION
        ], $this->expectResourceHeader(self::DATABASE, [
            self::SESSION
        ]));
    }

    public function testDeleteSessionAsync()
    {
        $promise = $this->prophesize(PromiseInterface::class)
            ->reveal();
        $sessionName = 'session1';
        $databaseName = 'database1';
        $request = new DeleteSessionRequest();
        $request->setName($sessionName);
        $client = $this->prophesize(SpannerClient::class);
        $transport = $this->prophesize(TransportInterface::class);
        $transport->startUnaryCall(
            Argument::type(Call::class),
            Argument::type('array')
        )->willReturn($promise);
        $client->getTransport()
            ->willReturn($transport->reveal());
        $grpc = new Grpc(['gapicSpannerClient' => $client->reveal()]);
        $call = $grpc->deleteSessionAsync([
            'name' => $sessionName,
            'database' => $databaseName
        ]);

        $this->assertInstanceOf(PromiseInterface::class, $call);
    }

    /**
     * @dataProvider larOptions
     */
    public function testExecuteStreamingSql($larEnabled, $grpcConfig)
    {
        $sql = 'SELECT 1';

        $mapper = new ValueMapper(false);
        $mapped = $mapper->formatParamsForExecuteSql(['foo' => 'bar']);

        $expectedParams = $this->serializer->decodeMessage(
            new Struct,
            $this->formatStructForApi($mapped['params'])
        );

        $expectedParamTypes = $mapped['paramTypes'];
        foreach ($expectedParamTypes as $key => $param) {
            $expectedParamTypes[$key] = $this->serializer->decodeMessage(new Type, $param);
        }

        $this->assertCallCorrect('executeStreamingSql', [
            'session' => self::SESSION,
            'sql' => $sql,
            'transactionId' => self::TRANSACTION,
            'database' => self::DATABASE,
            'headers' => ['x-goog-spanner-route-to-leader' => ['true']]
        ] + $mapped, $this->expectResourceHeader(self::DATABASE, [
            self::SESSION,
            $sql,
            [
                'transaction' => $this->transactionSelector(),
                'params' => $expectedParams,
                'paramTypes' => $expectedParamTypes
            ]
        ], true, $larEnabled), null, '', $grpcConfig);
    }

    public function testExecuteStreamingSqlWithRequestOptions()
    {
        $sql = 'SELECT 1';
        $requestOptions = ["priority" => RequestOptions\Priority::PRIORITY_LOW];
        $expectedRequestOptions = $this->serializer->decodeMessage(
            new RequestOptions,
            $requestOptions
        );

        $this->assertCallCorrect('executeStreamingSql', [
                'session' => self::SESSION,
                'sql' => $sql,
                'transactionId' => self::TRANSACTION,
                'database' => self::DATABASE,
                'params' => [],
                'requestOptions' => $requestOptions
            ], $this->expectResourceHeader(self::DATABASE, [
                self::SESSION,
                $sql,
                [
                    'transaction' => $this->transactionSelector(),
                    'requestOptions' => $expectedRequestOptions
                ]
            ]));
    }

    /**
     * @dataProvider queryOptions
     */
    public function testExecuteStreamingSqlWithQueryOptions(
        array $methodOptions,
        array $envOptions,
        array $clientOptions,
        array $expectedOptions
    ) {
        $sql = 'SELECT 1';

        if (array_key_exists('optimizerVersion', $envOptions)) {
            putenv('SPANNER_OPTIMIZER_VERSION=' . $envOptions['optimizerVersion']);
        }
        if (array_key_exists('optimizerStatisticsPackage', $envOptions)) {
            putenv('SPANNER_OPTIMIZER_STATISTICS_PACKAGE=' . $envOptions['optimizerStatisticsPackage']);
        }
        $gapic = $this->prophesize(SpannerClient::class);
        $gapic->executeStreamingSql(
            self::SESSION,
            $sql,
            Argument::that(function ($arguments) use ($expectedOptions) {
                $queryOptions = $arguments['queryOptions'] ?? null;
                $expectedOptions += ['optimizerVersion' => null, 'optimizerStatisticsPackage' => null];
                $this->assertEquals(
                    $queryOptions ? $queryOptions->getOptimizerVersion() : null,
                    $expectedOptions['optimizerVersion']
                );
                $this->assertEquals(
                    $queryOptions ? $queryOptions->getOptimizerStatisticsPackage() : null,
                    $expectedOptions['optimizerStatisticsPackage']
                );
                return true;
            })
        )->shouldBeCalledOnce();

        $grpc = new Grpc([
            'gapicSpannerClient' => $gapic->reveal()
        ] + ['queryOptions' => $clientOptions]);

        $grpc->executeStreamingSql([
            'database' => self::DATABASE,
            'session' => self::SESSION,
            'sql' => $sql,
            'params' => []
        ] + ['queryOptions' => $methodOptions]);

        if ($envOptions) {
            putenv('SPANNER_OPTIMIZER_VERSION=');
            putenv('SPANNER_OPTIMIZER_STATISTICS_PACKAGE=');
        }
    }

    public function queryOptions()
    {
        return [
            [
                ['optimizerVersion' => '8'],
                [
                    'optimizerVersion' => '7',
                    'optimizerStatisticsPackage' => "auto_20191128_18_47_22UTC",
                ],
                ['optimizerStatisticsPackage' => "auto_20191128_14_47_22UTC"],
                [
                    'optimizerVersion' => '8',
                    'optimizerStatisticsPackage' => "auto_20191128_18_47_22UTC",
                ]
            ],
            [
                [],
                ['optimizerVersion' => '7'],
                [
                    'optimizerVersion' => '6',
                    'optimizerStatisticsPackage' => "auto_20191128_14_47_22UTC",
                ],
                [
                    'optimizerVersion' => '7',
                    'optimizerStatisticsPackage' => "auto_20191128_14_47_22UTC",
                ]
            ],
            [
                ['optimizerStatisticsPackage' => "auto_20191128_23_47_22UTC"],
                [],
                [
                    'optimizerVersion' => '6',
                    'optimizerStatisticsPackage' => "auto_20191128_14_47_22UTC",
                ],
                [
                    'optimizerVersion' => '6',
                    'optimizerStatisticsPackage' => "auto_20191128_23_47_22UTC",
                ]
            ],
            [
                [],
                [],
                [],
                []
            ]
        ];
    }

    /**
     * @dataProvider readKeysets
     */
    public function testStreamingRead($keyArg, $keyObj, $larEnabled, $grpcConfig)
    {
        $columns = [
            'id',
            'name'
        ];

        $this->assertCallCorrect('streamingRead', [
            'keySet' => $keyArg,
            'transactionId' => self::TRANSACTION,
            'session' => self::SESSION,
            'table' => self::TABLE,
            'columns' => $columns,
            'database' => self::DATABASE,
            'headers' => ['x-goog-spanner-route-to-leader' => ['true']]
        ], $this->expectResourceHeader(self::DATABASE, [
            self::SESSION,
            self::TABLE,
            $columns,
            $keyObj,
            [
                'transaction' => $this->transactionSelector()
            ]
        ], true, $larEnabled), null, '', $grpcConfig);
    }

    public function testStreamingReadWithRequestOptions()
    {
        $columns = [
            'id',
            'name'
        ];
        $requestOptions = ['priority' => RequestOptions\Priority::PRIORITY_LOW];
        $expectedRequestOptions = $this->serializer->decodeMessage(
            new RequestOptions,
            $requestOptions
        );

        $this->assertCallCorrect('streamingRead', [
            'keySet' => [],
            'transactionId' => self::TRANSACTION,
            'session' => self::SESSION,
            'table' => self::TABLE,
            'columns' => $columns,
            'database' => self::DATABASE,
            'requestOptions' => $requestOptions
        ], $this->expectResourceHeader(self::DATABASE, [
            self::SESSION,
            self::TABLE,
            $columns,
            new KeySet,
            [
                'transaction' => $this->transactionSelector(),
                'requestOptions' => $expectedRequestOptions
            ]
        ]));
    }

    public function readKeysets()
    {
        $this->setUp();

        return [
            [
                [],
                new KeySet,
                true,
                ['routeToLeader' => true]
            ], [
                ['keys' => [1]],
                $this->serializer->decodeMessage(new KeySet, [
                    'keys' => [
                        [
                            'values' => [
                                [
                                    'number_value' => 1
                                ]
                            ]
                        ]
                    ]
                ]),
                false,
                ['routeToLeader' => false]
            ], [
                ['keys' => [[1,1]]],
                $this->serializer->decodeMessage(new KeySet, [
                    'keys' => [
                        [
                            'values' => [
                                [
                                    'number_value' => 1
                                ],
                                [
                                    'number_value' => 1
                                ]
                            ]
                        ]
                    ]
                ]),
                false,
                ['routeToLeader' => false]
            ]
        ];
    }

    /**
     * @dataProvider larOptions
     */
    public function testExecuteBatchDml($larEnabled, $grpcConfig)
    {
        $statements = [
            [
                'sql' => 'SELECT 1',
                'params' => []
            ]
        ];

        $statementsObjs = [
            new Statement([
                'sql' => 'SELECT 1'
            ])
        ];

        $this->assertCallCorrect('executeBatchDml', [
            'session' => self::SESSION,
            'database' => self::DATABASE,
            'transactionId' => self::TRANSACTION,
            'statements' => $statements,
            'seqno' => 1
        ], $this->expectResourceHeader(self::DATABASE, [
            self::SESSION,
            $this->transactionSelector(),
            $statementsObjs,
            1
        ], true, $larEnabled), null, '', $grpcConfig);
    }

    public function testExecuteBatchDmlWithRequestOptions()
    {
        $statements = [
            [
                'sql' => 'SELECT 1',
                'params' => []
            ]
        ];

        $statementsObjs = [
            new Statement([
                'sql' => 'SELECT 1'
            ])
        ];
        $requestOptions = ['priority' => RequestOptions\Priority::PRIORITY_LOW];
        $expectedRequestOptions = $this->serializer->decodeMessage(
            new RequestOptions,
            $requestOptions
        );


        $this->assertCallCorrect('executeBatchDml', [
            'session' => self::SESSION,
            'database' => self::DATABASE,
            'transactionId' => self::TRANSACTION,
            'statements' => $statements,
            'seqno' => 1,
            'requestOptions' => $requestOptions
        ], $this->expectResourceHeader(self::DATABASE, [
            self::SESSION,
            $this->transactionSelector(),
            $statementsObjs,
            1,
            ['requestOptions' => $expectedRequestOptions]
        ], true, true));
    }

    /**
     * @dataProvider transactionTypes
     */
    public function testBeginTransaction($optionsArr, $optionsObj, $larEnabled, $grpcConfig)
    {
        $this->assertCallCorrect('beginTransaction', [
            'session' => self::SESSION,
            'transactionOptions' => $optionsArr,
            'database' => self::DATABASE
        ], $this->expectResourceHeader(self::DATABASE, [
            self::SESSION,
            $optionsObj
        ], true, $larEnabled, $optionsArr), null, '', $grpcConfig);
    }

    public function transactionTypes()
    {
        $ts = (new \DateTime)->format('Y-m-d\TH:i:s.u\Z');
        $pbTs = new Timestamp($this->formatTimestampForApi($ts));
        $readOnlyClass = PHP_VERSION_ID >= 80100
            ? PBReadOnly::class
            : 'Google\Cloud\Spanner\V1\TransactionOptions\ReadOnly';

        return [
            [
                ['readWrite' => []],
                new TransactionOptions([
                    'read_write' => new ReadWrite
                ]),
                true,
                ['routeToLeader' => true]
            ], [
                [
                    'readOnly' => [
                        'minReadTimestamp' => $ts,
                        'readTimestamp' => $ts
                    ]
                ],
                new TransactionOptions([
                    'read_only' => new $readOnlyClass([
                        'min_read_timestamp' => $pbTs,
                        'read_timestamp' => $pbTs
                    ])
                ]),
                true,
                ['routeToLeader' => true]
            ], [
                ['partitionedDml' => []],
                new TransactionOptions([
                    'partitioned_dml' => new PartitionedDml
                ]),
                true,
                ['routeToLeader' => true]
            ]
        ];
    }

    /**
     * @dataProvider commit
     */
    public function testCommit($mutationsArr, $mutationsObjArr, $larEnabled, $grpcConfig)
    {
        $this->assertCallCorrect('commit', [
            'session' => self::SESSION,
            'mutations' => $mutationsArr,
            'singleUseTransaction' => true,
            'database' => self::DATABASE
        ], $this->expectResourceHeader(self::DATABASE, [
            self::SESSION,
            $mutationsObjArr,
            [
                'singleUseTransaction' => new TransactionOptions([
                    'read_write' => new ReadWrite
                ])
            ]
        ], true, $grpcConfig), null, '', $grpcConfig);
    }

    /**
     * @dataProvider commit
     */
    public function testCommitWithRequestOptions($mutationsArr, $mutationsObjArr)
    {
        $requestOptions = ['priority' => RequestOptions\Priority::PRIORITY_LOW];
        $expectedRequestOptions = $this->serializer->decodeMessage(
            new RequestOptions,
            $requestOptions
        );
        $this->assertCallCorrect('commit', [
            'session' => self::SESSION,
            'mutations' => $mutationsArr,
            'singleUseTransaction' => true,
            'database' => self::DATABASE,
            'requestOptions' => $requestOptions
        ], $this->expectResourceHeader(self::DATABASE, [
            self::SESSION,
            $mutationsObjArr,
            [
                'singleUseTransaction' => new TransactionOptions([
                    'read_write' => new ReadWrite
                ]),
                'requestOptions' => $expectedRequestOptions
            ]
        ], true, true));
    }

    public function commit()
    {
        $mutation = [
            'table' => self::TABLE,
            'columns' => [
                'col1'
            ],
            'values' => [
                'val1'
            ]
        ];

        $write = new Write([
            'table' => self::TABLE,
            'columns' => ['col1'],
            'values' => [
                new ListValue([
                    'values' => [
                        new Value([
                            'string_value' => 'val1'
                        ])
                    ]
                ])
            ]
        ]);

        return [
            [
                [], [], true, ['routeToLeader' => true]
            ], [
                [
                    [
                        'delete' => [
                            'table' => self::TABLE,
                            'keySet' => []
                        ]
                    ]
                ],
                [
                    new Mutation([
                        'delete' => new Delete([
                            'table' => self::TABLE,
                            'key_set' => new KeySet
                        ])
                    ])
                ],
                true,
                ['routeToLeader' => true]
            ], [
                [
                    [
                        'insert' => $mutation
                    ]
                ],
                [
                    new Mutation([
                        'insert' => $write
                    ])
                ],
                true,
                ['routeToLeader' => true]
            ], [
                [
                    [
                        'update' => $mutation
                    ]
                ],
                [
                    new Mutation([
                        'update' => $write
                    ])
                ],
                true,
                ['routeToLeader' => true]
            ], [
                [
                    [
                        'insertOrUpdate' => $mutation
                    ]
                ],
                [
                    new Mutation([
                        'insert_or_update' => $write
                    ])
                ],
                true,
                ['routeToLeader' => true]
            ], [
                [
                    [
                        'replace' => $mutation
                    ]
                ],
                [
                    new Mutation([
                        'replace' => $write
                    ])
                ],
                true,
                ['routeToLeader' => true]
            ]
        ];
    }

    /**
     * @dataProvider larOptions
     */
    public function testRollback($larEnabled, $grpcConfig)
    {
        $this->assertCallCorrect('rollback', [
            'session' => self::SESSION,
            'transactionId' => self::TRANSACTION,
            'database' => self::DATABASE
        ], $this->expectResourceHeader(self::DATABASE, [
            self::SESSION,
            self::TRANSACTION
        ], true, $larEnabled), null, '', $grpcConfig);
    }

    /**
     * @dataProvider partitionOptions
     */
    public function testPartitionQuery($partitionOptions, $partitionOptionsObj, $larEnabled, $grpcConfig)
    {
        $sql = 'SELECT 1';
        $this->assertCallCorrect('partitionQuery', [
            'session' => self::SESSION,
            'sql' => $sql,
            'params' => [],
            'transactionId' => self::TRANSACTION,
            'database' => self::DATABASE,
            'partitionOptions' => $partitionOptions,
        ], $this->expectResourceHeader(self::DATABASE, [
            self::SESSION,
            $sql,
            [
                'transaction' => $this->transactionSelector(),
                'partitionOptions' => $partitionOptionsObj
            ]
        ], true, $larEnabled), null, '', $grpcConfig);
    }

    /**
     * @dataProvider partitionOptions
     */
    public function testPartitionRead($partitionOptions, $partitionOptionsObj, $larEnabled, $grpcConfig)
    {
        $this->assertCallCorrect('partitionRead', [
            'session' => self::SESSION,
            'keySet' => [],
            'table' => self::TABLE,
            'transactionId' => self::TRANSACTION,
            'database' => self::DATABASE,
            'partitionOptions' => $partitionOptions,
        ], $this->expectResourceHeader(self::DATABASE, [
            self::SESSION,
            self::TABLE,
            new KeySet,
            [
                'transaction' => $this->transactionSelector(),
                'partitionOptions' => $partitionOptionsObj
            ]
        ], true, $larEnabled), null, '', $grpcConfig);
    }

    public function partitionOptions()
    {
        return [
            [
                [],
                new PartitionOptions,
                true,
                ['routeToLeader' => true]
            ],
            [
                ['maxPartitions' => 10],
                new PartitionOptions([
                    'max_partitions' => 10
                ]),
                true,
                ['routeToLeader' => true]
            ]
        ];
    }

    /**
     * @dataProvider keysets
     */
    public function testFormatKeySet($input, $expected)
    {
        $this->assertEquals(
            $expected,
            $this->callPrivateMethod('formatKeySet', [$input])
        );
    }

    public function keysets()
    {
        return [
            [
                [],
                []
            ], [
                [
                    'keys' => [
                        [
                            1,
                            2
                        ]
                    ]
                ],
                [
                    'keys' => [
                        $this->formatListForApi([1, 2])
                    ]
                ]
            ], [
                [
                    'ranges' => [
                        [
                            'startOpen' => [1],
                            'endClosed' => [2]
                        ]
                    ],
                ], [
                    'ranges' => [
                        [
                            'startOpen' => $this->formatListForApi([1]),
                            'endClosed' => $this->formatListForApi([2]),
                        ]
                    ]
                ]
            ], [
                [
                    'ranges' => []
                ],
                []
            ]
        ];
    }

    /**
     * @dataProvider fieldvalues
     */
    public function testFieldValue($input, $expected)
    {
        $this->assertEquals(
            $expected,
            $this->callPrivateMethod('fieldValue', [$input])
        );
    }

    public function fieldvalues()
    {
        return [
            [
                'foo',
                new Value([
                    'string_value' => 'foo'
                ])
            ], [
                1,
                new Value([
                    'number_value' => 1
                ])
            ], [
                false,
                new Value([
                    'bool_value' => false
                ])
            ], [
                null,
                new Value([
                    'null_value' => NullValue::NULL_VALUE
                ])
            ], [
                [
                    'a' => 'b'
                ],
                new Value([
                    'struct_value' => new Struct([
                        'fields' => [
                            'a' => new Value([
                                'string_value' => 'b'
                            ])
                        ]
                    ])
                ])
            ], [
                [
                    'a', 'b', 'c'
                ],
                new Value([
                    'list_value' => new ListValue([
                        'values' => [
                            new Value([
                                'string_value' => 'a'
                            ]),
                            new Value([
                                'string_value' => 'b'
                            ]),
                            new Value([
                                'string_value' => 'c'
                            ]),
                        ]
                    ])
                ])
            ]
        ];
    }

    /**
     * @dataProvider transactionOptions
     */
    public function testTransactionOptions($input, $expected)
    {
        // Since the tested method uses pass-by-reference arg, the callPrivateMethod function won't work.
        // test on php7 only is better than nothing.
        if (version_compare(PHP_VERSION, '7.0.0', '<')) {
            $this->markTestSkipped('only works in php 7.');
            return;
        }

        $grpc = new Grpc;
        $createTransactionSelector = function () {
            $args = func_get_args();
            return $this->createTransactionSelector($args[0]);
        };

        $this->assertEquals(
            $expected->serializeToJsonString(),
            $createTransactionSelector->call($grpc, $input)->serializeToJsonString()
        );
    }

    public function transactionOptions()
    {
        return [
            [
                [
                    'transactionId' => self::TRANSACTION
                ],
                $this->transactionSelector()
            ], [
                [
                    'transaction' => [
                        'singleUse' => [
                            'readWrite' => []
                        ]
                    ]
                ],
                new TransactionSelector([
                    'single_use' => new TransactionOptions([
                        'read_write' => new ReadWrite
                    ])
                ])
            ], [
                [
                    'transaction' => [
                        'begin' => [
                            'readWrite' => []
                        ]
                    ]
                ],
                new TransactionSelector([
                    'begin' => new TransactionOptions([
                        'read_write' => new ReadWrite
                    ])
                ])
            ]
        ];
    }

    public function larOptions()
    {
        return [
            [
                true,
                ['routeToLeader' => true]
            ], [
                false,
                ['routeToLeader' => false]
            ]
        ];
    }

    public function testPartialResultSetCustomEncoder()
    {
        $partialResultSet = new PartialResultSet();
        $partialResultSet->mergeFromJsonString(json_encode([
            'metadata' => [
                'transaction' => [
                    'id' => base64_encode(0b00010100) // bytedata is represented as a base64-encoded string in JSON
                ],
                'rowType' => [
                    'fields' => [
                        ['type' => ['code' => 'INT64']] // enums are represented as their string equivalents in JSON
                    ]
                ],
            ],
        ]));

        $this->assertEquals(0b00010100, $partialResultSet->getMetadata()->getTransaction()->getId());
        $this->assertEquals(2, $partialResultSet->getMetadata()->getRowType()->getFields()[0]->getType()->getCode());

        // decode the message and ensure it's decoded as expected
        $grpc = new Grpc();
        $serializerProp = new \ReflectionProperty($grpc, 'serializer');
        $serializerProp->setAccessible(true);
        $serializer = $serializerProp->getValue($grpc);
        $arr = $serializer->encodeMessage($partialResultSet);

        // We expect this to be the binary string
        $this->assertEquals(0b00010100, $arr['metadata']['transaction']['id']);
        // We expect this to be the integer
        $this->assertEquals(2, $arr['metadata']['rowType']['fields'][0]['type']['code']);
    }
    private function assertCallCorrect(
        $method,
        array $args,
        array $expectedArgs,
        $return = null,
        $result = '',
        $grpcConfig = []
    ) {
        $this->requestWrapper->send(
            Argument::type('callable'),
            $expectedArgs,
            Argument::type('array')
        )->shouldBeCalled()->willReturn($return ?: $this->successMessage);

        $connection = new Grpc($grpcConfig);
        $connection->setRequestWrapper($this->requestWrapper->reveal());

        $this->assertEquals($result !== '' ? $result : $this->successMessage, $connection->$method($args));
    }

    /**
     * Add the resource header to the args list.
     *
     * @param string $val The header value to add.
     * @param array $args The remaining call args.
     * @param boolean $append If true, should the last value in $args be an
     *     array, the header will be appended to that array. If false, the
     *     header will be added to a separate array.
     * @param boolean $lar If true, will add the x-goog-spanner-route-to-leader
     *    header.
     * @param array $options The options to add to the call.
     * @return array
     */
    private function expectResourceHeader(
        $val,
        array $args,
        $append = true,
        $lar = false,
        $options = []
    ) {
        $header = [
            'google-cloud-resource-prefix' => [$val]
        ];
        if ($lar && !isset($options['readOnly'])) {
            $header['x-goog-spanner-route-to-leader'] = ['true'];
        }

        $end = end($args);
        if (!is_array($end) || !$append) {
            $args[]['headers'] = $header;
        } elseif (is_array($end)) {
            $keys = array_keys($args);
            $key = end($keys);
            $args[$key]['headers'] = $header;
        }
        return $args;
    }

    private function callPrivateMethod($method, array $args)
    {
        $grpc = new Grpc;
        $ref = new \ReflectionClass($grpc);

        $method = $ref->getMethod($method);
        $method->setAccessible(true);

        array_unshift($args, $grpc);
        return call_user_func_array([$method, 'invoke'], $args);
    }

    private function instanceConfig($full = true)
    {
        $args = [
            'name' => self::CONFIG,
            'displayName' => self::CONFIG,
        ];

        if ($full) {
            $args = array_merge($args, [
                'baseConfig' => self::CONFIG,
                'configType' => InstanceConfig\Type::TYPE_UNSPECIFIED,
                'state' => State::CREATING,
                'labels' => [],
                'replicas' => [],
                'optionalReplicas' => [],
                'leaderOptions' => [],
                'reconciling' => false,
            ]);
        }

        $mask = [];
        foreach (array_keys($args) as $key) {
            if ($key != "name") {
                $mask[] = Serializer::toSnakeCase($key);
            }
        }

        $fieldMask = $this->serializer->decodeMessage(new FieldMask, ['paths' => $mask]);

        return [
            $args,
            $this->serializer->decodeMessage(new InstanceConfig, $args),
            $fieldMask
        ];
    }

    private function instance($full = true, $nodes = true)
    {
        $args = [
            'name' => self::INSTANCE,
            'displayName' => self::INSTANCE,
        ];

        if ($full) {
            if ($nodes) {
                $args = array_merge($args, [
                    'config' => self::CONFIG,
                    'nodeCount' => 1,
                    'state' => State::CREATING,
                    'labels' => []
                ]);
            } else {
                $args = array_merge($args, [
                    'config' => self::CONFIG,
                    'processingUnits' => 1000,
                    'state' => State::CREATING,
                    'labels' => []
                ]);
            }
        }

        $mask = [];
        foreach (array_keys($args) as $key) {
            if ($key != "name") {
                $mask[] = Serializer::toSnakeCase($key);
            }
        }

        $fieldMask = $this->serializer->decodeMessage(new FieldMask, ['paths' => $mask]);

        return [
            $args,
            $this->serializer->decodeMessage(new Instance, $args),
            $fieldMask
        ];
    }

    private function transactionSelector()
    {
        return new TransactionSelector([
            'id' => self::TRANSACTION
        ]);
    }
}

//@codingStandardsIgnoreStart
class GrpcStub extends Grpc
{
    public $config;

    protected function constructGapic($gapicName, array $config)
    {
        $this->config = $config;

        return parent::constructGapic($gapicName, $config);
    }
}
//@codingStandardsIgnoreEnd
